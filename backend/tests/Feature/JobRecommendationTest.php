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

    public function test_ollama_can_refine_recommendation_explanations_when_configured(): void
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
            ->assertJsonPath('matches.0.explanation', 'Your verified Laravel and PHP skills match this backend role.');

        $this->assertDatabaseHas('job_recommendation_runs', [
            'user_id' => $student->id,
            'model_name' => 'ollama:qwen3:8b',
        ]);
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
