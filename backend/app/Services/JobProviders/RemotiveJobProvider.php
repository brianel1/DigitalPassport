<?php

namespace App\Services\JobProviders;

use App\Contracts\JobProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RemotiveJobProvider implements JobProvider
{
    use ExtractsJobSkills;

    public function source(): string
    {
        return 'remotive';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function fetch(string $keywords, string $location): array
    {
        $jobs = Http::acceptJson()
            ->timeout(15)
            ->retry(2, 250)
            ->get(config('services.remotive.url'), ['search' => $keywords])
            ->throw()
            ->json('jobs', []);

        return collect($jobs)->take(50)->map(function (array $job) {
            $description = trim(preg_replace('/\s+/', ' ', strip_tags($job['description'] ?? '')));

            return [
                'external_id' => (string) $job['id'],
                'title' => $job['title'],
                'company' => $job['company_name'] ?? 'Company not listed',
                'location' => $job['candidate_required_location'] ?? 'Remote',
                'work_type' => Str::lower($job['job_type'] ?? 'remote'),
                'description' => Str::limit($description, 4000),
                'required_skills' => $this->extractSkills(($job['title'] ?? '').' '.$description),
                'application_url' => $job['url'],
                'published_at' => $job['publication_date'] ?? null,
                'raw_payload' => $job,
            ];
        })->filter(fn ($job) => filled($job['application_url']))->values()->all();
    }
}
