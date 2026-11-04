<?php

namespace Tests\Feature;

use App\Services\JobSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class JobProviderSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_configured_providers_normalize_and_cache_job_listings(): void
    {
        config(['services.jooble.key' => 'test-key']);
        Http::fake([
            'my.jooble.org/api/test-key' => Http::response([
                'jobs' => [[
                    'id' => 101,
                    'title' => 'Junior PHP Developer',
                    'company' => 'Example Employer',
                    'location' => 'Melaka',
                    'snippet' => 'Build Laravel and MySQL applications.',
                    'type' => 'Full-time',
                    'link' => 'https://my.jooble.org/job/101',
                    'updated' => '2026-11-01T08:00:00Z',
                ]],
            ]),
            'remotive.com/api/remote-jobs*' => Http::response([
                'jobs' => [[
                    'id' => 202,
                    'title' => 'Remote Vue Developer',
                    'company_name' => 'Remote Employer',
                    'candidate_required_location' => 'Worldwide',
                    'job_type' => 'full_time',
                    'description' => '<p>Build Vue.js and TypeScript interfaces.</p>',
                    'url' => 'https://remotive.com/remote-jobs/202',
                    'publication_date' => '2026-11-01T08:00:00Z',
                ]],
            ]),
        ]);

        $results = app(JobSyncService::class)->sync('developer', 'Malaysia');

        $this->assertSame(1, $results['jooble']['saved']);
        $this->assertSame(1, $results['remotive']['saved']);
        $this->assertDatabaseHas('job_listings', ['source' => 'jooble', 'external_id' => '101']);
        $this->assertDatabaseHas('job_listings', ['source' => 'remotive', 'external_id' => '202']);
    }
}
