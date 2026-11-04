<?php

namespace App\Services;

use App\Models\GeneratedCv;
use App\Models\User;

class CvGeneratorService
{
    public function __construct(private readonly OllamaClient $ollama)
    {
    }

    public function generate(User $user): GeneratedCv
    {
        $user->load(['profile', 'skills', 'projects', 'certifications', 'assessmentAttempts.assessment']);
        $skills = $user->skills->where('status', 'verified')->pluck('name')->values()->all();
        $projects = $user->projects->where('status', 'verified')->map(fn ($project) => [
            'title' => $project->title,
            'description' => $project->description,
            'technologies' => $project->technologies ?? [],
        ])->values()->all();
        $certifications = $user->certifications->where('status', 'verified')->map(fn ($certification) => [
            'title' => $certification->title,
            'issuer' => $certification->issuing_organization,
            'issueDate' => $certification->issue_date->toDateString(),
        ])->values()->all();
        $assessments = $user->assessmentAttempts->where('passed', true)->map(fn ($attempt) => [
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

        $version = ((int) $user->generatedCvs()->max('version')) + 1;

        $content = [
            'personal' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->profile?->phone ?? '',
                'location' => $user->profile?->location ?? '',
                'headline' => $user->profile?->headline ?? "{$user->programme} Student",
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
        ];
        [$content, $modelName] = $this->enhanceWithAi($user, $content);

        return $user->generatedCvs()->create([
            'title' => "{$user->name} CV",
            'content' => $content,
            'version' => $version,
            'status' => 'draft',
            'model_name' => $modelName,
        ]);
    }

    /** @param array<string, mixed> $content @return array{0: array<string, mixed>, 1: string} */
    private function enhanceWithAi(User $user, array $content): array
    {
        if (! $this->ollama->isConfigured()) {
            return [$content, 'trusted-profile-template-v1'];
        }

        $prompt = 'Prepare concise ATS CV wording from the supplied verified student profile. Use only the supplied facts and do not invent employers, dates, qualifications, metrics, or technologies. Return JSON only with this shape: {"summary":"...","project_descriptions":["..."]}. Keep the summary under 500 characters. Keep one project description for each supplied project and keep each under 500 characters.'.
            "\n\nVerified profile:\n".json_encode([
                'programme' => $user->programme,
                'skills' => $content['skills'],
                'projects' => $content['projects'],
                'certifications' => $content['certifications'],
                'assessments' => $content['assessments'],
                'current_summary' => $content['summary'],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $response = $this->ollama->generateJson($prompt);
        $summary = data_get($response, 'summary');
        $descriptions = data_get($response, 'project_descriptions');

        if (is_string($summary) && trim($summary) !== '') {
            $content['summary'] = trim($summary);
        }

        if (is_array($descriptions) && count($descriptions) === count($content['projects'])) {
            foreach ($content['projects'] as $index => $project) {
                if (is_string($descriptions[$index] ?? null) && trim($descriptions[$index]) !== '') {
                    $content['projects'][$index]['description'] = trim($descriptions[$index]);
                }
            }
        }

        $modelName = ($response !== null) ? 'ollama:'.$this->ollama->modelName() : 'trusted-profile-template-v1';

        return [$content, $modelName];
    }
}
