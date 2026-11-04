<?php

namespace App\Services;

use App\Contracts\JobProvider;
use App\Models\JobListing;
use App\Services\JobProviders\JoobleJobProvider;
use App\Services\JobProviders\RemotiveJobProvider;
use Throwable;

class JobSyncService
{
    /** @return array<string, array{saved: int, error: string|null}> */
    public function sync(string $keywords = 'software developer', string $location = 'Malaysia'): array
    {
        $results = [];
        foreach ($this->providers() as $provider) {
            if (! $provider->isConfigured()) {
                $results[$provider->source()] = ['saved' => 0, 'error' => 'Provider is not configured.'];
                continue;
            }

            try {
                $saved = 0;
                foreach ($provider->fetch($keywords, $location) as $job) {
                    JobListing::updateOrCreate(
                        ['source' => $provider->source(), 'external_id' => $job['external_id']],
                        [...$job, 'fetched_at' => now()],
                    );
                    $saved++;
                }
                $results[$provider->source()] = ['saved' => $saved, 'error' => null];
            } catch (Throwable $exception) {
                report($exception);
                $results[$provider->source()] = ['saved' => 0, 'error' => 'The provider could not be reached.'];
            }
        }

        return $results;
    }

    /** @return array<int, JobProvider> */
    private function providers(): array
    {
        return [app(JoobleJobProvider::class), app(RemotiveJobProvider::class)];
    }
}
