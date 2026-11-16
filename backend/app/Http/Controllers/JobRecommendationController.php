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
            'minimum_salary' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'maximum_salary' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99', ...($request->filled('minimum_salary') ? ['gte:minimum_salary'] : [])],
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

        return response()->json($run ? $this->runData($run) : ['runId' => null, 'modelName' => null, 'matches' => [], 'catalogue'=>app(\App\Services\JobCatalogue::class)->summary()]);
    }

    public function generate(Request $request, JobRecommendationService $service): JsonResponse
    {
        $this->ensureStudent($request);
        set_time_limit(150);
        $validated = $request->validate(['query' => ['nullable', 'string', 'max:1000']]);
        $run = $service->generate($request->user(), $validated['query'] ?? null);

        return response()->json($this->runData($run), 201);
    }

    public function feedback(Request $request, \App\Models\JobListing $listing): JsonResponse
    {
        $this->ensureStudent($request);
        abort_unless($request->user()->jobRecommendationRuns()->whereHas('matches', fn ($q) => $q->where('job_listing_id', $listing->id))->exists(), 404);
        $validated = $request->validate(['rating' => ['required', 'in:relevant,not_relevant'], 'reason' => ['nullable', 'string', 'max:500']]);
        \Illuminate\Support\Facades\DB::table('job_feedback')->updateOrInsert(['user_id' => $request->user()->id, 'job_listing_id' => $listing->id], ['reason' => null, ...$validated, 'updated_at' => now(), 'created_at' => now()]);
        return response()->json(['message' => 'Thanks. Your feedback has been saved.']);
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
        $evidence = app(\App\Services\VerifiedEvidence::class)->terms($run->user);
        foreach ($run->matches as $match) {
            if (!$match->listing) continue;
            $fit = app(\App\Services\JobFitScorer::class)->score($match->listing, $run->user, $evidence);
            $match->match_score = $fit['score'];
            $match->matched_skills = $fit['matched'];
            $match->missing_skills = $fit['missing'];
            $match->explanation = $fit['explanation'];
            $match->score_breakdown = $fit['breakdown'];
        }
        $context = app(\App\Services\JobSearchContext::class);
        $catalogue = app(\App\Services\JobCatalogue::class);
        $uniqueIds = $catalogue->unique($run->matches->pluck('listing')->filter())->pluck('id');
        $run->setRelation('matches', $run->matches->filter(fn ($match) => $match->listing && $uniqueIds->contains($match->job_listing_id) && $context->visible($match->listing, $run->user))
            ->sort(fn($a,$b)=>($b->match_score<=>$a->match_score) ?: ($catalogue->localPriority($b->listing)<=>$catalogue->localPriority($a->listing)))->values());
        $insights = ($run->profile_snapshot['fingerprint'] ?? '') === $context->fingerprint($run->user) ? $run->ai_insights : null;
        if ($insights) $insights['picks'] = collect($insights['picks'] ?? [])->filter(fn ($pick) => $run->matches->contains('job_listing_id', $pick['jobId']))->values()->all();
        $feedback = \Illuminate\Support\Facades\DB::table('job_feedback')->where('user_id', $run->user_id)->whereIn('job_listing_id', $run->matches->pluck('job_listing_id'))->get()->keyBy('job_listing_id');
        foreach ($run->matches as $index => $match) $match->rank = $index + 1;
        return [
            'runId' => $run->id,
            'generatedAt' => $run->created_at->toISOString(),
            'scoredAt' => now()->toISOString(),
            'modelName' => $run->model_name,
            'catalogue' => $catalogue->summary(),
            'aiInsights' => $insights,
            'matches' => $run->matches->map(fn ($match) => [
                'id' => $match->id,
                'rank' => $match->rank,
                'matchScore' => (float) $match->match_score,
                'scoreBreakdown' => $match->score_breakdown,
                'matchedSkills' => $match->matched_skills ?? [],
                'missingSkills' => $match->missing_skills ?? [],
                'explanation' => $match->explanation,
                'feedback' => $feedback->get($match->job_listing_id)?->rating,
                'feedbackReason' => $feedback->get($match->job_listing_id)?->reason,
                'job' => [
                    'id' => $match->listing->id,
                    'title' => $match->listing->title,
                    'company' => $match->listing->company,
                    'location' => $match->listing->location,
                    'workType' => $match->listing->work_type,
                    'salaryMin' => $match->listing->salary_min,
                    'salaryMax' => $match->listing->salary_max,
                    'salaryCurrency' => $match->listing->salary_currency,
                    'description' => \Illuminate\Support\Str::limit($match->listing->description,1500),
                    'descriptionScope' => data_get($match->listing->raw_payload,'description_scope','full_listing'),
                    'source' => $match->listing->source,
                    'applicationUrl' => $match->listing->application_url,
                    'publishedAt' => $match->listing->published_at?->toISOString(),
                    'fetchedAt' => $match->listing->fetched_at?->toISOString(),
                ],
            ])->values(),
        ];
    }
}
