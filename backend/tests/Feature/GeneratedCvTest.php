<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\JobListing;
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

    public function test_incomplete_imported_projects_can_be_saved_and_reviewed(): void
    {
        config()->set('services.ollama.model', null);
        Sanctum::actingAs(User::factory()->create());
        $draft=$this->postJson('/api/student/cvs/upload', ['text'=>"Aina Rahman\naina@example.test\nProjects\nCampus App\nSkills: Laravel, PHP, MySQL"])->assertCreated();
        $content=$draft->json('content');
        $content['projects']=[['title'=>'Campus App','description'=>'','technologies'=>[]]];
        $content['education']=[['institution'=>'','qualification'=>'','period'=>'']];
        $this->putJson('/api/student/cvs/'.$draft->json('id'), ['title'=>'Draft','content'=>$content])->assertOk()->assertJsonPath('content.projects.0.description','');
        $review=$this->postJson('/api/student/cvs/'.$draft->json('id').'/review')->assertOk();
        $this->assertFalse(collect($review->json('analysis.checks'))->firstWhere('key','experience')['passed']);
        $this->assertFalse(collect($review->json('analysis.checks'))->firstWhere('key','education')['passed']);
        $content['personal']['email']='invalid';
        $invalid=$this->putJson('/api/student/cvs/'.$draft->json('id'), ['title'=>'Draft','content'=>$content])->assertUnprocessable();
        $this->assertStringContainsString('contact email',$invalid->json('message'));
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

    public function test_student_can_target_a_current_job_and_verified_skills_are_prioritised(): void
    {
        $student = User::factory()->create(['programme' => 'BITS']);
        $student->skills()->createMany([
            ['name' => 'Vue.js', 'category' => 'web-development', 'proficiency' => 'intermediate', 'status' => 'verified'],
            ['name' => 'Laravel', 'category' => 'web-development', 'proficiency' => 'intermediate', 'status' => 'verified'],
        ]);
        $job = JobListing::create([
            'source' => 'remotive', 'external_id' => 'current-1', 'title' => 'Backend Developer',
            'company' => 'Real Company', 'location' => 'Remote', 'description' => 'Build APIs.',
            'required_skills' => ['Laravel'], 'application_url' => 'https://example.com/job',
            'fetched_at' => now(),
        ]);
        Sanctum::actingAs($student);

        $this->postJson('/api/student/cvs', ['target_job_listing_id' => $job->id])
            ->assertCreated()
            ->assertJsonPath('targetJob.id', $job->id)
            ->assertJsonPath('content.skills.0', 'Laravel');

        $this->assertDatabaseHas('generated_cvs', ['user_id' => $student->id, 'target_job_listing_id' => $job->id]);
    }

    public function test_ai_review_keeps_original_profile_facts_until_user_applies_a_suggestion(): void
    {
        config()->set('services.ollama.model', 'qwen3:8b');
        Http::fake([
            '*127.0.0.1:11434/api/generate*' => Http::response([
                'response' => json_encode([
                    'strengths' => ['Your Portfolio Manager project demonstrates Laravel.'],
                    'suggestions' => ['Describe your personal contribution to Portfolio Manager.'],
                    'rewrites' => [],
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
            ->assertJsonPath('content.summary', 'BITS student building a verified record of technical skills and project experience.')
            ->assertJsonPath('content.projects.0.description', 'A Laravel application for portfolio records.')
            ->assertJsonPath('analysis.suggestions.0', 'Describe your personal contribution to Portfolio Manager.');

        $this->assertDatabaseHas('generated_cvs', [
            'user_id' => $student->id,
            'model_name' => 'ollama:qwen3:8b',
        ]);
        $this->assertSame('A Laravel application for portfolio records.', $student->generatedCvs()->first()->provenance['source_content']['projects'][0]['description']);
    }
}
