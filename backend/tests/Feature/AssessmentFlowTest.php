<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AssessmentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_starts_and_submits_assessment_with_server_scoring(): void
    {
        $student = User::factory()->create();
        $assessment = $this->assessment();
        Sanctum::actingAs($student);

        $start = $this->postJson("/api/student/assessments/{$assessment->id}/attempts")
            ->assertCreated()
            ->assertJsonMissingPath('assessment.questions.0.correctAnswer');

        $attemptId = $start->json('attemptId');
        $questions = $assessment->questions;

        $this->postJson("/api/student/assessment-attempts/{$attemptId}/submit", [
            'answers' => [
                ['question_id' => $questions[0]->id, 'selected_option' => 1],
                ['question_id' => $questions[1]->id, 'selected_option' => 2],
            ],
        ])->assertOk()
            ->assertJsonPath('score', 1)
            ->assertJsonPath('percentage', 50)
            ->assertJsonPath('passed', false);

        $this->assertDatabaseHas('assessment_attempts', [
            'id' => $attemptId,
            'score' => 1,
            'passed' => false,
        ]);
    }

    public function test_only_published_assessments_are_visible_to_students(): void
    {
        $student = User::factory()->create();
        $this->assessment();
        $draft = $this->assessment(['title' => 'Draft Assessment', 'is_published' => false]);
        Sanctum::actingAs($student);

        $this->getJson('/api/student/assessments')
            ->assertOk()
            ->assertJsonCount(1, 'assessments');

        $this->postJson("/api/student/assessments/{$draft->id}/attempts")->assertNotFound();
    }

    public function test_non_admin_cannot_manage_assessments(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/admin/assessments')->assertForbidden();
    }

    public function test_admin_can_manage_questions_and_publish_an_assessment(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'matric_no' => null]);
        $assessment = $this->assessment(['title' => 'Draft Assessment', 'is_published' => false]);
        $assessment->questions()->delete();
        Sanctum::actingAs($admin);

        $question = $this->postJson("/api/admin/assessments/{$assessment->id}/questions", [
            'question' => 'Which command starts the Laravel development server?',
            'options' => ['php artisan serve', 'npm run dev'],
            'correct_option' => 0,
            'points' => 2,
        ])->assertCreated()
            ->assertJsonPath('correctOption', 0)
            ->assertJsonPath('points', 2);

        $questionId = $question->json('id');

        $this->putJson("/api/admin/assessments/{$assessment->id}/questions/{$questionId}", [
            'question' => 'Which command runs the Laravel development server?',
            'options' => ['php artisan serve', 'composer run dev'],
            'correct_option' => 0,
            'points' => 1,
        ])->assertOk();

        $this->postJson("/api/admin/assessments/{$assessment->id}/toggle-publish")
            ->assertOk()
            ->assertJsonPath('isPublished', true);

        $this->deleteJson("/api/admin/assessments/{$assessment->id}/questions/{$questionId}")
            ->assertOk();
    }

    public function test_admin_cannot_publish_an_assessment_without_questions(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'matric_no' => null]);
        $assessment = Assessment::create([
            'title' => 'Empty Assessment',
            'category' => 'programming',
            'description' => 'An assessment without questions.',
            'time_limit_minutes' => 10,
            'passing_score' => 60,
            'is_published' => false,
        ]);
        Sanctum::actingAs($admin);

        $this->postJson("/api/admin/assessments/{$assessment->id}/toggle-publish")
            ->assertUnprocessable();
    }

    private function assessment(array $attributes = []): Assessment
    {
        $assessment = Assessment::create([
            'title' => $attributes['title'] ?? 'PHP Fundamentals',
            'category' => 'programming',
            'description' => 'A short PHP assessment.',
            'time_limit_minutes' => 10,
            'passing_score' => 60,
            'is_published' => $attributes['is_published'] ?? true,
        ]);
        $assessment->questions()->createMany([
            ['question' => 'Which tag opens PHP?', 'options' => ['<php>', '<?php', '<script php>'], 'correct_option' => 1, 'points' => 1, 'position' => 1],
            ['question' => 'Which symbol starts a PHP variable?', 'options' => ['$', '#', '@'], 'correct_option' => 0, 'points' => 1, 'position' => 2],
        ]);

        return $assessment->load('questions');
    }
}
