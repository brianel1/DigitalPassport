<?php

namespace App\Console\Commands;

use App\Services\JobSyncService;
use Illuminate\Console\Command;

class SyncJobs extends Command
{
    protected $signature = 'jobs:sync {--keywords= : Comma-separated searches; defaults to several career fields} {--location=Malaysia} {--source= : Refresh one source only}';

    protected $description = 'Fetch and cache listings from configured job providers';

    public function handle(JobSyncService $service): int
    {
        $results = $service->sync((string)$this->option('keywords'), $this->option('location'), $this->option('source'));
        foreach ($results as $provider => $result) {
            $message = "{$result['saved']} listing(s) saved. ".($result['error'] ?? '');
            $result['error'] ? $this->warn("{$provider}: {$message}") : $this->info("{$provider}: {$message}");
        }

        return self::SUCCESS;
    }
}
