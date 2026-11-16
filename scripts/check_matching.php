<?php
// Integration smoke check. All temporary student and recommendation rows are rolled back.
require __DIR__.'/../backend/vendor/autoload.php';
$app = require __DIR__.'/../backend/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

Illuminate\Support\Facades\DB::beginTransaction();
try {
    $student = App\Models\User::create([
        'name' => 'Temporary Integration Check',
        'email' => 'integration-check@digitalpassport.local',
        'password' => Illuminate\Support\Facades\Hash::make(bin2hex(random_bytes(16))),
        'role' => 'student',
        'programme' => 'Computer Science',
        'matric_no' => 'INTEGRATIONCHECK',
    ]);
    $student->skills()->create([
        'name' => 'Laravel', 'category' => 'web-development',
        'proficiency' => 'intermediate', 'status' => 'verified',
    ]);
    $run = $app->make(App\Services\JobRecommendationService::class)->generate($student);
    echo "matches={$run->matches->count()} engine={$run->model_name}\n";
} finally {
    Illuminate\Support\Facades\DB::rollBack();
}
