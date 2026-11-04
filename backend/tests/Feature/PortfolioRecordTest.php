<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PortfolioRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_create_project_with_evidence(): void
    {
        Storage::fake('local');
        Sanctum::actingAs(User::factory()->create());

        $response = $this->post('/api/student/projects', [
            'title' => 'Attendance System',
            'description' => 'A web system for recording class attendance.',
            'technologies' => ['Laravel', 'Vue', 'MySQL'],
            'project_url' => 'https://github.com/student/attendance',
            'completion_date' => '2026-10-01',
            'evidence' => UploadedFile::fake()->create('report.pdf', 200, 'application/pdf'),
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('evidenceFiles.0', 'report.pdf');

        $this->getJson('/api/student/projects')->assertJsonCount(1, 'data');
    }

    public function test_student_can_create_certification_with_evidence(): void
    {
        Storage::fake('local');
        Sanctum::actingAs(User::factory()->create());

        $response = $this->post('/api/student/certifications', [
            'title' => 'Cloud Fundamentals',
            'issuing_organization' => 'Example Academy',
            'issue_date' => '2026-08-01',
            'credential_id' => 'CERT-123',
            'evidence' => UploadedFile::fake()->image('certificate.png'),
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('referenceNumber', 'CERT-123')
            ->assertJsonPath('evidenceFiles.0', 'certificate.png');

        $this->getJson('/api/student/certifications')->assertJsonCount(1, 'data');
    }
}
