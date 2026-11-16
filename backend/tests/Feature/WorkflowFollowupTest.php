<?php
namespace Tests\Feature;

use App\Models\{User, Assessment, AssessmentAttempt};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkflowFollowupTest extends TestCase
{
    use RefreshDatabase;

    public function test_resubmission_preserves_proof_and_review_history_and_is_owner_only(): void
    {
        Storage::fake('local');$student=User::factory()->create();$admin=User::factory()->create(['role'=>'admin']);Sanctum::actingAs($student);
        $data=['title'=>'Campus App','description'=>'Built a campus application.','technologies'=>['Laravel'],'completion_date'=>'2026-01-01','role'=>'Developer'];
        $record=$this->post('/api/student/projects',[...$data,'evidence'=>UploadedFile::fake()->createWithContent('proof.pdf','%PDF-1.4 proof')],['Accept'=>'application/json'])->assertCreated()->json();
        $file=$record['evidence'][0]['id'];
        $this->get('/api/evidence/'.$file)->assertOk();
        Sanctum::actingAs($admin);$this->putJson('/api/admin/reviews/project/'.$record['id'],['decision'=>'verified','remarks'=>'Evidence checked.'])->assertOk();
        $this->putJson('/api/admin/reviews/project/'.$record['id'],['decision'=>'rejected'])->assertConflict();
        Sanctum::actingAs($student);
        $this->putJson('/api/student/projects/'.$record['id'],[...$data,'description'=>'Built accessible forms in the campus application.'])->assertOk()->assertJsonPath('status','pending')->assertJsonCount(1,'reviewHistory')->assertJsonCount(1,'evidence')->assertJsonPath('revision',2);
        Sanctum::actingAs(User::factory()->create());$this->get('/api/evidence/'.$file)->assertNotFound();
        $this->putJson('/api/student/projects/'.$record['id'],$data)->assertNotFound();
    }

    public function test_started_assessment_is_frozen_and_new_version_does_not_change_original_score(): void
    {
        $student=User::factory()->create();$admin=User::factory()->create(['role'=>'admin']);
        $a=Assessment::create(['title'=>'SQL','category'=>'databases','description'=>'SQL basics','time_limit_minutes'=>10,'passing_score'=>60,'skill_names'=>['SQL'],'is_published'=>true]);
        $q=$a->questions()->create(['question'=>'Which query reads rows?','options'=>['SELECT','DELETE'],'correct_option'=>0,'points'=>1,'position'=>1]);
        Sanctum::actingAs($student);$started=$this->postJson('/api/student/assessments/'.$a->id.'/attempts')->assertCreated()->assertJsonMissingPath('assessment.questions.0.correct_option');
        Sanctum::actingAs($admin);
        $this->putJson('/api/admin/assessments/'.$a->id,['title'=>'Changed','category'=>'databases','description'=>'Changed','time_limit_minutes'=>1,'passing_score'=>100,'skill_names'=>['AWS']])->assertConflict();
        $this->deleteJson('/api/admin/assessments/'.$a->id)->assertConflict();
        $version=$this->postJson('/api/admin/assessments/'.$a->id.'/versions')->assertCreated()->assertJsonPath('version',2)->json('id');
        $this->postJson('/api/admin/assessments/'.$version.'/toggle-publish')->assertOk();
        $this->assertFalse($a->fresh()->is_published);
        Sanctum::actingAs($student);
        $id=$started->json('attemptId');
        $this->postJson('/api/student/assessment-attempts/'.$id.'/submit',['answers'=>[['question_id'=>$q->id,'selected_option'=>0]]])->assertOk()->assertJsonPath('percentage',100)->assertJsonPath('skillNames',['SQL']);
        $this->postJson('/api/student/assessment-attempts/'.$id.'/submit',['answers'=>[]])->assertUnprocessable();
    }

    public function test_expired_attempt_closes_and_user_can_start_fresh(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $a=Assessment::create(['title'=>'SQL','category'=>'databases','description'=>'SQL basics','time_limit_minutes'=>1,'passing_score'=>60,'is_published'=>true]);
        $a->questions()->create(['question'=>'Read rows?','options'=>['SELECT','DELETE'],'correct_option'=>0,'points'=>1,'position'=>1]);
        $old=$this->postJson('/api/student/assessments/'.$a->id.'/attempts')->assertCreated()->json('attemptId');
        $this->travel(2)->minutes();
        $new=$this->postJson('/api/student/assessments/'.$a->id.'/attempts')->assertCreated()->json('attemptId');
        $this->assertNotSame($old,$new);$this->assertTrue(AssessmentAttempt::find($old)->assessment_snapshot['expired']);
        $this->assertNotNull(AssessmentAttempt::find($old)->submitted_at);
    }

    public function test_cv_name_and_phone_are_saved_without_creating_another_document(): void
    {
        $user=User::factory()->create();$user->profile()->create(['phone'=>'+60)12-2652519']);Sanctum::actingAs($user);
        $cv=$this->postJson('/api/student/cvs',['title'=>'My general CV'])->assertCreated()->assertJsonPath('title','My general CV')->assertJsonPath('content.personal.phone','+(60) - 122652519');
        $this->patchJson('/api/student/cvs/'.$cv->json('id').'/name',['title'=>'My renamed CV'])->assertOk()->assertJsonPath('title','My renamed CV');
        $this->assertDatabaseCount('generated_cvs',1);
        Sanctum::actingAs(User::factory()->create());$this->patchJson('/api/student/cvs/'.$cv->json('id').'/name',['title'=>'Other'])->assertNotFound();
    }

    public function test_progress_compares_real_previous_visit_and_admin_monitoring_is_restricted(): void
    {
        $user=User::factory()->create();Sanctum::actingAs($user);
        $this->postJson('/api/student/progress/visit')->assertOk()->assertJsonPath('previous',null)->assertJsonCount(6,'checklist');
        $user->skills()->create(['name'=>'SQL','category'=>'databases','proficiency'=>'beginner','status'=>'verified']);
        $this->postJson('/api/student/progress/visit')->assertOk()->assertJsonPath('previous.verifiedSkills',0)->assertJsonPath('current.verifiedSkills',1);
        $this->getJson('/api/admin/system-status')->assertForbidden();
        Sanctum::actingAs(User::factory()->create(['role'=>'admin']));$this->getJson('/api/admin/system-status')->assertOk()->assertJsonPath('ai.configured',false);
    }
}
