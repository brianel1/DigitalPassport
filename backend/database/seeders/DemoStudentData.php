<?php

namespace Database\Seeders;

/** Explicitly fictional development fixture. Never copies an existing student's records. */
class DemoStudentData
{
    public const EMAIL = 'demo@utem.edu.my';
    public const PASSWORD = 'demo123';

    public static function rows(): array
    {
        $date='2026-11-16 08:00:00';
        $stamp=['created_at'=>$date,'updated_at'=>$date];
        $verified=['status'=>'verified','reviewed_by'=>null,'reviewed_at'=>$date,'admin_remarks'=>'Fictional development fixture, pre-verified for fit-score testing. Not a real qualification.'];
        $name='Aina Rahman (Demo)';
        $skills=['JavaScript','TypeScript','PHP','Laravel','Vue','React','HTML','CSS','SQL','MySQL','Git','REST API','Python','Docker','Communication','Teamwork'];
        $rows=['users'=>[['id'=>1,'name'=>$name,'email'=>self::EMAIL,'password'=>password_hash(self::PASSWORD,PASSWORD_BCRYPT),'role'=>'student','is_active'=>1,'programme'=>'Bachelor of Computer Science (Software Development)','matric_no'=>'DEMO2026001','email_verified_at'=>$date,...$stamp]],
            'student_profiles'=>[['id'=>1,'user_id'=>1,'headline'=>'Junior full-stack developer | Laravel, Vue and SQL','bio'=>'Fictional demonstration profile. Built accessible booking and inventory applications using Laravel, Vue and SQL; looking for a junior developer role in Malaysia.','phone'=>'+(60) - 123456789','location'=>'Kuala Lumpur, Malaysia','expected_graduation_date'=>'2027-06-30',...$stamp]],
            'skills'=>[], 'projects'=>[], 'certifications'=>[], 'project_skill'=>[], 'certification_skill'=>[],
        ];
        foreach($skills as $i=>$skill)$rows['skills'][]=['id'=>$i+1,'user_id'=>1,'name'=>$skill,'category'=>in_array($skill,['SQL','MySQL'])?'databases':(in_array($skill,['Communication','Teamwork'])?'soft-skills':'web-development'),'proficiency'=>'advanced','description'=>'Demonstrated in the fictional campus application portfolio. Seeded for development testing.',...$verified,...$stamp];
        $projects=[
            ['Campus laboratory booking','Full-stack developer','Built a responsive laboratory booking application with authentication, role permissions, collision checks, email reminders and automated tests. This is a fictional demonstration project.',['Laravel','PHP','Vue','TypeScript','MySQL','SQL','REST API','Git']],
            ['Student society inventory','Frontend developer','Created an accessible React inventory dashboard with reusable components, search, keyboard navigation and form validation. Worked with a three-person student team. Fictional demonstration project.',['React','JavaScript','HTML','CSS','Communication','Teamwork']],
            ['Inventory data quality checks','Developer','Wrote Python scripts to validate inventory records, generated a quality report and packaged the development environment with Docker. Fictional demonstration project.',['Python','Docker','SQL','Git']],
        ];
        foreach($projects as $i=>[$title,$role,$description,$tech]){
            $id=$i+1;
            $rows['projects'][]=['id'=>$id,'user_id'=>1,'title'=>$title.' (Demo)','role'=>$role,'description'=>$description,'technologies'=>$tech,'completion_date'=>'2026-10-15','submitted_at'=>$date,'submission_revision'=>1,...$verified,...$stamp];
            foreach($tech as $skill)$rows['project_skill'][]=['project_id'=>$id,'skill_id'=>array_search($skill,$skills,true)+1];
        }
        foreach([['Web Development Practice Certificate',['JavaScript','HTML','CSS','Vue']],['Database Design Practice Certificate',['SQL','MySQL']]] as $i=>[$title,$names]){
            $rows['certifications'][]=['id'=>$i+1,'user_id'=>1,'title'=>$title.' (Demo)','issuing_organization'=>'Skills & Career Demo Lab — fictional issuer','issue_date'=>'2026-09-01','credential_id'=>'DEMO-ONLY-'.($i+1),'submitted_at'=>$date,'submission_revision'=>1,...$verified,...$stamp];
            foreach($names as $skill)$rows['certification_skill'][]=['certification_id'=>$i+1,'skill_id'=>array_search($skill,$skills,true)+1];
        }
        $rows['job_preferences']=[['id'=>1,'user_id'=>1,'preferred_fields'=>['developer'],'preferred_locations'=>['Kuala Lumpur','Selangor','Melaka'],'work_types'=>['full-time'],'accepts_remote'=>1,'minimum_salary'=>3000,'maximum_salary'=>6000,...$stamp]];
        $rows['assessment_attempts']=[];$rows['assessment_answers']=[];
        $bank=json_decode(file_get_contents(database_path('assessment_bank.json')),true,512,JSON_THROW_ON_ERROR);
        $qid=0;$answerId=0;
        foreach($bank as $i=>$a){
            $questions=[];
            foreach($a['questions'] as $question){
                $id=++$qid;$questions[]=['id'=>$id,...$question,'points'=>1];
                $rows['assessment_answers'][]=['id'=>++$answerId,'attempt_id'=>$i+1,'question_id'=>$id,'selected_option'=>$question['correct_option'],'is_correct'=>1,'points_awarded'=>1,...$stamp];
            }
            $snapshot=['title'=>$a['title'],'category'=>$a['category'],'description'=>$a['description'],'version'=>1,'family_id'=>$i+1,'skill_names'=>$a['skill_names'],'time_limit'=>$a['time_limit_minutes'],'passing_score'=>$a['passing_score'],'questions'=>$questions];
            $rows['assessment_attempts'][]=['id'=>$i+1,'assessment_id'=>$i+1,'user_id'=>1,'score'=>count($questions),'maximum_score'=>count($questions),'percentage'=>100,'passed'=>1,'started_at'=>'2026-11-16 07:50:00','submitted_at'=>$date,'assessment_snapshot'=>$snapshot,...$stamp];
        }
        $rows['generated_cvs']=[['id'=>1,'user_id'=>1,'title'=>'Aina Rahman — General CV (Demo)','version'=>1,'status'=>'draft','source_type'=>'profile','model_name'=>'authored-demo-fixture','content'=>[
            'personal'=>['name'=>$name,'email'=>self::EMAIL,'phone'=>'+(60) - 123456789','location'=>'Kuala Lumpur, Malaysia','headline'=>'Junior full-stack developer','website'=>''],
            'summary'=>'Software development student experienced in Laravel, Vue, React and SQL through campus application projects. Seeking a junior developer role in Malaysia. This entire profile is fictional demonstration data.',
            'skills'=>$skills,'education'=>[['institution'=>'Demo University Malaysia (fictional)','qualification'=>'Bachelor of Computer Science (Software Development)','period'=>'2023–2027','details'=>'CGPA 3.80 (demonstration data)']],
            'experience'=>[['role'=>'Web development intern (Demo)','company'=>'Demo Campus Technology Lab (fictional)','period'=>'June–September 2026','description'=>'Implemented booking validation, wrote API tests and documented the deployment process in a supervised practice project.']],
            'projects'=>array_map(fn($p)=>['title'=>$p['title'],'description'=>$p['description'],'technologies'=>$p['technologies']],$rows['projects']),
            'certifications'=>array_map(fn($c)=>['title'=>$c['title'],'issuer'=>$c['issuing_organization'],'issueDate'=>$c['issue_date']],$rows['certifications']),
            'assessments'=>array_map(fn($a)=>['title'=>$a['title'].' (demo result)','percentage'=>100,'completedAt'=>'2026-11-16'],$bank),
            'additional'=>"Languages: Bahasa Melayu, English.\nAll qualifications and results in this account are fictional fixtures for development testing.",
        ],...$stamp]];
        return $rows;
    }
}
