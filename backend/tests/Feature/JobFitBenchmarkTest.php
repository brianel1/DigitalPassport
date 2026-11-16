<?php
namespace Tests\Feature;

use App\Models\{User, Assessment, AssessmentAttempt, JobListing};
use App\Services\{AssessmentEvidence, JobFitScorer};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class JobFitBenchmarkTest extends TestCase
{
    use RefreshDatabase;

    public static function profiles(): array
    {
        return [
            'empty profile'=>['none',['SQL'],0],
            'pending skill'=>['pending',['SQL'],0],
            'rejected skill'=>['rejected',['SQL'],0],
            'verified exact'=>['verified',['SQL'],100],
            'case insensitive'=>['verified',['sql'],100],
            'one of two requirements'=>['verified',['SQL','Docker'],50],
            'one of three requirements'=>['verified',['SQL','Docker','AWS'],33.33],
            'duplicates do not inflate'=>['verified',['SQL','sql'],100],
            'unknown requirements'=>['verified',[],0],
            'unrelated verified skill'=>['verified',['Python'],0],
            'not a substring'=>['verified',['MySQL'],0],
            'verified project'=>['project',['SQL'],100],
            'pending project'=>['project_pending',['SQL'],0],
            'current certificate'=>['certificate',['SQL'],100],
            'expired certificate'=>['expired',['SQL'],0],
            'certificate expires today'=>['today',['SQL'],100],
            'passed mapped assessment'=>['assessment',['SQL'],100],
            'failed assessment'=>['failed',['SQL'],0],
            'unmapped assessment'=>['unmapped',['SQL'],0],
            'duplicate evidence'=>['all',['SQL','Docker'],50],
        ];
    }

    #[DataProvider('profiles')]
    public function test_evidence_fit_benchmark(string $kind,array $required,float $expected): void
    {
        $user=User::factory()->create();
        if(in_array($kind,['verified','pending','rejected','all']))$user->skills()->create(['name'=>'SQL','category'=>'databases','proficiency'=>'intermediate','status'=>$kind==='all'?'verified':$kind]);
        if(in_array($kind,['project','project_pending','all']))$user->projects()->create(['title'=>'Database application','description'=>'Built SQL reports.','technologies'=>['SQL'],'completion_date'=>'2026-01-01','status'=>$kind==='project_pending'?'pending':'verified']);
        if(in_array($kind,['certificate','expired','today','all']))$user->certifications()->create(['title'=>'SQL Fundamentals','issuing_organization'=>'Benchmark training','issue_date'=>today()->subYear(),'expiry_date'=>match($kind){'expired'=>today()->subDay(),'today'=>today(),default=>today()->addYear()},'status'=>'verified']);
        if(in_array($kind,['assessment','failed','unmapped','all'])){
            $a=Assessment::create(['title'=>'Database basics','category'=>'databases','description'=>'SQL test','time_limit_minutes'=>10,'passing_score'=>60,'skill_names'=>$kind==='unmapped'?[]:['SQL']]);
            AssessmentAttempt::create(['assessment_id'=>$a->id,'user_id'=>$user->id,'score'=>1,'maximum_score'=>1,'percentage'=>$kind==='failed'?0:100,'passed'=>$kind!=='failed','started_at'=>now()->subMinutes(2),'submitted_at'=>now(),'assessment_snapshot'=>app(AssessmentEvidence::class)->snapshot($a)]);
        }
        $listing=new JobListing(['title'=>'Database developer','required_skills'=>$required]);
        $fit=app(JobFitScorer::class)->score($listing,$user);
        $this->assertEqualsWithDelta($expected,$fit['score'],0.01);
        $this->assertSame(count(array_unique(array_map('strtolower',$required))),$fit['breakdown']['requirementCount']);
    }

    public function test_search_is_explicit_and_ai_shortlist_expires_when_evidence_changes(): void
    {
        $user=User::factory()->create();Sanctum::actingAs($user);
        $base=['source'=>'remotive','location'=>'Worldwide','company'=>'Benchmark company','description'=>'A role for SQL skills','required_skills'=>['SQL'],'application_url'=>'https://remotive.com/test','fetched_at'=>now()];
        $job=JobListing::create([...$base,'title'=>'Database developer','external_id'=>'benchmark-sql']);
        JobListing::create([...$base,'title'=>'Designer','external_id'=>'benchmark-design','required_skills'=>['Figma']]);
        config()->set('services.ollama.model','test-model');
        Http::fake(['*11434*'=>Http::response(['response'=>json_encode(['matches'=>[['job_id'=>$job->id,'focus_skill'=>'SQL']]])])]);
        $this->postJson('/api/student/job-recommendations',['query'=>'SQL'])->assertCreated()->assertJsonCount(1,'matches')->assertJsonPath('aiInsights.picks.0.jobId',$job->id);
        $this->getJson('/api/student/job-recommendations')->assertOk()->assertJsonCount(1,'aiInsights.picks');
        $user->skills()->create(['name'=>'SQL','category'=>'databases','proficiency'=>'beginner','status'=>'verified']);
        $this->getJson('/api/student/job-recommendations')->assertOk()->assertJsonPath('aiInsights',null)->assertJsonPath('matches.0.matchScore',100);
        $this->putJson('/api/student/jobs/'.$job->id.'/feedback',['rating'=>'not_relevant','reason'=>'Requires more experience'])->assertOk();
        Sanctum::actingAs(User::factory()->create());
        $this->putJson('/api/student/jobs/'.$job->id.'/feedback',['rating'=>'relevant'])->assertNotFound();
    }
}
