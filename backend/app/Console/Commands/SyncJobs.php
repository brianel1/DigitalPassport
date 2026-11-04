<?php

namespace App\Console\Commands;

use App\Services\JobSyncService;
use Illuminate\Console\Command;

class SyncJobs extends Command
{
    protected $signature = 'jobs:sync {--keywords=software developer} {--location=Malaysia}';

    protected $description = 'Fetch and cache listings from configured job providers';

    public function handle(JobSyncService $service): int
    {
        $results = $service->sync($this->option('keywords'), $this->option('location'));
        foreach ($results as $provider => $result) {
            $message = $result['error'] ?? "{$result['saved']} listing(s) saved.";
            $result['error'] ? $this->warn("{$provider}: {$message}") : $this->info("{$provider}: {$message}");
        }

        return self::SUCCESS;
    }
}
