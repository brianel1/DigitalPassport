<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_students_and_change_account_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'matric_no' => null]);
        $student = User::factory()->create(['programme' => 'BITS', 'matric_no' => 'B0001']);
        $student->skills()->create([
            'name' => 'Laravel',
            'category' => 'web-development',
            'proficiency' => 'intermediate',
        ]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/users?search=B0001')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.skillsCount', 1)
            ->assertJsonPath('data.0.isActive', true);

        $this->putJson("/api/admin/users/{$student->id}/status", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('user.isActive', false);

        $this->assertDatabaseHas('users', ['id' => $student->id, 'is_active' => 0]);
    }

    public function test_student_cannot_manage_accounts(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/admin/users')->assertForbidden();
    }

    public function test_inactive_student_cannot_login(): void
    {
        $student = User::factory()->create(['is_active' => false]);

        $this->postJson('/api/auth/login', [
            'email' => $student->email,
            'password' => 'password',
        ])->assertForbidden();
    }
}
