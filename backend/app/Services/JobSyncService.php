<?php

namespace App\Services;

use App\Contracts\JobProvider;
use App\Models\JobListing;
use App\Services\JobProviders\JoobleJobProvider;
use App\Services\JobProviders\RemotiveJobProvider;
use App\Services\JobProviders\PublicHtmlJobProvider;
use Throwable;
use Illuminate\Support\Facades\DB;

class JobSyncService
{
    /** @return array<string, array{saved: int, error: string|null}> */
    public function sync(string $keywords = '', string $location = 'Malaysia', ?string $onlySource = null): array
    {
        $results = [];
        foreach ($this->providers() as $provider) {
            if ($onlySource && $provider->source() !== $onlySource) continue;
            $run = DB::table('job_sync_runs')->insertGetId(['source'=>$provider->source(),'status'=>'running','started_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
            if (! $provider->isConfigured()) {
                $results[$provider->source()] = ['saved' => 0, 'error' => 'Provider is not configured.'];
                DB::table('job_sync_runs')->where('id',$run)->update(['status'=>'not_configured','message'=>'Provider key is not configured.','completed_at'=>now(),'updated_at'=>now()]);
                continue;
            }

            $saved = 0;
            try {
                foreach ($provider->fetch($keywords, $location) as $job) {
                    if (!filter_var($job['application_url'],FILTER_VALIDATE_URL) || !in_array(parse_url($job['application_url'],PHP_URL_SCHEME),['http','https'],true)) continue;
                    if (!app(JobCatalogue::class)->eligibleArray($job,$provider->source())) continue;
                    JobListing::updateOrCreate(
                        ['source' => $provider->source(), 'external_id' => $job['external_id']],
                        [...$job, 'fetched_at' => now()],
                    );
                    $saved++;
                }
                $warnings = method_exists($provider,'warnings') ? $provider->warnings() : [];
                $message = $warnings ? mb_substr(implode(' ', $warnings),0,500) : null;
                $results[$provider->source()] = ['saved' => $saved, 'error' => $message];
                DB::table('job_sync_runs')->where('id',$run)->update(['status'=>$warnings?'partial':'completed','message'=>$message,'saved_count'=>$saved,'completed_at'=>now(),'updated_at'=>now()]);
            } catch (Throwable $exception) {
                report($exception);
                $message = $provider instanceof PublicHtmlJobProvider ? mb_substr($exception->getMessage(),0,500) : 'The provider could not be reached.';
                $status = str_contains($message,'restricted') || str_contains($message,'browser verification') ? 'blocked' : ($saved ? 'partial' : 'failed');
                $results[$provider->source()] = ['saved' => $saved, 'error' => $message];
                DB::table('job_sync_runs')->where('id',$run)->update(['status'=>$status,'message'=>$message,'saved_count'=>$saved,'completed_at'=>now(),'updated_at'=>now()]);
            }
        }

        return $results;
    }

    /** @return array<int, JobProvider> */
    public function providers(): array
    {
        return [...array_map(fn ($name)=>new PublicHtmlJobProvider($name),config('services.public_jobs.sources',[])), app(RemotiveJobProvider::class), app(JoobleJobProvider::class)];
    }
}
