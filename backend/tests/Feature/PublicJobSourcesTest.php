<?php

namespace Tests\Feature;

use App\Models\{JobListing,User};
use App\Services\{JobCatalogue,JobFitScorer,JobSyncService};
use App\Services\JobProviders\{PublicJobParser,PublicJobPageClient};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Cache,Http};
use Tests\TestCase;

class PublicJobSourcesTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void { parent::setUp(); Http::preventStrayRequests(); Cache::flush(); }

    private function card(string $id='example-1',string $location='Melaka'): string
    {
        $locations=htmlspecialchars(json_encode([['label'=>$location]]),ENT_QUOTES);
        return '<mkrb-job-card job-id="'.$id.'" href="/job/'.$id.'-developer" job-title="PHP Developer" company="Fixture Employer" locations="'.$locations.'" salary="MYR3,000 - MYR4,000 Per Month" posted-at="1794500000000" highlight="Build Laravel applications."></mkrb-job-card>';
    }

    public function test_public_card_parse_is_safe_and_never_scores_incomplete_summaries(): void
    {
        $job=app(PublicJobParser::class)->parse('ricebowl',$this->card())[0];
        $this->assertSame(3000.0,$job['salary_min']);
        $this->assertSame('MY',$job['raw_payload']['country']);
        $this->assertFalse($job['raw_payload']['requirements_complete']);
        $this->assertSame([],$job['required_skills']);
        $job['required_skills']=['PHP'];
        $user=User::factory()->create();$user->skills()->create(['name'=>'PHP','category'=>'programming','proficiency'=>'intermediate','status'=>'verified']);
        $score=app(JobFitScorer::class)->score(new JobListing($job),$user);
        $this->assertFalse($score['breakdown']['assessable']);$this->assertSame(0.0,$score['score']);
    }

    public function test_public_json_ld_enriches_requirements_and_expiry(): void
    {
        $parser=app(PublicJobParser::class);$job=$parser->parse('ricebowl',$this->card())[0];
        $data=['@type'=>'JobPosting','description'=>'Develop Laravel and MySQL systems.','datePosted'=>'2026-11-01','validThrough'=>'2026-12-01','jobLocation'=>['address'=>['addressCountry'=>'MY']]];
        $result=$parser->detail('<script type="application/ld+json">'.json_encode($data).'</script>',$job);
        $this->assertTrue($result['raw_payload']['requirements_complete']);
        $this->assertContains('Laravel',$result['required_skills']);$this->assertNotNull($result['expires_at']);
    }

    public function test_hiredly_reads_published_skill_tags_and_skips_inactive_jobs(): void
    {
        $row=['id'=>'1','title'=>'PHP Developer','company'=>['name'=>'Fixture Employer'],'location'=>'Melaka','stateRegion'=>'Melaka','active'=>true,'slug'=>'fixture-developer','salary'=>'3000 - 4000','skills'=>[['name'=>'PHP']], 'gptSummary'=>'A source summary.'];
        $data=['props'=>['pageProps'=>['jobs'=>[$row,[...$row,'id'=>'2','active'=>false]]]]];
        $jobs=app(PublicJobParser::class)->parse('hiredly','<script id="__NEXT_DATA__" type="application/json">'.json_encode($data).'</script>');
        $this->assertCount(1,$jobs);$this->assertSame(['PHP'],$jobs[0]['required_skills']);
        $this->assertSame('source_ai_summary',$jobs[0]['raw_payload']['description_scope']);
    }

    public function test_jobstore_estimated_salaries_are_not_presented_as_offered_pay(): void
    {
        $row=['title'=>'PHP Developer','company'=>'Fixture Employer','area'=>'Melaka, Malaysia','country'=>'MY','description'=>'Build Laravel and MySQL applications. Integrate a C++ library.','salary_min'=>'5,000','salary_max'=>'7,000','salary_period'=>'per month','salary_currency'=>'MYR','is_est_salary'=>true];
        $html='<a data-id="l1" href="/my/job/l1/developer"><div data-full="'.htmlspecialchars(json_encode($row),ENT_QUOTES).'"></div></a>';
        $job=app(PublicJobParser::class)->parse('jobstore',$html)[0];
        $this->assertNull($job['salary_min']);$this->assertNull($job['salary_max']);
        $this->assertContains('Laravel',$job['required_skills']);
        $this->assertContains('C++',$job['required_skills']);
    }

    public function test_shared_ricebowl_maukerja_vacancy_is_shown_once_and_full_description_wins(): void
    {
        $a=new JobListing(['id'=>1,'source'=>'ricebowl','title'=>'Developer','company'=>'Company','raw_payload'=>['shared_listing_id'=>'ajobthing:123','requirements_complete'=>false]]);
        $b=new JobListing(['id'=>2,'source'=>'maukerja','title'=>'Developer','company'=>'Company','raw_payload'=>['shared_listing_id'=>'ajobthing:123','requirements_complete'=>true]]);
        $unique=app(JobCatalogue::class)->unique(collect([$a,$b]));
        $this->assertCount(1,$unique);$this->assertSame('maukerja',$unique->first()->source);
    }

    public function test_malaysian_and_global_roles_are_allowed_but_country_restricted_remote_is_not(): void
    {
        $catalogue=app(JobCatalogue::class);
        $this->assertTrue($catalogue->eligibleArray(['location'=>'Worldwide'],'remotive'));
        $this->assertTrue($catalogue->eligibleArray(['location'=>'Malaysia'],'hiredly'));
        $this->assertFalse($catalogue->eligibleArray(['location'=>'United States'],'remotive'));
        $this->assertFalse($catalogue->eligibleArray(['location'=>'Singapore','raw_payload'=>['country'=>'SG']],'ricebowl'));
        $this->assertFalse($catalogue->eligibleArray(['location'=>null],'remotive'));
    }

    public function test_query_matches_role_aliases_and_whole_skills(): void
    {
        $job=new JobListing(['title'=>'Software Engineer','required_skills'=>['JavaScript','SQL']]);$catalogue=app(JobCatalogue::class);
        $this->assertTrue($catalogue->matchesQuery($job,'software developer'));
        $this->assertTrue($catalogue->matchesQuery($job,'SQL, Python'));
        $this->assertFalse($catalogue->matchesQuery($job,'Java'));
    }

    public function test_blocked_jobstreet_is_reported_and_other_public_sources_continue(): void
    {
        config(['services.public_jobs.sources'=>['jobstreet','ricebowl'],'services.public_jobs.pages'=>1,'services.public_jobs.detail_limit'=>0]);
        Http::fake(['my.jobstreet.com/*'=>Http::response('Forbidden',403),'www.ricebowl.my/*'=>Http::response($this->card(),200),'remotive.com/*'=>Http::response(['jobs'=>[]])]);
        $result=app(JobSyncService::class)->sync('developer');
        $this->assertSame(0,$result['jobstreet']['saved']);$this->assertSame(1,$result['ricebowl']['saved']);
        $this->assertDatabaseHas('job_sync_runs',['source'=>'jobstreet','status'=>'blocked']);
        Http::assertSentCount(3);
    }

    public function test_failure_on_later_page_preserves_earlier_results(): void
    {
        config(['services.public_jobs.sources'=>['ricebowl'],'services.public_jobs.pages'=>2,'services.public_jobs.detail_limit'=>0]);
        Http::fake(['www.ricebowl.my/*page=1'=>Http::response($this->card(),200),'www.ricebowl.my/*page=2'=>Http::response('Unavailable',503)]);
        $result=app(JobSyncService::class)->sync('developer','Malaysia','ricebowl');
        $this->assertSame(1,$result['ricebowl']['saved']);$this->assertNotNull($result['ricebowl']['error']);
        $this->assertDatabaseHas('job_sync_runs',['source'=>'ricebowl','status'=>'partial']);
    }

    public function test_restricted_detail_page_stops_source_requests_but_keeps_fetched_summaries(): void
    {
        config(['services.public_jobs.sources'=>['ricebowl'],'services.public_jobs.pages'=>2,'services.public_jobs.detail_limit'=>24]);
        Http::fake([
            'www.ricebowl.my/jobsearch/*'=>Http::response($this->card('one').$this->card('two'),200),
            'www.ricebowl.my/job/*'=>Http::response('Restricted',403),
        ]);
        $result=app(JobSyncService::class)->sync('developer','Malaysia','ricebowl');
        $this->assertSame(2,$result['ricebowl']['saved']);
        $this->assertStringContainsString('restricted',$result['ricebowl']['error']);
        $this->assertDatabaseHas('job_sync_runs',['source'=>'ricebowl','status'=>'partial']);
        Http::assertSentCount(2);
    }

    public function test_public_page_client_rejects_arbitrary_hosts_without_network_access(): void
    {
        $this->expectException(\RuntimeException::class);
        app(PublicJobPageClient::class)->get('http://127.0.0.1/private');
    }
}
