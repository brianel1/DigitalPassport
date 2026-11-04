<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_verify_a_pending_project(): void
    {
        $student = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin', 'matric_no' => null]);
        $project = $student->projects()->create([
            'title' => 'Portfolio System',
            'description' => 'A verified student portfolio.',
            'technologies' => ['Laravel', 'Vue'],
            'completion_date' => '2026-10-01',
        ]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/reviews')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->putJson("/api/admin/reviews/project/{$project->id}", [
            'decision' => 'verified',
            'remarks' => 'Evidence and project details confirmed.',
        ])->assertOk();

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => 'verified']);
        $this->assertDatabaseHas('verification_reviews', [
            'reviewable_id' => $project->id,
            'decision' => 'verified',
            'reviewer_id' => $admin->id,
        ]);
    }

    public function test_student_cannot_review_submissions(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/admin/reviews')->assertForbidden();
        $this->getJson('/api/admin/dashboard-stats')->assertForbidden();
    }

    public function test_dashboard_stats_include_pending_skills(): void
    {
        $student = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin', 'matric_no' => null]);
        $student->skills()->create([
            'name' => 'Laravel',
            'category' => 'web-development',
            'proficiency' => 'intermediate',
            'description' => 'Backend framework experience.',
        ]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/dashboard-stats')
            ->assertOk()
            ->assertJsonPath('pendingReviews', 1)
            ->assertJsonPath('totalStudents', 1);
    }
}
