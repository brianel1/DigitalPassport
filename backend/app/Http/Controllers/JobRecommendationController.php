<?php

namespace App\Http\Controllers;

use App\Models\JobRecommendationRun;
use App\Services\JobRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobRecommendationController extends Controller
{
    public function preferences(Request $request): JsonResponse
    {
        $this->ensureStudent($request);
        return response()->json($this->preferenceData($request->user()->jobPreference));
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $this->ensureStudent($request);
        $validated = $request->validate([
            'preferred_fields' => ['nullable', 'array', 'max:10'],
            'preferred_fields.*' => ['string', 'max:100'],
            'preferred_locations' => ['nullable', 'array', 'max:10'],
            'preferred_locations.*' => ['string', 'max:100'],
            'work_types' => ['nullable', 'array', 'max:5'],
            'work_types.*' => ['string', 'max:50'],
            'accepts_remote' => ['required', 'boolean'],
            'minimum_salary' => ['nullable', 'integer', 'min:0'],
            'maximum_salary' => ['nullable', 'integer', 'gte:minimum_salary'],
        ]);

        $preference = $request->user()->jobPreference()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated,
        );

        return response()->json($this->preferenceData($preference));
    }

    public function latest(Request $request): JsonResponse
    {
        $this->ensureStudent($request);
        $run = JobRecommendationRun::query()
            ->where('user_id', $request->user()->id)
            ->with('matches.listing')
            ->latest()
            ->first();

        return response()->json($run ? $this->runData($run) : ['runId' => null, 'matches' => []]);
    }

    public function generate(Request $request, JobRecommendationService $service): JsonResponse
    {
        $this->ensureStudent($request);
        $run = $service->generate($request->user());

        return response()->json($this->runData($run), 201);
    }

    private function ensureStudent(Request $request): void
    {
        abort_unless($request->user()->role === 'student', 403, 'Student access is required.');
    }

    /** @return array<string, mixed> */
    private function preferenceData($preference): array
    {
        return [
            'preferredFields' => $preference?->preferred_fields ?? [],
            'preferredLocations' => $preference?->preferred_locations ?? [],
            'workTypes' => $preference?->work_types ?? [],
            'acceptsRemote' => $preference?->accepts_remote ?? true,
            'minimumSalary' => $preference?->minimum_salary,
            'maximumSalary' => $preference?->maximum_salary,
        ];
    }

    /** @return array<string, mixed> */
    private function runData(JobRecommendationRun $run): array
    {
        return [
            'runId' => $run->id,
            'generatedAt' => $run->created_at->toISOString(),
            'matches' => $run->matches->map(fn ($match) => [
                'id' => $match->id,
                'rank' => $match->rank,
                'matchScore' => (float) $match->match_score,
                'matchedSkills' => $match->matched_skills ?? [],
                'missingSkills' => $match->missing_skills ?? [],
                'explanation' => $match->explanation,
                'job' => [
                    'id' => $match->listing->id,
                    'title' => $match->listing->title,
                    'company' => $match->listing->company,
                    'location' => $match->listing->location,
                    'workType' => $match->listing->work_type,
                    'salaryMin' => $match->listing->salary_min,
                    'salaryMax' => $match->listing->salary_max,
                    'salaryCurrency' => $match->listing->salary_currency,
                    'description' => $match->listing->description,
                    'source' => $match->listing->source,
                    'applicationUrl' => $match->listing->application_url,
                    'publishedAt' => $match->listing->published_at?->toISOString(),
                ],
            ])->values(),
        ];
    }
}
