<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'programme' => ['nullable', 'string', 'max:100'],
        ]);

        $query = User::query()
            ->where('role', 'student')
            ->withCount(['skills', 'projects', 'certifications', 'assessmentAttempts'])
            ->latest();

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('matric_no', 'like', "%{$search}%");
            });
        }

        if (!empty($validated['status'])) {
            $query->where('is_active', $validated['status'] === 'active');
        }

        if (!empty($validated['programme'])) {
            $query->where('programme', $validated['programme']);
        }

        $users = $query->get()->map(fn (User $user) => $this->summary($user));

        return response()->json([
            'data' => $users,
            'meta' => [
                'total' => $users->count(),
                'programmes' => User::where('role', 'student')
                    ->whereNotNull('programme')
                    ->distinct()
                    ->orderBy('programme')
                    ->pluck('programme')
                    ->values(),
            ],
        ]);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $this->ensureAdmin($request);
        abort_unless($user->role === 'student', 404);

        $user->load([
            'profile',
            'skills' => fn ($query) => $query->latest(),
            'projects' => fn ($query) => $query->latest(),
            'certifications' => fn ($query) => $query->latest(),
            'assessmentAttempts.assessment',
        ]);

        return response()->json([
            ...$this->summary($user),
            'profile' => $user->profile,
            'skills' => $user->skills->map(fn ($skill) => [
                'id' => $skill->id,
                'name' => $skill->name,
                'category' => $skill->category,
                'proficiency' => $skill->proficiency,
                'status' => $skill->status,
            ])->values(),
            'projects' => $user->projects->map(fn ($project) => [
                'id' => $project->id,
                'title' => $project->title,
                'status' => $project->status,
                'createdAt' => $project->created_at?->toISOString(),
            ])->values(),
            'certifications' => $user->certifications->map(fn ($certification) => [
                'id' => $certification->id,
                'title' => $certification->title,
                'status' => $certification->status,
                'createdAt' => $certification->created_at?->toISOString(),
            ])->values(),
            'assessmentAttempts' => $user->assessmentAttempts->map(fn ($attempt) => [
                'id' => $attempt->id,
                'assessmentTitle' => $attempt->assessment?->title,
                'percentage' => $attempt->percentage,
                'passed' => $attempt->passed,
                'submittedAt' => $attempt->submitted_at?->toISOString(),
            ])->values(),
        ]);
    }

    public function updateStatus(Request $request, User $user): JsonResponse
    {
        $this->ensureAdmin($request);
        abort_unless($user->role === 'student', 404);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $user->update(['is_active' => $validated['is_active']]);

        if (!$validated['is_active']) {
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => $user->is_active ? 'Student account activated.' : 'Student account suspended.',
            'user' => $this->summary($user->refresh()),
        ]);
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()->role === 'admin', 403, 'Administrator access is required.');
    }

    /** @return array<string, mixed> */
    private function summary(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'programme' => $user->programme,
            'matricNo' => $user->matric_no,
            'isActive' => (bool) $user->is_active,
            'createdAt' => $user->created_at?->toISOString(),
            'skillsCount' => (int) ($user->skills_count ?? $user->skills()->count()),
            'projectsCount' => (int) ($user->projects_count ?? $user->projects()->count()),
            'certificationsCount' => (int) ($user->certifications_count ?? $user->certifications()->count()),
            'assessmentAttemptsCount' => (int) ($user->assessment_attempts_count ?? $user->assessmentAttempts()->count()),
        ];
    }
}
