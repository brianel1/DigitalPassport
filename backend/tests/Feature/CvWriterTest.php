<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CvWriterService;
use App\Support\CvDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CvWriterTest extends TestCase
{
    use RefreshDatabase;

    public function test_grounded_wording_is_applied_and_invented_facts_are_discarded(): void
    {
        config()->set('services.ollama.model', 'test-model');
        $project = ['title' => 'Booking app', 'role' => 'Developer', 'description' => 'Built a booking app with Laravel.', 'technologies' => ['Laravel', 'MySQL']];
        Http::fake(['*11434*' => Http::response(['message' => ['content' => json_encode([
            'headline' => 'Junior Laravel Developer',
            'summary' => 'Computer Science student focused on dependable web applications. Builds booking systems with Laravel and MySQL, from data design to working features. Works carefully, documents decisions and enjoys solving practical problems for real users. Seeking a junior developer role in Malaysia where reliable delivery matters.',
            'projects' => [['index' => 0, 'bullets' => [
                'Built a booking application with Laravel, covering reservations, validation and clear user flows.',
                'Designed relational tables in MySQL to store bookings reliably and keep records consistent.',
                'Improved booking speed by 40% for every campus user of the system.',
                'Wrote automated tests with PHPUnit to protect the booking rules from regressions.',
                'I personally delivered the full application on my own schedule and budget.',
            ]]],
        ])]])]);
        $content = ['personal' => ['headline' => ''], 'summary' => 'Old', 'skills' => ['Laravel', 'MySQL'], 'projects' => [['title' => 'Booking app', 'description' => 'Built a booking app with Laravel.', 'technologies' => ['Laravel', 'MySQL']]], 'certifications' => [], 'assessments' => []];

        $result = app(CvWriterService::class)->write($content, User::factory()->create(), [$project]);

        $this->assertTrue($result['written']);
        $this->assertSame('Junior Laravel Developer', $result['content']['personal']['headline']);
        $this->assertStringStartsWith('Computer Science student', $result['content']['summary']);
        $bullets = explode("\n", $result['content']['projects'][0]['description']);
        $this->assertCount(2, $bullets);
        $this->assertStringNotContainsString('40%', implode(' ', $bullets));
        $this->assertStringNotContainsString('PHPUnit', implode(' ', $bullets));
    }

    public function test_export_layout_splits_bullets_and_orders_sections_identically(): void
    {
        $doc = CvDocument::build([
            'personal' => ['name' => 'Aina', 'email' => 'a@x.test', 'phone' => '', 'location' => 'Melaka', 'headline' => 'Developer'],
            'summary' => 'Summary.', 'skills' => ['PHP', 'SQL'], 'experience' => [],
            'projects' => [['title' => 'App', 'description' => "- First line\n• Second line", 'technologies' => ['PHP']]],
            'education' => [['institution' => 'UTeM', 'qualification' => 'BCS', 'period' => '2023-2027', 'details' => 'CGPA 3.5. Dean list.']],
            'certifications' => [], 'assessments' => [['title' => 'SQL', 'percentage' => 100, 'completedAt' => '2026-11-16']], 'additional' => '',
        ]);

        $this->assertSame('a@x.test | Melaka', $doc['contact']);
        $this->assertSame(['Professional Summary', 'Skills', 'Projects', 'Education', 'Verified Assessments'], array_column($doc['sections'], 'title'));
        $this->assertSame(['First line', 'Second line'], $doc['sections'][2]['entries'][0]['bullets']);
        $this->assertSame(['CGPA 3.5.', 'Dean list.'], $doc['sections'][3]['entries'][0]['bullets']);
        $this->assertSame('SQL — 100% (2026-11-16)', $doc['sections'][4]['items'][0]);
    }
}
