<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PassportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_export_a_passport_from_verified_records(): void
    {
        Storage::fake('local');
        $student = User::factory()->create([
            'programme' => 'BITS',
            'matric_no' => 'B032510581',
        ]);
        $student->skills()->create([
            'name' => 'Laravel',
            'category' => 'web-development',
            'proficiency' => 'intermediate',
            'description' => 'Backend framework experience.',
            'status' => 'verified',
        ]);
        $student->skills()->create([
            'name' => 'Draft skill',
            'category' => 'other',
            'proficiency' => 'beginner',
            'status' => 'pending',
        ]);
        Sanctum::actingAs($student);

        $this->get('/api/student/passport/export')
            ->assertOk()
            ->assertDownload('digital-passport.pdf');
    }

    public function test_admin_cannot_export_a_student_passport(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin', 'matric_no' => null]));

        $this->getJson('/api/student/passport/export')->assertForbidden();
    }
}
