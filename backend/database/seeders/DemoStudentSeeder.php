<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Add the demo safely to an existing development database; never reset it. */
class DemoStudentSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('users')->where('email',DemoStudentData::EMAIL)->exists()) {
            $this->command?->info('Demo account already exists; its edits and password were preserved.'); return;
        }
        DB::transaction(function(){
            $ids=[];
            $bank=json_decode(file_get_contents(database_path('assessment_bank.json')),true,512,JSON_THROW_ON_ERROR);
            $qid=0;
            foreach($bank as $i=>$a){
                $assessment=\App\Models\Assessment::where('title',$a['title'])->where('version',1)->first();
                if(!$assessment){
                    $questions=$a['questions'];unset($a['questions']);
                    $assessment=\App\Models\Assessment::create([...$a,'is_published'=>true,'version'=>1]);
                    foreach($questions as $position=>$q)$assessment->questions()->create([...$q,'points'=>1,'position'=>$position+1]);
                }
                $ids['assessments'][$i+1]=$assessment->id;
                foreach($assessment->questions()->orderBy('position')->get() as $q)$ids['assessment_questions'][++$qid]=$q->id;
            }
            $foreign=['user_id'=>'users','project_id'=>'projects','skill_id'=>'skills','certification_id'=>'certifications','assessment_id'=>'assessments','attempt_id'=>'assessment_attempts','question_id'=>'assessment_questions'];
            foreach(DemoStudentData::rows() as $table=>$rows)foreach($rows as $row){
                $oldId=$row['id']??null;unset($row['id']);
                foreach($foreign as $key=>$parent)if(isset($row[$key]))$row[$key]=$ids[$parent][$row[$key]];
                if(isset($row['assessment_snapshot'])){
                    $row['assessment_snapshot']['family_id']=$row['assessment_id'];
                    foreach($row['assessment_snapshot']['questions'] as &$q)$q['id']=$ids['assessment_questions'][$q['id']];unset($q);
                }
                foreach($row as &$value)if(is_array($value))$value=json_encode($value,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);unset($value);
                if($oldId)$ids[$table][$oldId]=DB::table($table)->insertGetId($row);
                else DB::table($table)->insert($row);
            }
        });
        $this->command?->info('Created demo@utem.edu.my / demo123. Fictional profile only; no jobs or matches seeded.');
    }
}
