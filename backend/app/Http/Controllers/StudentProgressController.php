<?php
namespace App\Http\Controllers;

use App\Models\JobRecommendationRun;
use App\Services\AssessmentEvidence;
use App\Services\JobFitScorer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentProgressController extends Controller
{
    public function visit(Request $request)
    {
        abort_unless($request->user()->role==='student',403);
        $user=$request->user()->load(['profile','skills','projects.evidenceFiles','certifications','jobPreference']);
        $currentCertificates=$user->certifications->where('status','verified')->filter(fn($c)=>!$c->expiry_date||!$c->expiry_date->lt(today()));
        $best=app(AssessmentEvidence::class)->best($user);
        $checklist=[
            ['label'=>'Complete your personal details','done'=>filled($user->profile?->headline)&&mb_strlen($user->profile?->bio??'')>=40&&filled($user->programme),'path'=>'/student/profile'],
            ['label'=>'Have supporting work verified','done'=>$user->projects->contains('status','verified')||$currentCertificates->isNotEmpty(),'path'=>'/student/projects'],
            ['label'=>'Have a skill verified','done'=>$user->skills->contains('status','verified'),'path'=>'/student/skills'],
            ['label'=>'Pass a skills assessment','done'=>$best->isNotEmpty(),'path'=>'/student/assessments'],
            ['label'=>'Search for a role','done'=>$user->jobRecommendationRuns()->exists(),'path'=>'/student/jobs'],
            ['label'=>'Create or upload a CV','done'=>$user->generatedCvs()->exists(),'path'=>'/student/cv'],
        ];
        $run=JobRecommendationRun::where('user_id',$user->id)->with('matches.listing')->latest()->first();
        $fits=$run?->matches->filter(fn($m)=>$m->listing&&app(\App\Services\JobSearchContext::class)->visible($m->listing,$user))->map(fn($m)=>app(JobFitScorer::class)->score($m->listing,$user))->sortByDesc('score')->values()??collect();
        $gaps=$fits->take(5)->flatMap(fn($fit)=>$fit['missing'])->countBy()->sortDesc()->take(5)->map(fn($count,$skill)=>['skill'=>$skill,'roles'=>$count])->values()->all();
        $snapshot=['verifiedSkills'=>$user->skills->where('status','verified')->count(),'verifiedProjects'=>$user->projects->where('status','verified')->count(),'currentCertificates'=>$currentCertificates->count(),'passedAssessments'=>$best->count(),'cvCount'=>$user->generatedCvs()->count(),'topFit'=>$fits->first()['score']??null,'topJobId'=>$fits->first()['listing']->id??null];
        $previous=DB::table('student_visits')->where('user_id',$user->id)->first();
        DB::table('student_visits')->updateOrInsert(['user_id'=>$user->id],['snapshot'=>json_encode($snapshot),'created_at'=>$previous?->created_at??now(),'updated_at'=>now()]);
        return response()->json(['checklist'=>$checklist,'current'=>$snapshot,'previous'=>$previous?json_decode($previous->snapshot,true):null,'previousVisit'=>$previous?->updated_at,'gaps'=>$gaps,'comparedRoles'=>$fits->take(5)->count()]);
    }
}
