<?php
// Parse captured public pages, or report the real catalogue. Does not invent or insert listings.
require __DIR__.'/../backend/vendor/autoload.php';
$app = require __DIR__.'/../backend/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (($argv[1] ?? '') === 'catalogue') {
    echo json_encode(app(App\Services\JobCatalogue::class)->summary(),JSON_PRETTY_PRINT).PHP_EOL;
    echo json_encode(Illuminate\Support\Facades\DB::table('job_sync_runs')->select('source','status','saved_count','message')->latest('id')->limit(7)->get(),JSON_PRETTY_PRINT).PHP_EOL;
    exit;
}
foreach (['hiredly','jobstore','ricebowl','maukerja'] as $source) {
    $file = sys_get_temp_dir().'/career-job-source-probe/'.$source.'.html';
    if (!is_file($file)) continue;
    $jobs = app(App\Services\JobProviders\PublicJobParser::class)->parse($source,file_get_contents($file));
    $eligible = array_values(array_filter($jobs,fn($job)=>app(App\Services\JobCatalogue::class)->eligibleArray($job,$source)));
    echo json_encode(['source'=>$source,'parsed'=>count($jobs),'eligible'=>count($eligible),'first'=>isset($eligible[0])?array_intersect_key($eligible[0],array_flip(['title','company','location','salary_min','salary_max','required_skills','raw_payload'])):null],JSON_PRETTY_PRINT).PHP_EOL;
}
