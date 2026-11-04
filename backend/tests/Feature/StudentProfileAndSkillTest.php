<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentProfileAndSkillTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_update_and_read_profile(): void
    {
        $student = User::factory()->create();
        Sanctum::actingAs($student);

        $this->putJson('/api/student/profile', [
            'name' => 'Aina Rahman',
            'programme' => 'BITC',
            'matric_no' => 'B032610010',
            'headline' => 'Software engineering student',
            'bio' => 'Interested in reliable web applications.',
            'location' => 'Melaka',
            'github_url' => 'https://github.com/aina',
        ])->assertOk()
            ->assertJsonPath('headline', 'Software engineering student')
            ->assertJsonPath('matricNo', 'B032610010');

        $this->getJson('/api/student/profile')
            ->assertOk()
            ->assertJsonPath('location', 'Melaka');
    }

    public function test_student_can_manage_own_skills(): void
    {
        $student = User::factory()->create();
        Sanctum::actingAs($student);

        $created = $this->postJson('/api/student/skills', [
            'name' => 'Laravel',
            'category' => 'web-development',
            'proficiency' => 'intermediate',
            'description' => 'Built REST APIs using Laravel.',
        ])->assertCreated()
            ->assertJsonPath('status', 'pending');

        $skillId = $created->json('id');

        $this->getJson('/api/student/skills')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->putJson("/api/student/skills/{$skillId}", [
            'name' => 'Laravel',
            'category' => 'web-development',
            'proficiency' => 'advanced',
            'description' => 'Built and tested REST APIs using Laravel.',
        ])->assertOk()
            ->assertJsonPath('proficiency', 'advanced');

        $this->deleteJson("/api/student/skills/{$skillId}")->assertOk();
        $this->assertDatabaseCount('skills', 0);
    }

    public function test_student_cannot_update_another_students_skill(): void
    {
        $owner = User::factory()->create();
        $otherStudent = User::factory()->create();
        $skill = $owner->skills()->create([
            'name' => 'PHP',
            'category' => 'programming',
            'proficiency' => 'intermediate',
        ]);

        Sanctum::actingAs($otherStudent);

        $this->putJson("/api/student/skills/{$skill->id}", [
            'name' => 'PHP',
            'category' => 'programming',
            'proficiency' => 'advanced',
        ])->assertNotFound();
    }
}
