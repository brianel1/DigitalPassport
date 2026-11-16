<?php
namespace Tests\Feature;

use App\Models\User;
use App\Models\ProfileShare;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileSharingTest extends TestCase
{
    use RefreshDatabase;
    public function test_preview_is_private_and_share_contains_only_selected_current_fields(): void
    {
        $user=User::factory()->create(['name'=>'Demo Student','is_active'=>true]);
        $user->profile()->create(['headline'=>'Student developer','phone'=>'0123456789','bio'=>'Private biography']);
        $user->skills()->create(['name'=>'SQL','category'=>'databases','proficiency'=>'beginner','status'=>'verified']);
        $user->skills()->create(['name'=>'AWS','category'=>'cloud-computing','proficiency'=>'beginner','status'=>'pending']);
        Sanctum::actingAs($user);
        $this->postJson('/api/student/profile-shares/preview',['selection'=>['skills']])->assertOk()->assertJsonMissingPath('headline');
        $this->assertDatabaseCount('profile_shares',0);
        $link=$this->postJson('/api/student/profile-shares',['selection'=>['skills'],'days'=>1])->assertCreated()->json();
        $token=basename($link['path']);
        $this->assertNotSame($token,ProfileShare::first()->getRawOriginal('encrypted_token'));
        $response=$this->getJson('/api/shared-profile/'.$token)->assertOk()->assertHeader('Cache-Control','no-store, private')->assertJsonPath('name','Demo Student')->assertJsonCount(1,'sections.0.records');
        foreach(['phone','email','matric_no','bio','headline','evidenceFiles'] as $field)$response->assertJsonMissingPath($field);
        $user->skills()->where('name','SQL')->update(['status'=>'pending']);
        $this->getJson('/api/shared-profile/'.$token)->assertOk()->assertJsonCount(0,'sections.0.records');
        $this->travel(2)->days();
        $this->getJson('/api/shared-profile/'.$token)->assertNotFound();
    }
    public function test_only_owner_can_list_or_revoke_links_and_revocation_is_immediate(): void
    {
        $owner=User::factory()->create(['is_active'=>true]);Sanctum::actingAs($owner);
        $link=$this->postJson('/api/student/profile-shares',['selection'=>['projects'],'days'=>7])->assertCreated()->json();
        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/student/profile-shares')->assertJsonCount(0,'data');
        $this->deleteJson('/api/student/profile-shares/'.$link['id'])->assertNotFound();
        Sanctum::actingAs($owner);
        $this->deleteJson('/api/student/profile-shares/'.$link['id'])->assertOk();
        $this->getJson('/api/shared-profile/'.basename($link['path']))->assertNotFound();
        $this->getJson('/api/student/profile-shares')->assertJsonPath('data.0.path',null);
        $this->postJson('/api/student/profile-shares',['selection'=>['email']])->assertUnprocessable();
    }
}
