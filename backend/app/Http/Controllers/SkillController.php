<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SkillController extends Controller
{
    private const CATEGORIES = [
        'programming',
        'databases',
        'web-development',
        'cybersecurity',
        'data-analysis',
        'networking',
        'cloud-computing',
        'other',
    ];

    private const PROFICIENCY_LEVELS = ['beginner', 'intermediate', 'advanced', 'expert'];

    public function index(Request $request): JsonResponse
    {
        $this->ensureStudent($request);

        $skills = $request->user()->skills()->latest()->get()->map(fn (Skill $skill) => $this->skillData($skill));

        return response()->json(['data' => $skills]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureStudent($request);
        $validated = $this->validateSkill($request);
        $skill = $request->user()->skills()->create($validated);

        return response()->json($this->skillData($skill->refresh()), 201);
    }

    public function update(Request $request, Skill $skill): JsonResponse
    {
        $this->ensureOwner($request, $skill);
        $skill->update([
            ...$this->validateSkill($request, $skill),
            'status' => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
            'admin_remarks' => null,
        ]);

        return response()->json($this->skillData($skill->refresh()));
    }

    public function destroy(Request $request, Skill $skill): JsonResponse
    {
        $this->ensureOwner($request, $skill);
        $skill->delete();

        return response()->json(['message' => 'Skill deleted successfully.']);
    }

    /** @return array<string, mixed> */
    private function validateSkill(Request $request, ?Skill $skill = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('skills')->where('user_id', $request->user()->id)->ignore($skill?->id),
            ],
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'proficiency' => ['required', Rule::in(self::PROFICIENCY_LEVELS)],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function ensureStudent(Request $request): void
    {
        abort_unless($request->user()->role === 'student', 403, 'Student access is required.');
    }

    private function ensureOwner(Request $request, Skill $skill): void
    {
        $this->ensureStudent($request);
        abort_unless($skill->user_id === $request->user()->id, 404);
    }

    /** @return array<string, mixed> */
    private function skillData(Skill $skill): array
    {
        return [
            'id' => $skill->id,
            'name' => $skill->name,
            'category' => $skill->category,
            'proficiency' => $skill->proficiency,
            'description' => $skill->description ?? '',
            'status' => $skill->status,
            'linkedProjects' => [],
            'linkedCertifications' => [],
            'linkedAssessments' => [],
            'adminRemarks' => $skill->admin_remarks,
            'createdAt' => $skill->created_at->toISOString(),
            'updatedAt' => $skill->updated_at->toISOString(),
        ];
    }
}
