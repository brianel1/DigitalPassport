<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GeneratedCvTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_generates_edits_and_exports_an_ats_cv(): void
    {
        Storage::fake('local');
        $student = User::factory()->create([
            'name' => 'Aina Rahman',
            'email' => 'aina@student.utem.edu.my',
            'programme' => 'BITC',
        ]);
        $student->profile()->create([
            'headline' => 'Software Engineering Student',
            'bio' => 'Computing student focused on reliable web applications.',
            'location' => 'Melaka',
        ]);
        $student->skills()->create([
            'name' => 'Laravel',
            'category' => 'web-development',
            'proficiency' => 'intermediate',
            'status' => 'verified',
        ]);
        Sanctum::actingAs($student);

        $generated = $this->postJson('/api/student/cvs')
            ->assertCreated()
            ->assertJsonPath('content.personal.name', 'Aina Rahman')
            ->assertJsonPath('content.skills.0', 'Laravel');

        $cvId = $generated->json('id');
        $content = $generated->json('content');
        $content['summary'] = 'Software engineering student with experience building Laravel applications.';

        $this->putJson("/api/student/cvs/{$cvId}", [
            'title' => 'Aina Rahman Software CV',
            'content' => $content,
        ])->assertOk()
            ->assertJsonPath('content.summary', $content['summary']);

        $this->get("/api/student/cvs/{$cvId}/export/pdf")
            ->assertOk()
            ->assertDownload('aina-rahman-software-cv.pdf');

        $this->get("/api/student/cvs/{$cvId}/export/word")
            ->assertOk()
            ->assertDownload('aina-rahman-software-cv.docx');
    }

    public function test_student_cannot_edit_another_students_cv(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $cv = $owner->generatedCvs()->create([
            'title' => 'Private CV',
            'content' => [],
            'version' => 1,
        ]);
        Sanctum::actingAs($other);

        $this->putJson("/api/student/cvs/{$cv->id}", [
            'title' => 'Changed CV',
            'content' => [],
        ])->assertNotFound();
    }

    public function test_ollama_can_prepare_cv_wording_from_verified_records(): void
    {
        config()->set('services.ollama.model', 'qwen3:8b');
        Http::fake([
            '*127.0.0.1:11434/api/generate*' => Http::response([
                'response' => json_encode([
                    'summary' => 'Computing student with verified Laravel project experience.',
                    'project_descriptions' => ['Built a verified Laravel application for portfolio management.'],
                ]),
            ]),
        ]);

        $student = User::factory()->create(['programme' => 'BITS']);
        $student->projects()->create([
            'title' => 'Portfolio Manager',
            'description' => 'A Laravel application for portfolio records.',
            'technologies' => ['Laravel'],
            'completion_date' => '2026-10-01',
            'status' => 'verified',
        ]);
        Sanctum::actingAs($student);

        $this->postJson('/api/student/cvs')
            ->assertCreated()
            ->assertJsonPath('content.summary', 'Computing student with verified Laravel project experience.')
            ->assertJsonPath('content.projects.0.description', 'Built a verified Laravel application for portfolio management.');

        $this->assertDatabaseHas('generated_cvs', [
            'user_id' => $student->id,
            'model_name' => 'ollama:qwen3:8b',
        ]);
    }
}
