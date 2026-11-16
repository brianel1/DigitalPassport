<?php

namespace Tests\Feature;

use App\Models\JobListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class JobRecommendationTest extends TestCase
{
    use RefreshDatabase;

    public function test_jobs_are_ranked_using_verified_skills_and_preferences(): void
    {
        $student = User::factory()->create();
        $student->skills()->createMany([
            ['name' => 'PHP', 'category' => 'programming', 'proficiency' => 'advanced', 'status' => 'verified'],
            ['name' => 'Laravel', 'category' => 'web-development', 'proficiency' => 'intermediate', 'status' => 'verified'],
            ['name' => 'MySQL', 'category' => 'databases', 'proficiency' => 'intermediate', 'status' => 'verified'],
        ]);
        $student->jobPreference()->create([
            'preferred_fields' => ['backend'],
            'preferred_locations' => ['Melaka'],
            'work_types' => ['full-time'],
            'accepts_remote' => true,
        ]);

        $strong = $this->job('job-1', 'Junior Backend Developer', 'Melaka', ['PHP', 'Laravel', 'MySQL']);
        $this->job('job-2', 'Frontend Developer', 'Kuala Lumpur', ['JavaScript', 'Vue.js', 'CSS']);
        Sanctum::actingAs($student);

        $response = $this->postJson('/api/student/job-recommendations')
            ->assertCreated()
            ->assertJsonCount(2, 'matches')
            ->assertJsonPath('matches.0.job.id', $strong->id)
            ->assertJsonPath('matches.0.matchedSkills.0', 'PHP');

        $this->assertGreaterThan(
            $response->json('matches.1.matchScore'),
            $response->json('matches.0.matchScore'),
        );
        $this->assertDatabaseCount('job_recommendation_runs', 1);
        $this->assertDatabaseCount('job_matches', 2);
    }

    public function test_student_can_save_job_preferences(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/student/job-preferences', [
            'preferred_fields' => ['backend', 'full stack'],
            'preferred_locations' => ['Melaka'],
            'work_types' => ['full-time'],
            'accepts_remote' => true,
            'minimum_salary' => 2500,
            'maximum_salary' => 4500,
        ])->assertOk()
            ->assertJsonPath('preferredFields.0', 'backend')
            ->assertJsonPath('minimumSalary', 2500);
    }

    public function test_ollama_ranking_does_not_publish_ungrounded_explanations(): void
    {
        config()->set('services.ollama.model', 'qwen3:8b');
        Http::fake([
            '*127.0.0.1:11434/api/generate*' => Http::response([
                'response' => json_encode([
                    'matches' => [
                        ['job_id' => 1, 'explanation' => 'Your verified Laravel and PHP skills match this backend role.'],
                    ],
                ]),
            ]),
        ]);

        $student = User::factory()->create();
        $student->skills()->create([
            'name' => 'Laravel',
            'category' => 'web-development',
            'proficiency' => 'intermediate',
            'status' => 'verified',
        ]);
        $job = $this->job('job-ai', 'Backend Developer', 'Melaka', ['Laravel']);
        Sanctum::actingAs($student);

        $this->postJson('/api/student/job-recommendations')
            ->assertCreated()
            ->assertJsonPath('matches.0.job.id', $job->id)
            ->assertJsonPath('matches.0.explanation', 'This role matches your laravel skills.')
            ->assertJsonPath('modelName', 'ollama:qwen3:8b');

        $this->assertDatabaseHas('job_recommendation_runs', [
            'user_id' => $student->id,
            'model_name' => 'ollama:qwen3:8b',
        ]);
    }

    public function test_salary_ceiling_can_be_set_without_a_minimum_and_remote_filter_updates_immediately(): void
    {
        config()->set('services.ollama.model', null);
        Sanctum::actingAs(User::factory()->create());
        $this->job('remote-filter', 'Developer', 'Worldwide', ['PHP']);
        $this->postJson('/api/student/job-recommendations')->assertCreated()->assertJsonCount(1, 'matches');
        $this->putJson('/api/student/job-preferences', ['minimum_salary' => null, 'maximum_salary' => 5000, 'accepts_remote' => false])->assertOk();
        $this->getJson('/api/student/job-recommendations')->assertOk()->assertJsonCount(0, 'matches');
    }

    public function test_score_has_no_free_points_and_updates_when_evidence_changes(): void
    {
        config()->set('services.ollama.model',null);
        $student=User::factory()->create();
        $skill=$student->skills()->create(['name'=>'JavaScript','category'=>'programming','proficiency'=>'beginner','status'=>'pending']);
        $this->job('score-case','Frontend Developer','Melaka',['JS','Laravel']);
        Sanctum::actingAs($student);
        $this->postJson('/api/student/job-recommendations')->assertCreated()->assertJsonPath('matches.0.matchScore',0)
            ->assertJsonPath('matches.0.scoreBreakdown.matchedCount',0);
        $skill->update(['status'=>'verified']);
        $this->getJson('/api/student/job-recommendations')->assertOk()->assertJsonPath('matches.0.matchScore',50)
            ->assertJsonPath('matches.0.scoreBreakdown.matchedCount',1);
        $skill->update(['status'=>'rejected']);
        $this->getJson('/api/student/job-recommendations')->assertOk()->assertJsonPath('matches.0.matchScore',0);
    }

    public function test_project_evidence_preferences_and_unknown_requirements_are_explained(): void
    {
        $student=User::factory()->create();
        $student->projects()->create(['title'=>'Booking API','description'=>'Implemented endpoints','technologies'=>['Laravel'],'completion_date'=>'2026-10-01','status'=>'verified']);
        $student->jobPreference()->create(['preferred_locations'=>['Melaka'],'work_types'=>['internship'],'minimum_salary'=>3000,'accepts_remote'=>true]);
        $job=$this->job('pref-case','Backend Developer','Melaka',['Laravel','PHP']);
        $fit=app(\App\Services\JobFitScorer::class)->score($job,$student);
        $this->assertEquals(50,$fit['breakdown']['skillCoverage']);
        $this->assertFalse($fit['breakdown']['salaryCompared']);
        $this->assertCount(2,$fit['breakdown']['preferences']);
        $this->assertEqualsWithDelta(46.15,$fit['score'],.01);
        $unknown=app(\App\Services\JobFitScorer::class)->score($this->job('unknown','Research role','Melaka',[]),$student);
        $this->assertFalse($unknown['breakdown']['assessable']);
        $this->assertEquals(0,$unknown['score']);
    }

    public function test_skill_extraction_does_not_match_substrings(): void
    {
        $extractor=new class { use \App\Services\JobProviders\ExtractsJobSkills; public function read(string $text):array{return $this->extractSkills($text);} };
        $skills=$extractor->read('JavaScript, MySQL and GitHub.');
        $this->assertContains('JavaScript',$skills);
        $this->assertContains('MySQL',$skills);
        $this->assertNotContains('Java',$skills);
        $this->assertNotContains('SQL',$skills);
        $this->assertNotContains('Git',$skills);
    }

    private function job(string $externalId, string $title, string $location, array $skills): JobListing
    {
        return JobListing::create([
            'source' => 'jooble',
            'external_id' => $externalId,
            'title' => $title,
            'company' => 'Technology Solutions Sdn. Bhd.',
            'location' => $location,
            'work_type' => 'full-time',
            'description' => 'Entry level role.',
            'required_skills' => $skills,
            'application_url' => 'https://my.jooble.org/jobs',
            'fetched_at' => now(),
        ]);
    }
}
