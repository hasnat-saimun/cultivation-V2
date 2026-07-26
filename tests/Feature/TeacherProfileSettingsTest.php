<?php

namespace Tests\Feature;

use App\Models\ClassManage;
use App\Models\CultivationAdmin;
use App\Models\SectionManage;
use App\Models\TeacherClassSubject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TeacherProfileSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_views_only_own_profile_and_guest_admin_session_are_denied(): void
    {
        $teacher=$this->teacher('Own Teacher');
        $this->actingAs($teacher,'teacher')->get(route('teacher.profile.show'))->assertOk()
            ->assertSee('Own Teacher')->assertSee($teacher->adminUser)->assertDontSee('loginPassword');
        auth('teacher')->logout();
        $this->get(route('teacher.profile.show'))->assertRedirect(route('teacher.login'));
        $this->withSession(['cultivationAdmin'=>1])->get(route('teacher.profile.show'))->assertRedirect(route('teacher.login'));
    }

    public function test_profile_updates_only_whitelisted_fields_and_cannot_target_another_account(): void
    {
        $teacher=$this->teacher(); $other=$this->teacher('Other');
        $class=$this->model(ClassManage::class,['className'=>'Class A']); $section=$this->model(SectionManage::class,['section'=>'A']);
        $teacher->forceFill(['primary_class_id'=>$class->id,'primary_section_id'=>$section->id,'userType'=>1])->save();
        $oldHash=$teacher->loginPassword;
        $payload=$this->profilePayload($teacher)+['id'=>$other->id,'adminUser'=>'hacked','userType'=>9,'role'=>'super',
            'status'=>'inactive','primary_class_id'=>999,'primary_section_id'=>999,'loginPassword'=>'plain'];
        $this->actingAs($teacher,'teacher')->put(route('teacher.profile.update'),$payload)->assertRedirect(route('teacher.profile.show'));
        $teacher->refresh(); $other->refresh();
        $this->assertSame('Updated Name',$teacher->adminName); $this->assertSame('updated@example.test',$teacher->adminMail);
        $this->assertSame('01800000000',$teacher->adminMobile); $this->assertSame($oldHash,$teacher->loginPassword);
        $this->assertSame(1,(int)$teacher->userType); $this->assertSame($class->id,(int)$teacher->primary_class_id);
        $this->assertSame('Other',$other->adminName);
    }

    public function test_email_mobile_format_and_uniqueness_are_enforced(): void
    {
        $teacher=$this->teacher(); $other=$this->teacher('Other');
        $this->actingAs($teacher,'teacher')->put(route('teacher.profile.update'),$this->profilePayload($teacher,['adminMail'=>$other->adminMail]))
            ->assertSessionHasErrors('adminMail');
        $this->put(route('teacher.profile.update'),$this->profilePayload($teacher,['adminMobile'=>$other->adminMobile]))
            ->assertSessionHasErrors('adminMobile');
        $this->put(route('teacher.profile.update'),$this->profilePayload($teacher,['adminMail'=>'bad','adminMobile'=>'abc']))
            ->assertSessionHasErrors(['adminMail','adminMobile']);
    }

    public function test_profile_update_does_not_change_assignments(): void
    {
        $teacher=$this->teacher(); TeacherClassSubject::create(['teacher_id'=>$teacher->id,'class_id'=>1,'subject_id'=>1]);
        $this->actingAs($teacher,'teacher')->put(route('teacher.profile.update'),$this->profilePayload($teacher));
        $this->assertDatabaseCount('teacher_class_subjects',1);
        $this->assertDatabaseHas('teacher_class_subjects',['teacher_id'=>$teacher->id,'class_id'=>1,'subject_id'=>1]);
    }

    public function test_password_requires_correct_current_value_and_confirmation(): void
    {
        $teacher=$this->teacher(); $this->actingAs($teacher,'teacher');
        $this->put(route('teacher.password.update'),['current_password'=>'wrong','password'=>'newsecret','password_confirmation'=>'newsecret'])
            ->assertSessionHasErrors('current_password');
        $this->put(route('teacher.password.update'),['current_password'=>'secret123','password'=>'newsecret','password_confirmation'=>'different'])
            ->assertSessionHasErrors('password');
        $this->assertTrue(Hash::check('secret123',$teacher->fresh()->loginPassword));
    }

    public function test_correct_password_change_updates_only_actor_with_hash_and_new_login_works(): void
    {
        $teacher=$this->teacher(); $other=$this->teacher('Other'); $name=$teacher->adminName;
        $this->actingAs($teacher,'teacher')->put(route('teacher.password.update'),[
            'current_password'=>'secret123','password'=>'newsecret9','password_confirmation'=>'newsecret9',
        ])->assertRedirect(route('teacher.profile.show'));
        $teacher->refresh(); $this->assertSame($name,$teacher->adminName);
        $this->assertFalse(Hash::check('secret123',$teacher->loginPassword)); $this->assertTrue(Hash::check('newsecret9',$teacher->loginPassword));
        $this->assertTrue(Hash::check('secret123',$other->fresh()->loginPassword));
        auth('teacher')->logout();
        $this->post(route('teacher.login.submit'),['identifier'=>$teacher->adminUser,'password'=>'secret123'])->assertSessionHasErrors('identifier');
        $this->post(route('teacher.login.submit'),['identifier'=>$teacher->adminUser,'password'=>'newsecret9'])->assertRedirect(route('teacher.dashboard'));
    }

    public function test_profile_photo_accepts_real_image_and_rejects_non_image(): void
    {
        $teacher=$this->teacher(); $this->actingAs($teacher,'teacher');
        $this->put(route('teacher.profile.update'),$this->profilePayload($teacher,['avatar'=>UploadedFile::fake()->image('avatar.jpg',200,200)]))
            ->assertRedirect(route('teacher.profile.show'));
        $name=$teacher->fresh()->avatar; $this->assertStringStartsWith('teacher-'.$teacher->id.'-',$name);
        $this->assertFileExists(public_path('upload/image/admin/'.$name));
        $this->put(route('teacher.profile.update'),$this->profilePayload($teacher,['avatar'=>UploadedFile::fake()->create('fake.jpg',10,'text/plain')]))
            ->assertSessionHasErrors('avatar');
        $this->assertSame($name,$teacher->fresh()->avatar);
        @unlink(public_path('upload/image/admin/'.$name));
    }

    public function test_teacher_profile_forms_use_shared_controls_and_preserve_validation_state(): void
    {
        $teacher = $this->teacher();

        $this->actingAs($teacher, 'teacher')->get(route('teacher.profile.edit'))
            ->assertOk()
            ->assertSee('class="tp-form"', false)
            ->assertSee('class="tp-control"', false)
            ->assertSee('type="file"', false)
            ->assertSee('.tp-form textarea', false)
            ->assertSee('.tp-control[disabled]', false);

        $this->put(route('teacher.profile.update'), $this->profilePayload($teacher, [
            'adminName' => 'Preserved Teacher',
            'adminMail' => 'invalid-email',
        ]))->assertSessionHasErrors('adminMail');

        $this->get(route('teacher.profile.edit'))
            ->assertOk()
            ->assertSee('value="Preserved Teacher"', false)
            ->assertSee('class="tp-error"', false);

        $this->get(route('teacher.password.edit'))
            ->assertOk()
            ->assertSee('class="tp-form"', false)
            ->assertSee('class="tp-btn tp-btn-primary"', false);
    }

    private function profilePayload(CultivationAdmin $teacher,array $override=[]): array { return array_merge(['adminName'=>'Updated Name','adminMail'=>'updated@example.test','adminMobile'=>'01800000000'],$override); }
    private function teacher(string $name='Teacher'): CultivationAdmin { static $n=0;$n++;$m=new CultivationAdmin();$m->forceFill(['adminName'=>$name,'adminUser'=>'teacher'.$n,'adminMail'=>"teacher{$n}@example.test",'adminMobile'=>'0170000000'.$n,'userType'=>1,'loginPassword'=>Hash::make('secret123')]);$m->save();return $m; }
    private function model(string $class,array $data) { $m=new $class();$m->forceFill($data);$m->save();return $m; }
}
