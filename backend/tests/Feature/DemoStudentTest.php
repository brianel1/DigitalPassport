<?php

namespace Tests\Feature;

use App\Models\JobListing;
use App\Models\User;
use App\Services\JobFitScorer;
use Database\Seeders\DemoStudentData;
use Database\Seeders\DemoStudentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DemoStudentTest extends TestCase
{
    use RefreshDatabase;
    public function test_complete_demo_is_idempotent_has_no_jobs_and_reaches_100_only_for_matching_requirements(): void
    {
        $this->seed(DemoStudentSeeder::class);
        $user=User::where('email',DemoStudentData::EMAIL)->firstOrFail();
        $this->assertTrue(Hash::check(DemoStudentData::PASSWORD,$user->password));
        $this->assertCount(16,$user->skills);
        $this->assertCount(3,$user->projects);
        $this->assertCount(2,$user->certifications);
        $this->assertCount(2,$user->assessmentAttempts);
        $this->assertCount(1,$user->generatedCvs);
        $this->assertDatabaseCount('assessment_answers',20);
        $this->assertDatabaseCount('job_listings',0);
        $this->assertDatabaseCount('job_matches',0);
        $this->assertDatabaseCount('job_recommendation_runs',0);
        $listing=new JobListing(['title'=>'Junior Laravel Developer','company'=>'Scoring test only','location'=>'Kuala Lumpur','work_type'=>'full-time','salary_currency'=>'MYR','salary_min'=>3500,'salary_max'=>5000,'required_skills'=>['Laravel','PHP','Vue','SQL','Git']]);
        $scorer=app(JobFitScorer::class);
        $this->assertSame(100.0,$scorer->score($listing,$user)['score']);
        $listing->required_skills=['Laravel','PHP','Vue','SQL','Git','Kubernetes'];
        $this->assertSame(83.33,$scorer->score($listing,$user)['score']);
        $listing->location='London';
        $this->assertLessThan(83.33,$scorer->score($listing,$user)['score']);
        $user->update(['name'=>'My edited demo name']);
        $this->seed(DemoStudentSeeder::class);
        $this->assertSame('My edited demo name',$user->fresh()->name);
        $this->assertDatabaseCount('users',1);
        $this->assertDatabaseCount('skills',16);
    }

    public function test_salary_accepts_two_decimals_and_rejects_extra_precision_or_invalid_ranges(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $body=['preferred_fields'=>['developer'],'preferred_locations'=>[],'work_types'=>[],'accepts_remote'=>true,'minimum_salary'=>3000.25,'maximum_salary'=>5000.75];
        $this->putJson('/api/student/job-preferences',$body)->assertOk()->assertJsonPath('minimumSalary',3000.25);
        $this->putJson('/api/student/job-preferences',[...$body,'minimum_salary'=>3000.256])->assertUnprocessable();
        $this->putJson('/api/student/job-preferences',[...$body,'maximum_salary'=>2000])->assertUnprocessable();
    }
}
