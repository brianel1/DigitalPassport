<?php
require __DIR__.'/../backend/vendor/autoload.php';
$app=require __DIR__.'/../backend/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$session=json_decode(file_get_contents($argv[1]),true,flags:JSON_THROW_ON_ERROR);
$user=App\Models\User::whereKey($session['cleanup']['studentId'])->where('email',$session['cleanup']['email'])->firstOrFail();
echo json_encode([
    'cvReviews'=>$user->generatedCvs->map(fn($cv)=>['version'=>$cv->version,'engine'=>$cv->analysis['engine']??null,'tailored'=>(bool)$cv->target_job_listing_id])->all(),
    'chatEngines'=>App\Models\AssistantSession::where('user_id',$user->id)->get()->flatMap(fn($chat)=>collect($chat->messages)->where('role','assistant')->pluck('engine'))->unique()->values()->all(),
    'temporarySkills'=>$user->skills()->count(),
    'migrations'=>Illuminate\Support\Facades\DB::table('migrations')->where('migration','2026_11_15_010000_add_job_score_breakdown')->exists(),
],JSON_PRETTY_PRINT)."\n";
