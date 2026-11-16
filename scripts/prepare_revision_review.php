<?php
require __DIR__.'/../backend/vendor/autoload.php';
$app=require __DIR__.'/../backend/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$data=json_decode(file_get_contents($argv[1]),true,flags:JSON_THROW_ON_ERROR);
$user=App\Models\User::whereKey($data['cleanup']['studentId'])->where('email',$data['cleanup']['email'])->firstOrFail();
if(!str_starts_with($user->email,'ui-review-'))throw new RuntimeException('QA identity required.');
$content=app(App\Services\CvIntelligenceService::class)->blank($user);
$content['summary']='Computing student building accessible applications with Laravel and Vue. Built a booking application used by 20 students.';
$content['skills']=['Laravel','Vue','SQL'];
$content['education']=[['institution'=>'UTeM','qualification'=>'Bachelor of Computer Science','period'=>'2024 – 2027']];
for($i=1;$i<=7;$i++)$content['projects'][]=['title'=>'QA project '.$i,'description'=>$i===1?'':'Built and tested interfaces for a university project using Vue and Laravel.','technologies'=>['Vue','Laravel']];
$cv=$user->generatedCvs()->firstOrCreate(['version'=>1],['title'=>'Interface Review CV','source_type'=>'paste','source_filename'=>'QA source text','source_text'=>"Interface Review\nComputing student at UTeM. Skills: Laravel, Vue, SQL.\nProjects: QA project 1.",'content'=>$content,'status'=>'draft','model_name'=>'text-extraction-v1']);
for($i=1;$i<=12;$i++)$user->skills()->firstOrCreate(['name'=>'QA skill '.$i],['category'=>'programming','proficiency'=>'beginner','description'=>'Temporary pagination check, removed after review.','status'=>'pending']);
$session=App\Models\AssistantSession::create(['user_id'=>$user->id,'title'=>'Markdown rendering check','mode'=>'coach','generated_cv_id'=>$cv->id,'messages'=>[['role'=>'assistant','content'=>"**Certifications:**\n- Add your certificate evidence for review.\n**Next step:** Check your skills record.\n<script>window.qaInjected=true</script>",'engine'=>'platform-guide','actions'=>[],'sources'=>[],'createdAt'=>now()->toISOString()]]]);
$data['cvId']=$cv->id;$data['chatId']=$session->id;
file_put_contents($argv[1],json_encode($data,JSON_THROW_ON_ERROR));
echo "Prepared isolated CV, pagination and markdown fixtures for browser checks.\n";
