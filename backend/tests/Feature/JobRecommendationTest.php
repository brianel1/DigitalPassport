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
        $this->job('job-2', 'Frontend Developer', 'Ayer Keroh, Melaka', ['JavaScript', 'Vue.js', 'CSS']);
        $this->job('job-3', 'Backend Developer', 'Kuala Lumpur', ['PHP', 'Laravel', 'MySQL']);
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

    public function test_navi_shortlist_keeps_model_order_while_list_keeps_fit_order(): void
    {
        config()->set('services.ollama.model', 'qwen3:4b');
        $student = User::factory()->create();
        $student->skills()->createMany([
            ['name' => 'Laravel', 'category' => 'web-development', 'proficiency' => 'intermediate', 'status' => 'verified'],
            ['name' => 'Vue', 'category' => 'web-development', 'proficiency' => 'intermediate', 'status' => 'verified'],
        ]);
        $strong = $this->job('ai-strong', 'Junior Full Stack Developer', 'Melaka', ['Laravel', 'Vue']);
        $weaker = $this->job('ai-weaker', 'Backend Intern', 'Melaka', ['Laravel', 'PHP']);
        Http::fake(['*127.0.0.1:11434/api/generate*' => Http::response(['response' => json_encode(['matches' => [
            ['job_id' => $weaker->id, 'focus_skill' => 'Laravel'],
            ['job_id' => $strong->id, 'focus_skill' => 'Vue'],
        ]])])]);
        Sanctum::actingAs($student);

        $this->postJson('/api/student/job-recommendations')->assertCreated()
            ->assertJsonPath('matches.0.job.id', $strong->id)
            ->assertJsonPath('aiInsights.picks.0.jobId', $weaker->id)
            ->assertJsonPath('aiInsights.picks.1.jobId', $strong->id);
        Http::assertSent(fn ($request) => data_get($request->data(), 'format.required') === ['matches']);
    }

    public function test_ai_output_with_wrong_shape_is_logged_as_failed_and_falls_back(): void
    {
        config()->set('services.ollama.model', 'qwen3:4b');
        Http::fake(['*127.0.0.1:11434/api/generate*' => Http::response(['response' => json_encode(['programme' => 'echoed profile'])])]);
        $student = User::factory()->create();
        $student->skills()->create(['name' => 'Laravel', 'category' => 'web-development', 'proficiency' => 'intermediate', 'status' => 'verified']);
        $this->job('ai-shape', 'Backend Developer', 'Melaka', ['Laravel']);
        Sanctum::actingAs($student);

        $this->postJson('/api/student/job-recommendations')->assertCreated()
            ->assertJsonPath('modelName', 'evidence-fit-v4')
            ->assertJsonPath('aiInsights', null);
        $this->assertDatabaseHas('system_events', ['kind' => 'ai', 'status' => 'failed']);
        $this->assertDatabaseMissing('system_events', ['kind' => 'ai', 'status' => 'completed']);
    }

    public function test_senior_titles_and_long_experience_requirements_reduce_student_fit(): void
    {
        $student = User::factory()->create();
        $student->skills()->create(['name' => 'Laravel', 'category' => 'web-development', 'proficiency' => 'intermediate', 'status' => 'verified']);
        $scorer = app(\App\Services\JobFitScorer::class);
        $score = fn (string $title, string $description = 'Entry level role.') => $scorer->score(
            tap($this->job(uniqid(), $title, 'Melaka', ['Laravel']), fn ($job) => $job->update(['description' => $description])), $student);

        $this->assertEquals(100, $score('Junior Backend Developer')['score']);
        $this->assertEquals(100, $score('Admin Staff')['score']);
        $this->assertEquals(60, $score('Senior Backend Developer')['score']);
        $this->assertEquals(50, $score('Principal Software Developer')['score']);
        $senior = $score('Backend Developer', 'Requires 5+ years of Laravel experience.');
        $this->assertEquals(60, $senior['score']);
        $this->assertStringContainsString('5+ years', $senior['breakdown']['levelNote']);
        $this->assertEquals(80, $score('Backend Developer', 'At least 3 years experience.')['score']);
    }

    public function test_location_filters_by_malaysian_state_and_remote_follows_its_switch(): void
    {
        config()->set('services.ollama.model', null);
        $student = User::factory()->create();
        Sanctum::actingAs($student);
        $pj = $this->job('loc-pj', 'Developer', 'Petaling Jaya, Selangor', ['PHP']);
        $jb = $this->job('loc-jb', 'Developer', 'Johor Bahru, 01, MY, Kuala Lumpur', ['PHP']);
        $this->job('loc-remote', 'Developer', 'Worldwide', ['PHP']);

        $this->getJson('/api/student/job-preferences')->assertOk()->assertJsonPath('acceptsRemote', false)->assertJsonPath('locationOptions.0', 'Johor');
        $this->postJson('/api/student/job-recommendations')->assertCreated()->assertJsonCount(2, 'matches');

        $this->putJson('/api/student/job-preferences', ['preferred_locations' => ['PJ', 'Selangor', 'Johor'], 'accepts_remote' => false])
            ->assertOk()->assertJsonPath('preferredLocations', ['Selangor', 'Johor']);
        $this->putJson('/api/student/job-preferences', ['preferred_locations' => ['Selangor'], 'accepts_remote' => false])->assertOk();
        $this->postJson('/api/student/job-recommendations')->assertCreated()->assertJsonCount(1, 'matches')
            ->assertJsonPath('matches.0.job.id', $pj->id)->assertJsonPath('matches.0.job.locationLabel', 'Petaling Jaya, Selangor');

        $this->putJson('/api/student/job-preferences', ['preferred_locations' => ['Johor'], 'accepts_remote' => true])->assertOk();
        $response = $this->postJson('/api/student/job-recommendations')->assertCreated()->assertJsonCount(2, 'matches');
        $this->assertEqualsCanonicalizing([$jb->id, 'Remote'], [collect($response->json('matches'))->firstWhere('job.remote', false)['job']['id'], collect($response->json('matches'))->firstWhere('job.remote', true)['job']['locationLabel']]);
    }

    public function test_role_search_uses_common_synonyms_and_state_pages_are_public_urls(): void
    {
        $catalogue = app(\App\Services\JobCatalogue::class);
        $this->assertTrue($catalogue->matchesQuery($this->job('syn-web', 'Web Developer', 'Melaka', []), 'software developer'));
        $this->assertTrue($catalogue->matchesQuery($this->job('syn-php', 'PHP Programmer', 'Melaka', []), 'software developer'));
        $this->assertTrue($catalogue->matchesQuery($this->job('syn-li', 'Latihan Industri IT', 'Melaka', []), 'internship'));
        $this->assertFalse($catalogue->matchesQuery($this->job('syn-mech', 'Mechanical Engineer', 'Melaka', []), 'software developer'));

        $url = new \ReflectionMethod(\App\Services\JobProviders\PublicHtmlJobProvider::class, 'url');
        $url->setAccessible(true);
        $this->assertSame('https://www.ricebowl.my/jobsearch/jobs-in-melaka?page=2', $url->invoke(new \App\Services\JobProviders\PublicHtmlJobProvider('ricebowl'), '', 2, 'melaka'));
        $this->assertSame('https://www.ricebowl.my/jobsearch/software-jobs-in-perak?page=1', $url->invoke(new \App\Services\JobProviders\PublicHtmlJobProvider('ricebowl'), 'software', 1, 'perak'));
        $this->assertSame('https://www.jobstore.com/my/jobs/l-johor.html?page=1', $url->invoke(new \App\Services\JobProviders\PublicHtmlJobProvider('jobstore'), '', 1, 'johor'));
    }

    public function test_salary_ceiling_can_be_set_without_a_minimum_and_remote_filter_updates_immediately(): void
    {
        config()->set('services.ollama.model', null);
        $student = User::factory()->create();
        $student->jobPreference()->create(['accepts_remote' => true]);
        Sanctum::actingAs($student);
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
