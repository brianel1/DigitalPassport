<?php
// Default: read-only comparison. --import-check creates/drops its own disposable database only.
require __DIR__.'/../backend/vendor/autoload.php';
$app=require __DIR__.'/../backend/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db=Illuminate\Support\Facades\DB::connection();
$normalise=fn(string $sql)=>preg_replace('/\s+/',' ',trim(preg_replace('/ AUTO_INCREMENT=\d+/','',rtrim($sql,";\r\n "))));
$live=[];
foreach($db->select('SHOW TABLES') as $row){
    $table=array_values((array)$row)[0];
    if(!preg_match('/^\w+$/D',$table))throw new RuntimeException('Unexpected table name.');
    $live[$table]=$normalise(array_values((array)$db->selectOne('SHOW CREATE TABLE `'.$table.'`'))[1]);
}
ksort($live);
$migrations=$db->table('migrations')->orderBy('migration')->pluck('migration')->all();
$files=array_map(fn($path)=>basename($path,'.php'),glob(__DIR__.'/../backend/database/migrations/*.php'));sort($files);
if($migrations!==$files)throw new RuntimeException('Live migration history differs from migration files.');
$bank=json_decode(file_get_contents(__DIR__.'/../backend/database/assessment_bank.json'),true,512,JSON_THROW_ON_ERROR);
$report=['checkedAt'=>now()->timezone('Asia/Kuala_Lumpur')->toIso8601String(),'tables'=>count($live),'migrations'=>count($migrations),'files'=>[]];

foreach(['database_clean.sql','database_with_data.sql'] as $file){
    $sql=file_get_contents(__DIR__.'/../backend/database/sql/'.$file);
    preg_match_all('/CREATE TABLE `(\w+)` \(.*?\) ENGINE=[^;]+;/s',$sql,$matches,PREG_SET_ORDER);
    $definitions=[];foreach($matches as $match)$definitions[$match[1]]=$normalise($match[0]);ksort($definitions);
    if($live!==$definitions){
        $different=array_keys(array_diff_assoc($live,$definitions)+array_diff_assoc($definitions,$live));
        throw new RuntimeException('Table definition mismatch in '.$file.': '.implode(', ',$different));
    }
    foreach($migrations as $migration)if(!str_contains($sql,"'".$migration."'"))throw new RuntimeException('Missing migration in '.$file.': '.$migration);
    preg_match_all('/INSERT INTO `(\w+)`/',$sql,$inserts);
    $allowed=$file==='database_clean.sql'?['migrations']:array_merge(['migrations','assessments','assessment_questions'],array_keys(Database\Seeders\DemoStudentData::rows()));
    if(array_diff($inserts[1],$allowed))throw new RuntimeException('Unexpected personal/runtime seed table in '.$file);
    $result=['schema'=>'matches live types, defaults, indexes, constraints and engine','import'=>'not requested'];

    if(in_array('--import-check',$argv,true)){
        $temporary='career_sql_check_'.bin2hex(random_bytes(6));
        if(!preg_match('/^career_sql_check_[a-f0-9]{12}$/D',$temporary)||$temporary===$db->getDatabaseName())throw new RuntimeException('Unsafe temporary database name.');
        if(preg_match('/DROP\s+DATABASE/i',$sql)||preg_match_all('/\bUSE\s+`/i',$sql)!==1||preg_match_all('/CREATE\s+DATABASE/i',$sql)!==1)throw new RuntimeException('Unexpected database directives.');
        $import=preg_replace('/CREATE DATABASE IF NOT EXISTS `digitalpassport`/','CREATE DATABASE IF NOT EXISTS `'.$temporary.'`',$sql,1,$createCount);
        $import=preg_replace('/USE `digitalpassport`;/','USE `'.$temporary.'`;',$import,1,$useCount);
        if($createCount!==1||$useCount!==1)throw new RuntimeException('Could not safely isolate SQL import.');
        $created=false;
        try{
            $db->statement('CREATE DATABASE `'.$temporary.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');$created=true;
            $cfg=$db->getConfig();
            $pdo=new PDO('mysql:host='.$cfg['host'].';port='.$cfg['port'].';dbname='.$temporary.';charset=utf8mb4',$cfg['username'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
            $pdo->exec($import);
            foreach($live as $table=>$definition){
                $actual=$pdo->query('SHOW CREATE TABLE `'.$table.'`')->fetch(PDO::FETCH_NUM)[1];
                if($normalise($actual)!==$definition)throw new RuntimeException('Imported schema mismatch: '.$table);
                if(!in_array($table,$allowed,true)&&(int)$pdo->query('SELECT COUNT(*) FROM `'.$table.'`')->fetchColumn()!==0)throw new RuntimeException('Unexpected records: '.$table);
            }
            if($pdo->query('SELECT migration FROM migrations ORDER BY migration')->fetchAll(PDO::FETCH_COLUMN)!==$migrations)throw new RuntimeException('Imported migration metadata differs.');
            $assessmentCount=(int)$pdo->query('SELECT COUNT(*) FROM assessments')->fetchColumn();
            $questionCount=(int)$pdo->query('SELECT COUNT(*) FROM assessment_questions')->fetchColumn();
            $hasData=$file==='database_with_data.sql';
            if($assessmentCount!==($hasData?count($bank):0)||$questionCount!==($hasData?array_sum(array_map(fn($a)=>count($a['questions']),$bank)):0))throw new RuntimeException('Seed counts differ.');
            if($hasData)foreach($bank as $index=>$expected){
                $actual=$pdo->query('SELECT * FROM assessments WHERE id='.($index+1))->fetch(PDO::FETCH_ASSOC);
                foreach(['title','category','description','time_limit_minutes','passing_score'] as $key)if((string)$actual[$key]!== (string)$expected[$key])throw new RuntimeException('Assessment seed differs: '.$key);
                if(json_decode($actual['skill_names'],true)!==$expected['skill_names'])throw new RuntimeException('Assessment skill mapping differs.');
                $questions=$pdo->query('SELECT * FROM assessment_questions WHERE assessment_id='.($index+1).' ORDER BY position')->fetchAll(PDO::FETCH_ASSOC);
                foreach($expected['questions'] as $position=>$question){
                    $row=$questions[$position];
                    if($row['question']!==$question['question']||json_decode($row['options'],true)!==$question['options']||(int)$row['correct_option']!==$question['correct_option'])throw new RuntimeException('Question/answer seed differs.');
                }
            }
            if($hasData){
                $demo=$pdo->query('SELECT * FROM users')->fetchAll(PDO::FETCH_ASSOC);
                if(count($demo)!==1 || $demo[0]['email']!==Database\Seeders\DemoStudentData::EMAIL || !password_verify(Database\Seeders\DemoStudentData::PASSWORD,$demo[0]['password']))throw new RuntimeException('Demo account is not correct.');
                foreach(Database\Seeders\DemoStudentData::rows() as $table=>$expected)if((int)$pdo->query('SELECT COUNT(*) FROM `'.$table.'`')->fetchColumn()!==count($expected))throw new RuntimeException('Incomplete demo table: '.$table);
            }
            $result+=['assessments'=>$assessmentCount,'questions'=>$questionCount];$result['import']='passed in isolated temporary database';
        }finally{
            $pdo=null;
            if($created)$db->statement('DROP DATABASE `'.$temporary.'`');
        }
    }
    $report['files'][$file]=$result;
    echo $file.': full schema matches ('.count($live).' tables, '.count($migrations).' migrations); '.$result['import'].".\n";
}
