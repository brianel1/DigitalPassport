<?php

namespace App\Http\Controllers;

use App\Models\Certification;
use App\Models\EvidenceFile;
use App\Models\Project;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'verified', 'rejected', 'all'])],
            'type' => ['nullable', Rule::in(['project', 'certification', 'skill', 'all'])],
            'search' => ['nullable', 'string', 'max:120'],
            'age_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $status = $validated['status'] ?? 'pending';
        $type = $validated['type'] ?? 'all';
        $search = strtolower(trim($validated['search'] ?? ''));

        $projects = Project::query()
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->with(['user', 'reviewer', 'evidenceFiles', 'skills'])
            ->get()
            ->map(fn (Project $project) => $this->projectData($project));

        $certifications = Certification::query()
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->with(['user', 'reviewer', 'evidenceFiles', 'skills'])
            ->get()
            ->map(fn (Certification $certification) => $this->certificationData($certification));

        $skills = Skill::query()
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->with(['user', 'reviewer', 'projects.evidenceFiles', 'certifications.evidenceFiles'])
            ->get()
            ->map(fn (Skill $skill) => $this->skillData($skill));

        $items = collect()
            ->when($type === 'all' || $type === 'project', fn ($items) => $items->concat($projects))
            ->when($type === 'all' || $type === 'certification', fn ($items) => $items->concat($certifications))
            ->when($type === 'all' || $type === 'skill', fn ($items) => $items->concat($skills));

        if ($search !== '') {
            $items = $items->filter(fn (array $item) => str_contains(strtolower($item['studentName']), $search)
                || str_contains(strtolower($item['studentMatricNo'] ?? ''), $search)
                || str_contains(strtolower($item['title']), $search));
        }
        if ($days = $validated['age_days'] ?? null) $items = $items->filter(fn ($item) => \Carbon\Carbon::parse($item['submittedAt'])->lte(now()->subDays($days)));

        return response()->json([
            'data' => $items->sortBy('submittedAt')->values(),
        ]);
    }

    public function evidence(Request $request, string $type, int $id, EvidenceFile $file)
    {

        $modelClass = match ($type) {
            'project' => Project::class,
            'certification' => Certification::class,
            default => abort(404),
        };

        abort_unless($file->evidenceable_type === $modelClass && (int) $file->evidenceable_id === $id, 404);
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        return Storage::disk($file->disk)->download($file->path, $file->original_name, [
            'Content-Type' => $file->mime_type,
        ]);
    }

    public function update(Request $request, string $type, int $id): JsonResponse
    {
        $validated = $request->validate([
            'decision' => ['required', Rule::in(['verified', 'rejected'])],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $type, $id, $validated) {
        $found = $this->findRecord($type, $id);
        $record = $found->newQuery()->whereKey($found->id)->lockForUpdate()->firstOrFail();
        abort_unless($record->status === 'pending', 409, 'This submission was already reviewed. Refresh the queue.');
        if ($validated['decision'] === 'verified' && $record instanceof Certification && $record->expiry_date?->lt(today())) {
            throw ValidationException::withMessages(['decision' => 'This certificate has expired. Ask the student for a current credential.']);
        }

        if ($validated['decision'] === 'verified' && $record instanceof Project
            && ! $record->evidenceFiles()->exists() && blank($record->project_url) && blank($record->repository_url)) {
            throw ValidationException::withMessages(['decision' => 'A project needs an evidence file, project URL or repository URL before verification.']);
        }

        if ($validated['decision'] === 'verified' && $record instanceof Skill) {
            $hasProject = $record->projects()->where('status', 'verified')->where(function ($query) {
                $query->whereNotNull('project_url')->orWhereNotNull('repository_url')->orWhereHas('evidenceFiles');
            })->exists();
            $hasCertification = $record->certifications()->where('status', 'verified')->whereHas('evidenceFiles')->where(fn ($q) => $q->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', today()))->exists();
            if (! $hasProject && ! $hasCertification && !app(\App\Services\VerifiedEvidence::class)->assessedSkill($record->user, $record->name)) {
                throw ValidationException::withMessages(['decision' => 'Verify a linked project or certification with evidence before verifying this skill.']);
            }
        }

            $record->update([
                'status' => $validated['decision'],
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'admin_remarks' => $validated['remarks'] ?? null,
            ]);
            $record->morphMany(\App\Models\VerificationReview::class, 'reviewable')->create([
                'reviewer_id' => $request->user()->id,
                'decision' => $validated['decision'],
                'remarks' => $validated['remarks'] ?? null,
                'record_snapshot' => app(\App\Services\EvidenceWorkflow::class)->snapshot($record),
            ]);
        });

        return response()->json(['message' => 'Submission review saved successfully.']);
    }

    private function findRecord(string $type, int $id): Model
    {
        return match ($type) {
            'project' => Project::findOrFail($id),
            'certification' => Certification::findOrFail($id),
            'skill' => Skill::findOrFail($id),
            default => abort(404),
        };
    }

    /** @return array<string, mixed> */
    private function projectData(Project $project): array
    {
        return [
            ...app(\App\Services\EvidenceWorkflow::class)->details($project),
            'id' => $project->id,
            'type' => 'project',
            'studentName' => $project->user->name,
            'studentMatricNo' => $project->user->matric_no,
            'title' => $project->title,
            'submittedAt' => ($project->submitted_at ?? $project->created_at)->toISOString(),
            'status' => $project->status,
            'evidenceFiles' => $project->evidenceFiles->map(fn (EvidenceFile $file) => $this->fileData($file))->values(),
            'reviewerName' => $project->reviewer?->name,
            'reviewedAt' => $project->reviewed_at?->toISOString(),
            'adminRemarks' => $project->admin_remarks,
            'details' => [
                'id' => $project->id,
                'title' => $project->title,
                'description' => $project->description,
                'role' => $project->role,
                'technologies' => $project->technologies ?? [],
                'projectUrl' => $project->project_url ?: $project->repository_url,
                'evidenceFiles' => $project->evidenceFiles->map(fn (EvidenceFile $file) => $this->fileData($file))->values(),
                'completionDate' => $project->completion_date?->toDateString(),
                'status' => $project->status,
                'linkedSkills' => $project->skills->pluck('id')->values(),
                'createdAt' => $project->created_at->toISOString(),
                'updatedAt' => $project->updated_at->toISOString(),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function certificationData(Certification $certification): array
    {
        return [
            ...app(\App\Services\EvidenceWorkflow::class)->details($certification),
            'id' => $certification->id,
            'type' => 'certification',
            'studentName' => $certification->user->name,
            'studentMatricNo' => $certification->user->matric_no,
            'title' => $certification->title,
            'submittedAt' => ($certification->submitted_at ?? $certification->created_at)->toISOString(),
            'status' => $certification->status,
            'evidenceFiles' => $certification->evidenceFiles->map(fn (EvidenceFile $file) => $this->fileData($file))->values(),
            'reviewerName' => $certification->reviewer?->name,
            'reviewedAt' => $certification->reviewed_at?->toISOString(),
            'adminRemarks' => $certification->admin_remarks,
            'details' => [
                'id' => $certification->id,
                'title' => $certification->title,
                'issuingOrganization' => $certification->issuing_organization,
                'issueDate' => $certification->issue_date->toDateString(),
                'expiryDate' => $certification->expiry_date?->toDateString(),
                'expired' => $certification->expiry_date?->lt(today()) ?? false,
                'referenceNumber' => $certification->credential_id,
                'evidenceFiles' => $certification->evidenceFiles->map(fn (EvidenceFile $file) => $this->fileData($file))->values(),
                'status' => $certification->status,
                'linkedSkills' => $certification->skills->pluck('id')->values(),
                'createdAt' => $certification->created_at->toISOString(),
                'updatedAt' => $certification->updated_at->toISOString(),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function skillData(Skill $skill): array
    {
        return [
            ...app(\App\Services\EvidenceWorkflow::class)->details($skill),
            'id' => $skill->id,
            'type' => 'skill',
            'studentName' => $skill->user->name,
            'studentMatricNo' => $skill->user->matric_no,
            'title' => $skill->name,
            'submittedAt' => $skill->created_at->toISOString(),
            'status' => $skill->status,
            'evidenceFiles' => [],
            'supportingEvidence' => $skill->projects->map(fn (Project $project) => [
                'type' => 'project',
                'id' => $project->id,
                'title' => $project->title,
                'status' => $project->status,
                'projectUrl' => $project->project_url ?: $project->repository_url,
                'files' => $project->evidenceFiles->map(fn (EvidenceFile $file) => $this->fileData($file))->values(),
            ])->concat($skill->certifications->map(fn (Certification $certification) => [
                'type' => 'certification',
                'id' => $certification->id,
                'title' => $certification->title,
                'status' => $certification->status,
                'expired' => $certification->expiry_date?->lt(today()) ?? false,
                'projectUrl' => null,
                'files' => $certification->evidenceFiles->map(fn (EvidenceFile $file) => $this->fileData($file))->values(),
            ]))->values(),
            'supportingAssessments' => app(\App\Services\AssessmentEvidence::class)->best($skill->user)->filter(fn ($a) => collect(app(\App\Services\AssessmentEvidence::class)->skills($a))->contains(fn ($name) => app(\App\Services\VerifiedEvidence::class)->normalise($name) === app(\App\Services\VerifiedEvidence::class)->normalise($skill->name)))->map(fn ($a) => ['title' => $a->assessment_snapshot['title'] ?? $a->assessment->title, 'percentage' => (float) $a->percentage, 'completedAt' => $a->submitted_at->toISOString()])->values(),
            'reviewerName' => $skill->reviewer?->name,
            'reviewedAt' => $skill->reviewed_at?->toISOString(),
            'adminRemarks' => $skill->admin_remarks,
            'details' => [
                'id' => $skill->id,
                'name' => $skill->name,
                'category' => $skill->category,
                'proficiency' => $skill->proficiency,
                'description' => $skill->description,
                'status' => $skill->status,
                'createdAt' => $skill->created_at->toISOString(),
                'updatedAt' => $skill->updated_at->toISOString(),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function fileData(EvidenceFile $file): array
    {
        return [
            'id' => $file->id,
            'name' => $file->original_name,
            'mimeType' => $file->mime_type,
            'size' => $file->size,
        ];
    }
}
