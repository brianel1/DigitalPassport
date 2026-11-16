<?php
// Read-only scoring check. No search run, job or match is created for the demo user.
require __DIR__.'/../backend/vendor/autoload.php';
require __DIR__.'/review_output.php';
$app=require __DIR__.'/../backend/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$user=App\Models\User::where('email',Database\Seeders\DemoStudentData::EMAIL)->firstOrFail();
$evidence=app(App\Services\VerifiedEvidence::class)->terms($user);
$scorer=app(App\Services\JobFitScorer::class);$best=[];$checked=0;$full=0;
foreach(App\Models\JobListing::where(fn($q)=>$q->whereNull('expires_at')->orWhere('expires_at','>',now()))->cursor() as $listing){
 $result=$scorer->score($listing,$user,$evidence);$checked++;if($result['score']===100.0)$full++;
 $best[]=['title'=>$listing->title,'source'=>$listing->source,'score'=>$result['score'],'matched'=>$result['matched'],'missing'=>$result['missing']];
}
usort($best,fn($a,$b)=>$b['score']<=>$a['score']);
$report=['checkedAt'=>now()->toIso8601String(),'listingsChecked'=>$checked,'fullCoverageMatches'=>$full,'highest'=>array_slice($best,0,5),'savedSearches'=>$user->jobRecommendationRuns()->count(),'basis'=>'Read-only catalogue check; the user still needs Search jobs to apply search eligibility/filters and get an AI shortlist.'];
file_put_contents(__DIR__.'/../design-system/skills-career/demo-fit-evaluation.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
echo json_encode(['checked'=>$checked,'fullCoverage'=>$full,'bestScore'=>$best[0]['score']??null,'savedSearches'=>$report['savedSearches']])."\n";
