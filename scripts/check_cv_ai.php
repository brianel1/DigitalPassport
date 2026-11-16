<?php
// Real Ollama check against an isolated in-memory database; no live records changed.
require __DIR__.'/../backend/vendor/autoload.php';
$app = require __DIR__.'/../backend/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config()->set('database.default', 'sqlite');
config()->set('database.connections.sqlite.database', ':memory:');
config()->set('cache.default', 'array');
Illuminate\Support\Facades\DB::purge('sqlite');
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
$user = App\Models\User::factory()->create(['name' => 'Aina Review', 'email' => 'aina@example.test', 'programme' => 'Computer Science']);
$text = "Aina Review\naina@example.test\n+60 12 345 6789\nMelaka\nSUMMARY\nComputing student building accessible web applications with Laravel and Vue.\nSKILLS\nLaravel, Vue, SQL\nEDUCATION\nUTeM\nBachelor of Computer Science\n2024 - 2027\nEXPERIENCE\nWeb Intern\nCampus Lab\nJune 2026\nBuilt a booking application with Laravel used by 20 students.\nPROJECTS\nLibrary Booking\nCreated a Vue interface with SQL storage for room reservations.\n";
$service = app(App\Services\CvIntelligenceService::class);
if (in_array('--diagnostic', $argv, true)) Illuminate\Support\Facades\Event::listen(Illuminate\Http\Client\Events\ResponseReceived::class, function ($event) {
    $json = json_decode($event->response->json('response') ?? '', true);
    if (isset($json['rewrites']) || isset($json['checks'])) echo json_encode(['aiReviewDiagnostic' => $json], JSON_PRETTY_PRINT).PHP_EOL;
});
[$content, $engine] = $service->structure($text, $user);
echo json_encode(['stage' => 'structure', 'engine' => $engine, 'skills' => $content['skills'], 'education' => $content['education'], 'experience' => $content['experience'], 'projects' => $content['projects']], JSON_PRETTY_PRINT).PHP_EOL;
if (!str_starts_with($engine, 'ollama:') || !$content['experience'] || !$content['education']) throw new RuntimeException('Real AI extraction was incomplete.');
$content['summary'] = 'I am a computing student. I make web apps. I use Laravel and Vue. I built a booking application used by 20 students.';
$cv = $user->generatedCvs()->create(['title' => 'Isolated AI check', 'content' => $content, 'source_type' => 'paste', 'source_text' => $text, 'version' => 1, 'status' => 'draft', 'model_name' => $engine]);
$review = $service->review($cv);
echo json_encode(['stage' => 'review', 'engine' => $review['engine'], 'checks' => $review['passedChecks'], 'suggestions' => $review['suggestions'], 'rewrites' => $review['rewrites']], JSON_PRETTY_PRINT).PHP_EOL;
if (!str_starts_with($review['engine'], 'ollama:') || !$review['suggestions']) throw new RuntimeException('Real AI review did not return advice.');
$session = App\Models\AssistantSession::create(['user_id' => $user->id, 'generated_cv_id' => $cv->id, 'title' => 'Isolated check', 'mode' => 'interview', 'messages' => []]);
$assistant = app(App\Services\CareerAssistantService::class);
$reply = $assistant->reply($user, $session, 'Start a practice interview based on this CV. Ask one question.');
echo json_encode(['stage' => 'interview', 'engine' => $reply['engine'], 'answer' => $reply['content']], JSON_PRETTY_PRINT).PHP_EOL;
if (!str_starts_with($reply['engine'], 'ollama:')) throw new RuntimeException('Real AI interview failed.');
echo "Real AI services passed; all records existed only in memory.\n";
