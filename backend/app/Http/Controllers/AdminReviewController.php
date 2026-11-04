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

class AdminReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'verified', 'rejected', 'all'])],
            'type' => ['nullable', Rule::in(['project', 'certification', 'skill', 'all'])],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $status = $validated['status'] ?? 'pending';
        $type = $validated['type'] ?? 'all';
        $search = strtolower(trim($validated['search'] ?? ''));

        $projects = Project::query()
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->with(['user', 'reviewer', 'evidenceFiles'])
            ->get()
            ->map(fn (Project $project) => $this->projectData($project));

        $certifications = Certification::query()
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->with(['user', 'reviewer', 'evidenceFiles'])
            ->get()
            ->map(fn (Certification $certification) => $this->certificationData($certification));

        $skills = Skill::query()
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->with(['user', 'reviewer'])
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

        return response()->json([
            'data' => $items->sortByDesc('submittedAt')->values(),
        ]);
    }

    public function evidence(Request $request, string $type, int $id, EvidenceFile $file)
    {
        $this->ensureAdmin($request);

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
        $this->ensureAdmin($request);
        $validated = $request->validate([
            'decision' => ['required', Rule::in(['verified', 'rejected'])],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $record = $this->findRecord($type, $id);

        DB::transaction(function () use ($request, $record, $validated) {
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

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()->role === 'admin', 403, 'Administrator access is required.');
    }

    /** @return array<string, mixed> */
    private function projectData(Project $project): array
    {
        return [
            'id' => $project->id,
            'type' => 'project',
            'studentName' => $project->user->name,
            'studentMatricNo' => $project->user->matric_no,
            'title' => $project->title,
            'submittedAt' => $project->created_at->toISOString(),
            'status' => $project->status,
            'evidenceFiles' => $project->evidenceFiles->map(fn (EvidenceFile $file) => $this->fileData($file))->values(),
            'reviewerName' => $project->reviewer?->name,
            'reviewedAt' => $project->reviewed_at?->toISOString(),
            'adminRemarks' => $project->admin_remarks,
            'details' => [
                'id' => $project->id,
                'title' => $project->title,
                'description' => $project->description,
                'technologies' => $project->technologies ?? [],
                'projectUrl' => $project->project_url,
                'evidenceFiles' => $project->evidenceFiles->map(fn (EvidenceFile $file) => $this->fileData($file))->values(),
                'completionDate' => $project->completion_date?->toDateString(),
                'status' => $project->status,
                'linkedSkills' => [],
                'createdAt' => $project->created_at->toISOString(),
                'updatedAt' => $project->updated_at->toISOString(),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function certificationData(Certification $certification): array
    {
        return [
            'id' => $certification->id,
            'type' => 'certification',
            'studentName' => $certification->user->name,
            'studentMatricNo' => $certification->user->matric_no,
            'title' => $certification->title,
            'submittedAt' => $certification->created_at->toISOString(),
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
                'referenceNumber' => $certification->credential_id,
                'evidenceFiles' => $certification->evidenceFiles->map(fn (EvidenceFile $file) => $this->fileData($file))->values(),
                'status' => $certification->status,
                'linkedSkills' => [],
                'createdAt' => $certification->created_at->toISOString(),
                'updatedAt' => $certification->updated_at->toISOString(),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function skillData(Skill $skill): array
    {
        return [
            'id' => $skill->id,
            'type' => 'skill',
            'studentName' => $skill->user->name,
            'studentMatricNo' => $skill->user->matric_no,
            'title' => $skill->name,
            'submittedAt' => $skill->created_at->toISOString(),
            'status' => $skill->status,
            'evidenceFiles' => [],
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
