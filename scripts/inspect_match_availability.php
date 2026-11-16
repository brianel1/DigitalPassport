<?php
require __DIR__.'/../backend/vendor/autoload.php';
$app=require __DIR__.'/../backend/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$run=App\Models\JobRecommendationRun::has('matches')->latest('id')->first();
$report=['latestSearch'=>null,'catalogue'=>[]];
if($run){
    $report['latestSearch']=['createdAt'=>$run->created_at->toIso8601String(),'matches'=>$run->matches()->count(),'assessed'=>0,'unassessed'=>0,'reasons'=>[],'examples'=>[]];
    foreach($run->matches()->with('listing')->get() as $match){
        if(data_get($match->score_breakdown,'assessable')!==false){$report['latestSearch']['assessed']++;continue;}
        $report['latestSearch']['unassessed']++;
        $job=$match->listing;
        $reason=!data_get($job->raw_payload,'requirements_complete',true)?'incomplete_listing_requirements':'no_recognised_skill_terms';
        $key=$job->source.':'.$reason;
        $report['latestSearch']['reasons'][$key]=($report['latestSearch']['reasons'][$key]??0)+1;
        if(count($report['latestSearch']['examples'])<3)$report['latestSearch']['examples'][]=['title'=>$job->title,'source'=>$job->source,'reason'=>$reason,'scope'=>data_get($job->raw_payload,'description_scope'),'recognisedSkills'=>$job->required_skills];
    }
}
foreach(App\Models\JobListing::cursor() as $job){
    $source=$job->source;
    $report['catalogue'][$source]??=['total'=>0,'incompleteRequirements'=>0,'noRecognisedSkills'=>0];
    $report['catalogue'][$source]['total']++;
    if(!data_get($job->raw_payload,'requirements_complete',true))$report['catalogue'][$source]['incompleteRequirements']++;
    elseif(empty($job->required_skills))$report['catalogue'][$source]['noRecognisedSkills']++;
}
echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
