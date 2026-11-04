<?php

namespace App\Services;

use App\Models\JobListing;
use App\Models\JobRecommendationRun;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JobRecommendationService
{
    public function __construct(private readonly OllamaClient $ollama)
    {
    }

    public function generate(User $user): JobRecommendationRun
    {
        $user->load(['skills', 'projects', 'certifications', 'assessmentAttempts.assessment', 'jobPreference']);
        $assessmentResults = $user->assessmentAttempts
            ->where('passed', true)
            ->map(fn ($attempt) => [
                'title' => $attempt->assessment?->title ?? 'Assessment',
                'percentage' => (float) $attempt->percentage,
            ])
            ->values();
        $verifiedSkills = $user->skills->where('status', 'verified')->pluck('name')->values();
        $otherSkills = $user->skills->where('status', '!=', 'verified')->pluck('name')->values();
        $projectTechnologies = $user->projects->where('status', 'verified')->flatMap(fn ($project) => $project->technologies ?? [])->unique()->values();
        $profileSkills = $verifiedSkills->merge($projectTechnologies)->unique(fn ($skill) => Str::lower($skill))->values();
        $preference = $user->jobPreference;

        $listings = JobListing::query()
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->get();

        $ranked = $listings->map(function (JobListing $listing) use ($profileSkills, $otherSkills, $preference) {
            return $this->score($listing, $profileSkills, $otherSkills, $preference);
        })->sortByDesc('score')->take(20)->values();
        $modelName = 'explainable-rules-v1';
        $aiRanked = $this->rankWithAi($user, $assessmentResults, $ranked);
        if ($aiRanked !== null) {
            $ranked = $aiRanked;
            $modelName = 'ollama:'.$this->ollama->modelName();
        }

        return DB::transaction(function () use ($user, $verifiedSkills, $otherSkills, $projectTechnologies, $assessmentResults, $preference, $ranked, $modelName) {
            $run = JobRecommendationRun::create([
                'user_id' => $user->id,
                'profile_snapshot' => [
                    'verified_skills' => $verifiedSkills,
                    'other_skills' => $otherSkills,
                    'project_technologies' => $projectTechnologies,
                    'assessment_results' => $assessmentResults,
                ],
                'preference_snapshot' => $preference?->toArray(),
                'model_name' => $modelName,
                'status' => 'completed',
            ]);

            foreach ($ranked as $index => $result) {
                $run->matches()->create([
                    'job_listing_id' => $result['listing']->id,
                    'match_score' => $result['score'],
                    'matched_skills' => $result['matched'],
                    'missing_skills' => $result['missing'],
                    'explanation' => $result['explanation'],
                    'rank' => $index + 1,
                ]);
            }

            return $run->load('matches.listing');
        });
    }

    /** @param Collection<int, array{listing: JobListing, score: float, matched: array<int, string>, missing: array<int, string>, explanation: string}> $ranked */
    private function rankWithAi(User $user, Collection $assessmentResults, Collection $ranked): ?Collection
    {
        if (! $this->ollama->isConfigured() || $ranked->isEmpty()) {
            return null;
        }

        $jobs = $ranked->map(fn (array $result) => [
            'job_id' => $result['listing']->id,
            'title' => $result['listing']->title,
            'company' => $result['listing']->company,
            'location' => $result['listing']->location,
            'work_type' => $result['listing']->work_type,
            'description' => $result['listing']->description,
            'required_skills' => $result['listing']->required_skills ?? [],
            'rule_score' => $result['score'],
        ])->values()->all();

        $prompt = 'You rank job listings for a university student. Use only the supplied profile and job data. Do not invent skills, qualifications, employers, salaries, or experience. Return JSON only with this exact shape: {"matches":[{"job_id":1,"explanation":"short reason"}]}. Include each supplied job_id once, ordered from best match to weakest match. Keep each explanation under 240 characters.'.
            "\n\nStudent profile:\n".json_encode([
                'name' => $user->name,
                'programme' => $user->programme,
                'verified_skills' => $user->skills->where('status', 'verified')->pluck('name')->values()->all(),
                'projects' => $user->projects->where('status', 'verified')->pluck('title')->values()->all(),
                'certifications' => $user->certifications->where('status', 'verified')->pluck('title')->values()->all(),
                'assessment_results' => $assessmentResults->all(),
                'preferences' => $user->jobPreference?->toArray(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).
            "\n\nJobs:\n".json_encode($jobs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $response = $this->ollama->generateJson($prompt);
        $matches = data_get($response, 'matches');
        if (! is_array($matches)) {
            return null;
        }

        $byId = $ranked->keyBy(fn (array $result) => (string) $result['listing']->id);
        $ordered = collect();
        $seen = [];
        foreach ($matches as $match) {
            if (! is_array($match) || ! isset($match['job_id'])) {
                continue;
            }
            $id = (string) $match['job_id'];
            if (isset($seen[$id]) || ! $byId->has($id)) {
                continue;
            }
            $result = $byId->get($id);
            if (is_string($match['explanation'] ?? null) && trim($match['explanation']) !== '') {
                $result['explanation'] = Str::limit(trim($match['explanation']), 240, '');
            }
            $ordered->push($result);
            $seen[$id] = true;
        }

        if ($ordered->isEmpty()) {
            return null;
        }

        return $ordered->concat($ranked->reject(
            fn (array $result) => isset($seen[(string) $result['listing']->id])
        ))->values();
    }

    /** @return array{listing: JobListing, score: float, matched: array<int, string>, missing: array<int, string>, explanation: string} */
    private function score(JobListing $listing, Collection $profileSkills, Collection $otherSkills, $preference): array
    {
        $requirements = collect($listing->required_skills ?? [])->values();
        $trusted = $profileSkills->map(fn ($skill) => Str::lower($skill));
        $unverified = $otherSkills->map(fn ($skill) => Str::lower($skill));
        $matched = $requirements->filter(fn ($skill) => $trusted->contains(Str::lower($skill)))->values();
        $partial = $requirements->filter(fn ($skill) => ! $trusted->contains(Str::lower($skill)) && $unverified->contains(Str::lower($skill)))->values();
        $missing = $requirements->diff($matched)->diff($partial)->values();

        $skillScore = $requirements->isEmpty()
            ? 35
            : (($matched->count() + ($partial->count() * 0.5)) / $requirements->count()) * 70;
        $preferenceScore = 0;

        $locations = collect($preference?->preferred_locations ?? [])->map(fn ($value) => Str::lower($value));
        if ($locations->isEmpty() || $locations->contains(fn ($value) => Str::contains(Str::lower($listing->location ?? ''), $value))) {
            $preferenceScore += 15;
        }
        $workTypes = collect($preference?->work_types ?? [])->map(fn ($value) => Str::lower($value));
        if ($workTypes->isEmpty() || $workTypes->contains(Str::lower($listing->work_type ?? ''))) {
            $preferenceScore += 10;
        }
        $fields = collect($preference?->preferred_fields ?? [])->map(fn ($value) => Str::lower($value));
        if ($fields->isEmpty() || $fields->contains(fn ($value) => Str::contains(Str::lower($listing->title), $value))) {
            $preferenceScore += 5;
        }

        $score = round(min(100, $skillScore + $preferenceScore), 2);
        $explanation = $matched->isNotEmpty()
            ? 'This role matches your '.Str::lower($matched->take(3)->join(', ', ', and ')).' skills.'
            : 'This role matches your selected career preferences. Review the missing skills before applying.';

        return [
            'listing' => $listing,
            'score' => $score,
            'matched' => $matched->all(),
            'missing' => $missing->all(),
            'explanation' => $explanation,
        ];
    }
}
