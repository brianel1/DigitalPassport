<?php

namespace App\Services;

use App\Models\Certification;
use App\Models\Skill;
use App\Models\VerificationReview;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EvidenceWorkflow
{
    public function save(Request $request, Model $record, array $validated): Model
    {
        $file = $request->file('evidence');
        $path = $file?->store('evidence/'.$request->user()->id, 'local');
        try {
            return DB::transaction(function () use ($request, $record, $validated, $file, $path) {
                if ($record->exists) $record = $record->newQuery()->whereKey($record->id)->lockForUpdate()->firstOrFail();
                $oldSkills = $record->exists ? $record->skills()->pluck('skills.id')->all() : [];
                if ($record->exists) {
                    // Retain the reviewed state before the student's next revision.
                    $latest = $record->morphMany(VerificationReview::class, 'reviewable')->latest()->first();
                    if ($latest && !$latest->record_snapshot) $latest->update(['record_snapshot' => $this->snapshot($record)]);
                }
                $record->fill(collect($validated)->except(['evidence', 'skill_ids'])->all());
                $record->forceFill([
                    'user_id' => $request->user()->id, 'status' => 'pending',
                    'reviewed_by' => null, 'reviewed_at' => null, 'admin_remarks' => null,
                    'submitted_at' => now(), 'submission_revision' => $record->exists ? $record->submission_revision + 1 : 1,
                ])->save();
                $record->skills()->sync($validated['skill_ids'] ?? []);
                if ($file) $record->evidenceFiles()->create([
                    'user_id' => $request->user()->id, 'disk' => 'local', 'path' => $path,
                    'original_name' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(), 'size' => $file->getSize(),
                ]);
                $this->invalidateUnsupportedSkills($oldSkills);
                return $record->refresh()->load(['evidenceFiles', 'skills', 'reviewer']);
            });
        } catch (\Throwable $error) {
            if ($path) Storage::disk('local')->delete($path);
            throw $error;
        }
    }

    public function snapshot(Model $record): array
    {
        $fields = $record instanceof \App\Models\Project
            ? ['title', 'description', 'role', 'technologies', 'project_url', 'repository_url', 'completion_date']
            : ($record instanceof Certification ? ['title', 'issuing_organization', 'issue_date', 'expiry_date', 'credential_id', 'credential_url'] : ['name', 'category', 'proficiency', 'description']);
        $snapshot = $record->only($fields);
        if (method_exists($record, 'evidenceFiles')) $snapshot['evidence_files'] = $record->evidenceFiles()->pluck('original_name', 'id')->all();
        if (method_exists($record, 'skills')) $snapshot['linked_skills'] = $record->skills()->pluck('name')->all();
        $snapshot['revision'] = $record->submission_revision ?? 1;
        return json_decode(json_encode($snapshot), true);
    }

    public function details(Model $record): array
    {
        $reviews = $record->morphMany(VerificationReview::class, 'reviewable')->with('reviewer')->latest()->get();
        $previous = $reviews->first()?->record_snapshot;
        $current = $this->snapshot($record);
        $changes = [];
        if ($previous && $record->status === 'pending') foreach ($current as $key => $value) {
            if ($key !== 'revision' && json_encode($previous[$key] ?? null) !== json_encode($value)) $changes[] = [
                'field' => ucfirst(str_replace('_', ' ', $key)), 'before' => $previous[$key] ?? null, 'after' => $value,
            ];
        }
        return [
            'revision' => $record->submission_revision ?? 1,
            'reviewerName' => $record->reviewer?->name,
            'reviewedAt' => $record->reviewed_at?->toISOString(),
            'changes' => $changes,
            'reviewHistory' => $reviews->map(fn ($review) => [
                'id' => $review->id, 'decision' => $review->decision, 'remarks' => $review->remarks,
                'reviewerName' => $review->reviewer?->name ?? 'Administrator', 'reviewedAt' => $review->created_at->toISOString(),
                'revision' => $review->record_snapshot['revision'] ?? null,
            ])->values()->all(),
            ... (method_exists($record, 'evidenceFiles') ? ['evidence' => $record->evidenceFiles->map(fn ($f) => [
                'id' => $f->id, 'name' => $f->original_name, 'mimeType' => $f->mime_type, 'size' => $f->size,
            ])->values()->all()] : []),
        ];
    }

    public function invalidateUnsupportedSkills(array $ids): void
    {
        foreach (Skill::whereIn('id', $ids)->where('status', 'verified')->get() as $skill) {
            $hasProject = $skill->projects()->where('status', 'verified')->exists();
            $hasCertificate = $skill->certifications()->where('status', 'verified')->where(fn ($q) => $q->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', today()))->exists();
            if (!$hasProject && !$hasCertificate && !app(VerifiedEvidence::class)->assessedSkill($skill->user, $skill->name)) $skill->update(['status' => 'pending', 'reviewed_by' => null, 'reviewed_at' => null, 'admin_remarks' => 'Supporting evidence changed. Link current evidence and request a new review.']);
        }
    }
}
