<?php
// Temporary, real API records for browser verification; removed by cleanup.
require __DIR__.'/../backend/vendor/autoload.php';
$app = require __DIR__.'/../backend/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$mode = $argv[1] ?? '';
$file = $argv[2] ?? '';
if (! $file) throw new RuntimeException('A private session file path is required.');
if ($mode === 'create') {
    if (file_exists($file)) throw new RuntimeException('Session file already exists.');
    $suffix = bin2hex(random_bytes(6));
    $result = Illuminate\Support\Facades\DB::transaction(function () use ($suffix) {
        $password=bin2hex(random_bytes(20));
        $user = App\Models\User::create([
            'name' => 'Interface Review',
            'email' => 'ui-review-'.$suffix.'@example.test',
            'password' => $password,
            'role' => 'student', 'is_active' => true,
            'programme' => 'Software Development', 'matric_no' => 'UI-'.$suffix,
        ]);
        $skill = $user->skills()->create([
            'name' => 'Interface testing', 'category' => 'web-development',
            'proficiency' => 'intermediate',
            'description' => 'Temporary record for checking the interface. Removed after verification.',
        ]);
        $project = $user->projects()->create([
            'title' => 'Temporary interface check',
            'description' => 'Checks the display of linked evidence and review controls. This temporary record is removed after browser verification.',
            'technologies' => ['Vue', 'Laravel'], 'project_url' => 'https://example.test/interface-review',
            'completion_date' => now()->toDateString(),
        ]);
        $project->skills()->attach($skill->id);
        $admin = App\Models\User::where('role', 'admin')->where('is_active', true)->firstOrFail();
        $adminToken = $admin->createToken('ui-review-'.$suffix);
        return [
            'student' => ['user' => App\Http\Controllers\AuthController::userData($user), 'token' => $user->createToken('ui-review-'.$suffix)->plainTextToken, 'isAuthenticated' => true],
            'admin' => ['user' => App\Http\Controllers\AuthController::userData($admin), 'token' => $adminToken->plainTextToken, 'isAuthenticated' => true],
            'cleanup' => ['studentId' => $user->id, 'email' => $user->email, 'adminTokenId' => $adminToken->accessToken->id, 'tokenName' => 'ui-review-'.$suffix],
            'projectId' => $project->id,
            'login' => ['email'=>$user->email,'password'=>$password],
        ];
    });
    file_put_contents($file, json_encode($result, JSON_THROW_ON_ERROR));
    echo "Temporary browser review session created.\n";
} elseif ($mode === 'cleanup') {
    $data = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
    $identity = $data['cleanup'];
    if (! preg_match('/^ui-review-[a-f0-9]{12}@example\.test$/', $identity['email'])) throw new RuntimeException('Unexpected cleanup identity.');
    Illuminate\Support\Facades\DB::transaction(function () use ($identity) {
        $user = App\Models\User::whereKey($identity['studentId'])->where('email', $identity['email'])->where('role', 'student')->first();
        if ($user) {
            // Polymorphic reviews have no record foreign key to cascade on user deletion.
            foreach ([App\Models\Project::class, App\Models\Skill::class, App\Models\Certification::class] as $recordClass) {
                $recordIds = $recordClass::where('user_id', $user->id)->pluck('id');
                App\Models\VerificationReview::where('reviewable_type', (new $recordClass)->getMorphClass())
                    ->whereIn('reviewable_id', $recordIds)->delete();
            }
            Illuminate\Support\Facades\DB::table('system_events')->where('user_id', $user->id)->delete();
            foreach (App\Models\EvidenceFile::where('user_id',$user->id)->get() as $evidence) {
                if(str_starts_with($evidence->path,'evidence/'.$user->id.'/')) Illuminate\Support\Facades\Storage::disk($evidence->disk)->delete($evidence->path);
            }
            foreach ($user->generatedCvs()->whereNotNull('source_path')->pluck('source_path') as $path) {
                if (str_starts_with($path, 'cv_sources/'.$user->id.'/')) Illuminate\Support\Facades\Storage::disk('local')->delete($path);
            }
            $user->tokens()->delete(); $user->delete();
        }
        Laravel\Sanctum\PersonalAccessToken::whereKey($identity['adminTokenId'])->where('name', $identity['tokenName'])->delete();
    });
    unlink($file);
    echo "Temporary user, records and review tokens removed.\n";
} else {
    throw new RuntimeException('Use create or cleanup.');
}
