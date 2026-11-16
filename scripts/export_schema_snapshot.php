<?php
// Read-only database access. Writes schema/migrations plus the authored starter bank, never personal data.
require __DIR__.'/../backend/vendor/autoload.php';
$app = require __DIR__.'/../backend/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db=Illuminate\Support\Facades\DB::connection();
$database=$db->getDatabaseName();
if(!preg_match('/^[a-zA-Z0-9_]+$/D',$database))throw new RuntimeException('Unexpected database name.');
$tables=array_map(fn($row)=>array_values((array)$row)[0],$db->select('SHOW TABLES'));
$database='digitalpassport'; // Fresh-install default; never point an import at the user's live database automatically.
sort($tables);
$sql="-- Skills & Career schema snapshot, ".now()->timezone('Asia/Kuala_Lumpur')->format('j F Y').".\n-- FRESH installations only. Existing installations: php artisan migrate.\n-- No user accounts, tokens, personal records, job listings or AI outputs are included.\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\nCREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\nUSE `{$database}`;\n\n";
foreach($tables as $table){
    if(!preg_match('/^[a-zA-Z0-9_]+$/D',$table))throw new RuntimeException('Unexpected table name.');
    $create=array_values((array)$db->selectOne('SHOW CREATE TABLE `'.$table.'`'))[1];
    $create=preg_replace('/ AUTO_INCREMENT=\d+/','',$create);
    $sql.='DROP TABLE IF EXISTS `'.$table."`;\n".$create.";\n\n";
}
foreach($db->table('migrations')->orderBy('id')->get() as $migration){
    $sql.="INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (".(int)$migration->id.','.$db->getPdo()->quote($migration->migration).','.(int)$migration->batch.");\n";
}
$bank=json_decode(file_get_contents(__DIR__.'/../backend/database/assessment_bank.json'),true,512,JSON_THROW_ON_ERROR);
$seed="\n-- Authored assessment bank. User, evidence, listing, match and CV records are created by real workflows.\n";
$insert=function(string $table,array $values)use($db):string{
    $columns=implode(',',array_map(fn($key)=>'`'.$key.'`',array_keys($values)));
    $quoted=implode(',',array_map(fn($value)=>$value===null?'NULL':$db->getPdo()->quote((string)$value),array_values($values)));
    return "INSERT INTO `{$table}` ({$columns}) VALUES ({$quoted});\n";
};
$questionId=0;
foreach($bank as $index=>$assessment){
    $id=$index+1;$questions=$assessment['questions'];unset($assessment['questions']);
    $assessment['skill_names']=json_encode($assessment['skill_names'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    $seed.=$insert('assessments',['id'=>$id,'created_by'=>null,...$assessment,'is_published'=>1,'created_at'=>now()->toDateTimeString(),'updated_at'=>now()->toDateTimeString()]);
    foreach($questions as $position=>$question){
        $question['options']=json_encode($question['options'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
        $seed.=$insert('assessment_questions',['id'=>++$questionId,'assessment_id'=>$id,...$question,'points'=>1,'position'=>$position+1,'created_at'=>now()->toDateTimeString(),'updated_at'=>now()->toDateTimeString()]);
    }
}
$seed.="\n-- FICTIONAL demo student: demo@utem.edu.my / demo123. Development use only.\n-- Includes profile, verified demo records, completed assessments and a general CV. NO jobs or matches.\n";
foreach(Database\Seeders\DemoStudentData::rows() as $table=>$rows)foreach($rows as $row){
    foreach($row as &$value)if(is_array($value))$value=json_encode($value,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);unset($value);
    $seed.=$insert($table,$row);
}
$snapshotDirectory=__DIR__.'/../backend/database/sql';
if(!is_dir($snapshotDirectory) && !mkdir($snapshotDirectory,0775,true) && !is_dir($snapshotDirectory))throw new RuntimeException('Cannot create SQL snapshot directory.');
file_put_contents($snapshotDirectory.'/database_clean.sql',$sql."\nSET FOREIGN_KEY_CHECKS = 1;\n",LOCK_EX);
file_put_contents($snapshotDirectory.'/database_with_data.sql',str_replace('-- No user accounts, tokens, personal records, job listings or AI outputs are included.','-- Contains one explicitly fictional demo student; no real user data, tokens, jobs or matches.',$sql).$seed."\nSET FOREIGN_KEY_CHECKS = 1;\n",LOCK_EX);
echo 'Wrote '.count($tables).' full table definitions/migrations; with_data also has '.count($bank).' assessments and '.$questionId." questions. One fictional demo student included; no real personal data exported.\n";
