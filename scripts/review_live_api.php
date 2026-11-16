<?php
require __DIR__.'/../backend/vendor/autoload.php';
require __DIR__.'/review_output.php';
$app=require __DIR__.'/../backend/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$fixture=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);
$client=Illuminate\Support\Facades\Http::withToken($fixture['student']['token'])->acceptJson()->timeout(200);
$url='http://127.0.0.1:8000/api';$id=null;
try{
 $session=$client->post($url.'/assistant/sessions',['mode'=>'coach','language'=>'ms'])->throw()->json();$id=$session['id'];
 $report=['checkedAt'=>now()->toIso8601String(),'cases'=>[]];
 foreach(['ATS tu apa sebenarnya?','aku xde pengalaman keje nk tulis ape?','Aku buat sistem tempahan makmal guna Laravel. Macam mana nak cerita dalam CV?','Bagi satu contoh ayat je','ringkaskan sikit'] as $question){
  $result=$client->post($url.'/assistant/sessions/'.$id.'/messages',['message'=>$question])->throw()->json();
  $answer=end($result['messages']);
  $report['cases'][]=['question'=>$question,'language'=>$result['language'],'engine'=>$answer['engine'],'answer'=>$answer['content']];
 }
 $report['selectedModelActive']=in_array('ollama:'.app(App\Services\OllamaClient::class)->chatModelName(),array_column($report['cases'],'engine'),true);
 file_put_contents(__DIR__.'/../design-system/skills-career/live-api-evaluation.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
 echo json_encode($report,JSON_UNESCAPED_UNICODE)."\n";
}finally{if($id)$client->delete($url.'/assistant/sessions/'.$id);}
