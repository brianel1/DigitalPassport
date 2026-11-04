<?php

namespace App\Services\JobProviders;

use App\Contracts\JobProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class JoobleJobProvider implements JobProvider
{
    use ExtractsJobSkills;

    public function source(): string
    {
        return 'jooble';
    }

    public function isConfigured(): bool
    {
        return filled(config('services.jooble.key'));
    }

    public function fetch(string $keywords, string $location): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $response = Http::acceptJson()
            ->timeout(15)
            ->retry(2, 250)
            ->post(rtrim(config('services.jooble.url'), '/').'/'.config('services.jooble.key'), [
                'keywords' => $keywords,
                'location' => $location,
                'page' => 1,
                'ResultOnPage' => 50,
            ])
            ->throw()
            ->json('jobs', []);

        return collect($response)->map(function (array $job) {
            $description = trim(strip_tags($job['snippet'] ?? ''));

            return [
                'external_id' => (string) ($job['id'] ?? sha1(($job['link'] ?? '').($job['title'] ?? ''))),
                'title' => $job['title'] ?? 'Job opportunity',
                'company' => $job['company'] ?? 'Company not listed',
                'location' => $job['location'] ?? null,
                'work_type' => Str::lower($job['type'] ?? '') ?: null,
                'description' => $description ?: 'Open the job listing to view the full description.',
                'required_skills' => $this->extractSkills(($job['title'] ?? '').' '.$description),
                'application_url' => $job['link'],
                'published_at' => $job['updated'] ?? null,
                'raw_payload' => $job,
            ];
        })->filter(fn ($job) => filled($job['application_url']))->values()->all();
    }
}
