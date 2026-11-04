<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $this->ensureStudent($request);

        return response()->json($this->profileData($request));
    }

    public function update(Request $request): JsonResponse
    {
        $this->ensureStudent($request);
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'programme' => ['required', 'string', 'max:100'],
            'matric_no' => ['required', 'string', 'max:40', Rule::unique('users')->ignore($user->id)],
            'headline' => ['nullable', 'string', 'max:160'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:40'],
            'location' => ['nullable', 'string', 'max:120'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'github_url' => ['nullable', 'url', 'max:255'],
            'expected_graduation_date' => ['nullable', 'date'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'programme' => $validated['programme'],
            'matric_no' => strtoupper($validated['matric_no']),
        ]);

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            collect($validated)->except(['name', 'programme', 'matric_no'])->all(),
        );

        return response()->json($this->profileData($request));
    }

    private function ensureStudent(Request $request): void
    {
        abort_unless($request->user()->role === 'student', 403, 'Student access is required.');
    }

    /** @return array<string, mixed> */
    private function profileData(Request $request): array
    {
        $user = $request->user()->load('profile');

        return [
            ...AuthController::userData($user),
            'headline' => $user->profile?->headline,
            'bio' => $user->profile?->bio,
            'phone' => $user->profile?->phone,
            'location' => $user->profile?->location,
            'linkedinUrl' => $user->profile?->linkedin_url,
            'githubUrl' => $user->profile?->github_url,
            'expectedGraduationDate' => $user->profile?->expected_graduation_date?->toDateString(),
        ];
    }
}
