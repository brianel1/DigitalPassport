<?php

namespace App\Services;

use App\Models\GeneratedCv;
use App\Models\JobListing;
use App\Models\User;
use Illuminate\Support\Str;

class CvGeneratorService
{
    public function __construct(private readonly OllamaClient $ollama)
    {
    }

    public function generate(User $user, ?JobListing $targetJob = null, ?string $title = null): GeneratedCv
    {
        $user->load(['profile', 'skills', 'projects', 'certifications', 'assessmentAttempts.assessment']);
        $skills = $user->skills->where('status', 'verified')->pluck('name')->values()->all();
        $wanted = collect($targetJob?->required_skills ?? [])->map(fn ($skill) => Str::lower($skill));
        if ($targetJob) {
            usort($skills, fn ($a, $b) => (int) $wanted->contains(Str::lower($b)) <=> (int) $wanted->contains(Str::lower($a)));
        }
        $projects = $user->projects->where('status', 'verified')->map(fn ($project) => [
            'title' => $project->title,
            'description' => $project->description,
            'technologies' => $project->technologies ?? [],
        ])->values()->all();
        if ($targetJob) {
            usort($projects, function ($a, $b) use ($wanted) {
                $relevance = fn ($project) => collect($project['technologies'])->filter(fn ($tech) => $wanted->contains(Str::lower($tech)))->count();
                return $relevance($b) <=> $relevance($a);
            });
        }
        $certifications = $user->certifications->where('status', 'verified')->filter(fn ($c) => !$c->expiry_date || !$c->expiry_date->lt(today()))->map(fn ($certification) => [
            'title' => $certification->title,
            'issuer' => $certification->issuing_organization,
            'issueDate' => $certification->issue_date->toDateString(),
        ])->values()->all();
        $assessments = app(AssessmentEvidence::class)->best($user)->map(fn ($attempt) => [
            'title' => $attempt->assessment?->title ?? 'Assessment',
            'percentage' => (float) $attempt->percentage,
            'completedAt' => $attempt->submitted_at?->toDateString() ?? '',
        ])->values()->all();

        $summary = $user->profile?->bio;
        if (! $summary) {
            $skillText = collect($skills)->take(4)->join(', ', ', and ');
            $summary = $skillText
                ? "{$user->programme} student with verified skills in {$skillText}."
                : "{$user->programme} student building a verified record of technical skills and project experience.";
        }


        $content = [
            'personal' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => \App\Support\PhoneNumber::display($user->profile?->phone),
                'location' => $user->profile?->location ?? '',
                'headline' => $user->profile?->headline ?? "{$user->programme} Student",
                'website' => $user->profile?->linkedin_url ?? $user->profile?->github_url ?? '',
            ],
            'summary' => $summary,
            'skills' => $skills,
            'education' => [[
                'institution' => 'Universiti Teknikal Malaysia Melaka',
                'qualification' => $user->programme,
                'period' => $user->profile?->expected_graduation_date
                    ? 'Expected graduation '.$user->profile->expected_graduation_date->format('Y')
                    : '',
            ]],
            'projects' => $projects,
            'certifications' => $certifications,
            'assessments' => $assessments,
            'experience' => [],
            'additional' => '',
        ];
        // AI returns optional suggestions. The original facts remain the editable draft
        // until the student explicitly applies a reviewed suggestion.
        $draft = new GeneratedCv(['content' => $content]);
        $draft->setRelation('user', $user)->setRelation('targetJob', $targetJob);
        $analysis = app(CvIntelligenceService::class)->review($draft);
        $modelName = $analysis['engine'];
        return \Illuminate\Support\Facades\DB::transaction(function () use ($user, $content, $targetJob, $title, $analysis, $modelName) {
            $user->newQuery()->whereKey($user->id)->lockForUpdate()->first();
            return $user->generatedCvs()->create([
                'title' => $title ?: Str::limit($targetJob ? "{$user->name} - {$targetJob->title}" : "{$user->name} CV", 160, ''),
                'target_job_listing_id' => $targetJob?->id, 'content' => $content,
                'version' => ((int) $user->generatedCvs()->max('version')) + 1, 'status' => 'draft',
                'model_name' => $modelName, 'analysis' => $analysis,
                'provenance' => [
                    'method' => 'verified-records-and-opt-in-review-v2', 'model' => $modelName,
                    'generated_at' => now()->toISOString(), 'source_content' => $content,
                    'skill_ids' => $user->skills->where('status', 'verified')->pluck('id')->all(),
                    'project_ids' => $user->projects->where('status', 'verified')->pluck('id')->all(),
                    'certificate_ids' => $user->certifications->where('status', 'verified')->filter(fn ($c) => !$c->expiry_date || !$c->expiry_date->lt(today()))->pluck('id')->all(),
                    'assessment_attempt_ids' => app(AssessmentEvidence::class)->best($user)->pluck('id')->all(),
                ],
            ]);
        });
    }
}
