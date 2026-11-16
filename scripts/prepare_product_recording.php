<?php
// Prepare ONLY the isolated temporary browser account. No real user's records are changed.
require __DIR__.'/../backend/vendor/autoload.php';
$app=require __DIR__.'/../backend/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$file=$argv[1];$session=json_decode(file_get_contents($file),true);
$user=App\Models\User::whereKey($session['cleanup']['studentId'])->where('email',$session['cleanup']['email'])->firstOrFail();
if(!str_starts_with($user->email,'ui-review-'))throw new RuntimeException('Not a temporary account.');
$user->update(['name'=>'Demo Student']);
$user->profile()->updateOrCreate(['user_id'=>$user->id],['headline'=>'Computing student interested in web development','bio'=>'I study software development and build accessible web applications using JavaScript, TypeScript and React. This is an example profile for the recorded product walkthrough.','phone'=>'0123456789','location'=>'Melaka']);
$job=App\Models\JobListing::where('source','remotive')->where('fetched_at','>=',now()->subDays(14))->where('title','like','%Developer%')->whereNotNull('required_skills')->firstOrFail();
$technologies=array_slice($job->required_skills,0,3);
$project=$user->projects()->findOrFail($session['projectId']);
$project->update(['title'=>'Campus booking application · Demo','description'=>'Example project: built accessible booking forms and a searchable room list. This demonstration record is removed after recording.','role'=>'Frontend developer — booking forms and room search','technologies'=>$technologies,'project_url'=>null]);
$pdf=Barryvdh\DomPDF\Facade\Pdf::loadHTML('<h1>Demonstration evidence</h1><p>Campus booking application</p><p>Example contribution: accessible booking forms and room search.</p><p>Technologies: '.htmlspecialchars(implode(', ',$technologies)).'</p><p>This file is created for a labelled product walkthrough. It is not an actual student credential.</p>')->output();
$path='evidence/'.$user->id.'/product-demo.pdf';Illuminate\Support\Facades\Storage::disk('local')->put($path,$pdf);
$project->evidenceFiles()->create(['user_id'=>$user->id,'disk'=>'local','path'=>$path,'original_name'=>'Demonstration evidence.pdf','mime_type'=>'application/pdf','size'=>strlen($pdf)]);
$skill=$user->skills()->first();$skill->update(['name'=>$technologies[0],'description'=>'Demonstration skill linked to the example booking project.']);
$session['student']['user']=App\Http\Controllers\AuthController::userData($user);
$session['recording']=['jobId'=>$job->id,'beforeFit'=>app(App\Services\JobFitScorer::class)->score($job,$user)['score'],'technologies'=>$technologies,'projectId'=>$project->id,'skillId'=>$skill->id];
file_put_contents($file,json_encode($session));echo "Isolated recording evidence prepared.\n";
