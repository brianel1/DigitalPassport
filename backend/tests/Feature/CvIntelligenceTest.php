<?php

namespace Tests\Feature;

use App\Models\GeneratedCv;
use App\Models\User;
use App\Services\CvTextExtractor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CvIntelligenceTest extends TestCase
{
    use RefreshDatabase;

    private string $text = "Aina Rahman\naina@example.test\nSUMMARY\nComputing student building accessible web applications.\nSKILLS\nLaravel, Vue, SQL\nEDUCATION\nUTeM\nComputer Science\n2024 - 2027\nEXPERIENCE\nWeb Intern\nCampus Lab\nJune 2026\nBuilt a booking application with Laravel.\n";

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config()->set('services.ollama.model', null);
        Sanctum::actingAs(User::factory()->create(['name' => 'Aina Rahman']));
    }

    public function test_pasted_cv_can_be_structured_without_ai_and_never_verifies_skills(): void
    {
        $result = $this->postJson('/api/student/cvs/upload', ['text' => $this->text])->assertCreated()
            ->assertJsonPath('sourceType', 'paste')->assertJsonPath('content.skills.0', 'Laravel');
        $this->postJson('/api/student/cvs/'.$result->json('id').'/structure', ['source_text' => $this->text])
            ->assertOk()->assertJsonPath('modelName', 'text-extraction-v1')->assertJsonPath('content.experience', []);
        $this->assertDatabaseCount('skills', 0);
        $this->assertDatabaseCount('projects', 0);
    }

    public function test_pdf_upload_extracts_real_text_and_original_is_private_then_deleted(): void
    {
        $bytes = Pdf::loadHTML('<h1>Aina Rahman</h1><p>aina@example.test</p><h2>Summary</h2><p>Computing student building accessible Laravel applications.</p>')->output();
        $file = UploadedFile::fake()->createWithContent('cv.pdf', $bytes);
        $response = $this->post('/api/student/cvs/upload', ['file' => $file], ['Accept' => 'application/json'])->assertCreated();
        $this->assertStringContainsString('Laravel', $response->json('sourceText'));
        $response->assertJsonMissingPath('source_path');
        $cv = GeneratedCv::findOrFail($response->json('id'));
        Storage::disk('local')->assertExists($cv->source_path);
        $this->get('/api/student/cvs/'.$cv->id.'/source')->assertOk()->assertDownload('cv.pdf');
        $this->deleteJson('/api/student/cvs/'.$cv->id)->assertOk();
        Storage::disk('local')->assertMissing($cv->source_path);
    }

    public function test_docx_extracts_text_in_paragraph_order(): void
    {
        $word = new \PhpOffice\PhpWord\PhpWord();
        $section = $word->addSection();
        foreach (explode("\n", $this->text) as $line) $section->addText($line);
        $path = tempnam(sys_get_temp_dir(), 'cv-docx-');
        try {
            $word->save($path, 'Word2007');
            $file = new UploadedFile($path, 'cv.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);
            $text = app(CvTextExtractor::class)->extract($file);
            $this->assertStringContainsString("EXPERIENCE\nWeb Intern\nCampus Lab", $text);
        } finally { if (file_exists($path)) unlink($path); }
    }

    public function test_unreadable_and_oversize_uploads_return_useful_validation_errors(): void
    {
        $this->post('/api/student/cvs/upload', ['file' => UploadedFile::fake()->createWithContent('broken.pdf', '%PDF-1.4 not a PDF')], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->post('/api/student/cvs/upload', ['file' => UploadedFile::fake()->create('large.pdf', 2049, 'application/pdf')], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->postJson('/api/student/cvs/upload', ['text' => 'Too short'])->assertUnprocessable();
        $this->assertDatabaseCount('generated_cvs', 0);
    }

    public function test_ai_extraction_discards_invented_values_and_preserves_real_experience(): void
    {
        $id = $this->postJson('/api/student/cvs/upload', ['text' => $this->text])->json('id');
        config()->set('services.ollama.model', 'test-model');
        Http::fake(['*11434*' => Http::response(['response' => json_encode([
            'personal' => ['name' => 'Aina Rahman', 'email' => 'invented@example.test', 'headline' => 'Senior Principal Engineer'],
            'summary' => 'Computing student building accessible web applications.', 'skills' => ['Laravel', 'Kubernetes'],
            'experience' => [['role' => 'Web Intern', 'company' => 'Campus Lab', 'period' => 'June 2026', 'description' => 'Built a booking application with Laravel.']],
            'assessments' => [['title' => 'Invented test', 'percentage' => 100]],
        ])])]);
        $this->postJson('/api/student/cvs/'.$id.'/structure', ['source_text' => $this->text])->assertOk()
            ->assertJsonPath('content.skills', ['Laravel','Vue','SQL'])->assertJsonPath('content.personal.headline', '')
            ->assertJsonPath('content.personal.email', 'aina@example.test')->assertJsonPath('content.experience.0.company', 'Campus Lab')
            ->assertJsonPath('content.assessments', [])->assertJsonPath('modelName', 'ollama:test-model');
    }

    public function test_review_does_not_apply_rewrites_and_rejects_new_numbers(): void
    {
        $id = $this->postJson('/api/student/cvs/upload', ['text' => $this->text])->json('id');
        config()->set('services.ollama.model', 'test-model');
        Http::fake(['*11434*' => Http::response(['response' => json_encode([
            'strengths' => ['Relevant web skills.'], 'suggestions' => ['Explain your personal contribution.'],
            'rewrites' => [['field' => 'summary', 'suggested' => 'Improved performance by 90%.', 'reason' => 'Specificity'], ['field' => 'personal.name', 'suggested' => 'Changed name']],
        ])])]);
        $original = GeneratedCv::find($id)->content;
        $this->postJson('/api/student/cvs/'.$id.'/review')->assertOk()->assertJsonPath('analysis.rewrites', [])
            ->assertJsonPath('analysis.verifiedSkills', [])->assertJsonPath('analysis.unverifiedSkills.0', 'Laravel');
        $this->assertSame($original, GeneratedCv::find($id)->content);
    }

    public function test_foreign_cv_is_hidden_from_all_new_endpoints(): void
    {
        $id = $this->postJson('/api/student/cvs/upload', ['text' => $this->text])->json('id');
        Sanctum::actingAs(User::factory()->create());
        foreach (['structure', 'review', 'duplicate'] as $action) $this->postJson('/api/student/cvs/'.$id.'/'.$action, ['source_text' => $this->text])->assertNotFound();
        $this->getJson('/api/student/cvs/'.$id.'/source')->assertNotFound();
        $this->deleteJson('/api/student/cvs/'.$id)->assertNotFound();
    }

    public function test_rewrites_require_a_second_factual_check(): void
    {
        $id = $this->postJson('/api/student/cvs/upload', ['text' => $this->text])->json('id');
        config()->set('services.ollama.model', 'test-model');
        Http::fake(['*11434*' => Http::sequence()->push(['response' => json_encode([
            'suggestions' => ['Keep the summary concise.'],
            'rewrites' => [['field' => 'summary', 'suggested' => 'Computing student creating accessible web applications.', 'reason' => 'Clearer wording.']],
        ])])->push(['response' => json_encode(['checks' => [['field' => 'summary', 'supported' => true]]])])]);
        $this->postJson('/api/student/cvs/'.$id.'/review')->assertOk()->assertJsonCount(1, 'analysis.rewrites');
        Http::fake(['*11434*' => Http::sequence()->push(['response' => json_encode([
            'rewrites' => [['field' => 'summary', 'suggested' => 'Computing student building web applications for a local hospital.']],
        ])])->push(['response' => json_encode(['checks' => [['field' => 'summary', 'supported' => false]]])])]);
        $this->postJson('/api/student/cvs/'.$id.'/review')->assertOk()->assertJsonPath('analysis.rewrites', []);
    }

    public function test_review_cannot_overwrite_a_concurrent_edit(): void
    {
        $id = $this->postJson('/api/student/cvs/upload', ['text' => $this->text])->json('id');
        config()->set('services.ollama.model', 'test-model');
        Http::fake(function () use ($id) {
            $cv = GeneratedCv::findOrFail($id);
            $content = $cv->content;
            $content['summary'] = 'A newer edit made while the AI request was running.';
            $cv->update(['content' => $content]);
            return Http::response(['response' => json_encode(['suggestions' => ['Old advice.']])]);
        });
        $this->postJson('/api/student/cvs/'.$id.'/review')->assertStatus(409);
        $this->assertSame('A newer edit made while the AI request was running.', GeneratedCv::findOrFail($id)->content['summary']);
        $this->assertNull(GeneratedCv::findOrFail($id)->analysis);
    }

    public function test_copy_preserves_content_and_deleting_it_does_not_delete_original_source(): void
    {
        $result = $this->post('/api/student/cvs/upload', ['file' => UploadedFile::fake()->createWithContent('cv.txt', $this->text)], ['Accept' => 'application/json'])->assertCreated();
        $original = GeneratedCv::findOrFail($result->json('id'));
        $copy = $this->postJson('/api/student/cvs/'.$original->id.'/duplicate')->assertCreated()->assertJsonPath('hasSourceFile', false);
        $this->assertSame($original->content, $copy->json('content'));
        $this->deleteJson('/api/student/cvs/'.$copy->json('id'))->assertOk();
        Storage::disk('local')->assertExists($original->source_path);
    }

    public function test_save_clears_stale_review_and_work_experience_survives_both_exports(): void
    {
        $response = $this->postJson('/api/student/cvs/upload', ['text' => $this->text])->assertCreated();
        $id = $response->json('id');
        $this->postJson('/api/student/cvs/'.$id.'/review')->assertOk();
        $content = $response->json('content');
        $content['experience'] = [['role' => 'Web Intern', 'company' => 'Campus & Lab', 'period' => 'June 2026', 'description' => 'Built a Laravel application.']];
        $this->putJson('/api/student/cvs/'.$id, ['title' => 'Reviewed CV', 'content' => $content])->assertOk()->assertJsonPath('analysis', null);
        $pdf = $this->get('/api/student/cvs/'.$id.'/export/pdf')->assertOk();
        $pdfPath = $pdf->baseResponse->getFile()->getPathname();
        $text = (new \Smalot\PdfParser\Parser())->parseFile($pdfPath)->getText();
        $this->assertStringContainsString('Web Intern', $text);
        $word = $this->get('/api/student/cvs/'.$id.'/export/word')->assertOk();
        $zip = new \ZipArchive(); $zip->open($word->baseResponse->getFile()->getPathname());
        $xml = $zip->getFromName('word/document.xml'); $zip->close();
        $this->assertStringContainsString('Web Intern', $xml);
        $this->assertStringContainsString('Campus &amp; Lab', $xml);
    }
}
