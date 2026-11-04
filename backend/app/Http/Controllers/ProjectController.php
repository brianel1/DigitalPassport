<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureStudent($request);
        $projects = $request->user()->projects()->with('evidenceFiles')->latest()->get();

        return response()->json(['data' => $projects->map(fn (Project $project) => $this->projectData($project))]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureStudent($request);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:4000'],
            'technologies' => ['required', 'array', 'min:1'],
            'technologies.*' => ['string', 'max:60'],
            'project_url' => ['nullable', 'url', 'max:255'],
            'completion_date' => ['required', 'date'],
            'evidence' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $project = $request->user()->projects()->create(collect($validated)->except('evidence')->all());

        if ($request->hasFile('evidence')) {
            $file = $request->file('evidence');
            $project->evidenceFiles()->create([
                'user_id' => $request->user()->id,
                'disk' => 'local',
                'path' => $file->store("evidence/projects/{$project->id}"),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'size' => $file->getSize(),
            ]);
        }

        return response()->json($this->projectData($project->refresh()->load('evidenceFiles')), 201);
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        $this->ensureOwner($request, $project);
        foreach ($project->evidenceFiles as $evidence) {
            Storage::disk($evidence->disk)->delete($evidence->path);
            $evidence->delete();
        }
        $project->delete();

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
            'id' => $project->id,
            'title' => $project->title,
            'description' => $project->description,
            'technologies' => $project->technologies ?? [],
            'projectUrl' => $project->project_url,
            'evidenceFiles' => $project->evidenceFiles->pluck('original_name')->values(),
            'completionDate' => $project->completion_date?->toDateString(),
            'status' => $project->status,
            'linkedSkills' => [],
            'adminRemarks' => $project->admin_remarks,
            'createdAt' => $project->created_at->toISOString(),
            'updatedAt' => $project->updated_at->toISOString(),
        ];
    }
}
