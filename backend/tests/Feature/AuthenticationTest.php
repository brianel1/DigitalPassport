<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_register_and_receive_an_api_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Aina Rahman',
            'email' => 'aina@student.utem.edu.my',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'programme' => 'BITC',
            'matric_no' => 'B032610001',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.role', 'student')
            ->assertJsonPath('user.matricNo', 'B032610001')
            ->assertJsonStructure(['user', 'token']);

        $this->assertDatabaseHas('users', [
            'email' => 'aina@student.utem.edu.my',
            'role' => 'student',
        ]);
    }

    public function test_user_can_login_read_profile_and_logout(): void
    {
        User::factory()->create([
            'email' => 'student@student.utem.edu.my',
            'password' => 'password123',
            'programme' => 'BITS',
            'matric_no' => 'B032610002',
        ]);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'student@student.utem.edu.my',
            'password' => 'password123',
        ])->assertOk();

        $token = $login->json('token');

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('matricNo', 'B032610002');

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertUnauthorized();
    }

    public function test_login_rejects_an_incorrect_password(): void
    {
        User::factory()->create([
            'email' => 'student@student.utem.edu.my',
            'password' => 'password123',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'student@student.utem.edu.my',
            'password' => 'wrong-password',
        ])->assertUnprocessable();
    }
}
