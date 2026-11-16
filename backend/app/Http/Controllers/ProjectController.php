<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureStudent($request);
        $projects = $request->user()->projects()->with(['evidenceFiles', 'skills', 'reviewer'])->latest()->get();

        return response()->json(['data' => $projects->map(fn (Project $project) => $this->projectData($project))]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureStudent($request);
        return $this->saveRecord($request, new Project(), 201);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $this->ensureOwner($request, $project);
        return $this->saveRecord($request, $project, 200);
    }

    private function saveRecord(Request $request, Project $record, int $status): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['nullable', 'string', 'max:255'],
            'repository_url' => ['nullable', 'url', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:4000'],
            'technologies' => ['required', 'array', 'min:1'],
            'technologies.*' => ['string', 'max:60'],
            'project_url' => ['nullable', 'url', 'max:255'],
            'completion_date' => ['required', 'date'],
            'evidence' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'skill_ids' => ['sometimes', 'array'],
            'skill_ids.*' => ['integer', 'distinct', Rule::exists('skills', 'id')->where('user_id', $request->user()->id)],
        ]);
        $record = app(\App\Services\EvidenceWorkflow::class)->save($request, $record, $validated);
        return response()->json($this->projectData($record), $status);
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        $this->ensureOwner($request, $project);
        foreach ($project->evidenceFiles as $evidence) {
            Storage::disk($evidence->disk)->delete($evidence->path);
            $evidence->delete();
        }
        $linked = $project->skills()->pluck('skills.id')->all();
        $project->delete();
        app(\App\Services\EvidenceWorkflow::class)->invalidateUnsupportedSkills($linked);

        return response()->json(['message' => 'Project deleted successfully.']);
    }

    private function ensureStudent(Request $request): void
    {
        abort_unless($request->user()->role === 'student', 403, 'Student access is required.');
    }

    private function ensureOwner(Request $request, Project $project): void
    {
        $this->ensureStudent($request);
        abort_unless($project->user_id === $request->user()->id, 404);
    }

    /** @return array<string, mixed> */
    private function projectData(Project $project): array
    {
        return [
            ...app(\App\Services\EvidenceWorkflow::class)->details($project),
            'id' => $project->id,
            'title' => $project->title,
            'description' => $project->description,
            'role' => $project->role,
            'repositoryUrl' => $project->repository_url,
            'technologies' => $project->technologies ?? [],
            'projectUrl' => $project->project_url,
            'evidenceFiles' => $project->evidenceFiles->pluck('original_name')->values(),
            'completionDate' => $project->completion_date?->toDateString(),
            'status' => $project->status,
            'linkedSkills' => $project->skills->pluck('id')->values(),
            'adminRemarks' => $project->admin_remarks,
            'createdAt' => $project->created_at->toISOString(),
            'updatedAt' => $project->updated_at->toISOString(),
        ];
    }
}
