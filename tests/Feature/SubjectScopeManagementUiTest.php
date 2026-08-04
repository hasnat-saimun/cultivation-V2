<?php

namespace Tests\Feature;

use App\Models\{ClassManage, CultivationAdmin, Marksheet, Subject};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Hash};
use Tests\TestCase;

class SubjectScopeManagementUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_subject_create_edit_supports_multiple_and_all_classes_without_overlap(): void
    {
        [$admin, $classes] = $this->baseFixture();

        $this->withSession(['cultivationAdmin' => $admin->id])->post(route('confirmSubject'), [
            'subjectName' => 'General Science', 'subjectType' => 'Main', 'classIds' => [$classes[0]->id, $classes[1]->id],
            'cqValue' => 50, 'mcqValue' => 50,
        ])->assertSessionHas('success');

        $subject = Subject::where('subjectName', 'General Science')->firstOrFail();
        $this->assertSame($classes[0]->id.','.$classes[1]->id, $subject->assign_class);
        $this->assertDatabaseHas('subject_class_scopes', ['subject_id' => $subject->id, 'class_id' => $classes[0]->id]);
        $this->assertDatabaseHas('subject_class_scopes', ['subject_id' => $subject->id, 'class_id' => $classes[1]->id]);

        $this->withSession(['cultivationAdmin' => $admin->id])->post(route('confirmSubject'), [
            'subjectName' => ' general   science ', 'subjectType' => 'Main', 'classIds' => [$classes[1]->id, $classes[2]->id],
        ])->assertSessionHasErrors('classIds');

        $this->withSession(['cultivationAdmin' => $admin->id])->post(route('updateSubject'), [
            'itemId' => $subject->id, 'subjectName' => 'General Science', 'subjectType' => 'Main', 'allClasses' => 1,
            'cqValue' => 50, 'mcqValue' => 50,
        ])->assertSessionHas('success');

        $this->assertSame('0', (string) $subject->fresh()->assign_class);
        $this->assertDatabaseHas('subject_class_scopes', ['subject_id' => $subject->id, 'class_id' => null]);
        $this->withSession(['cultivationAdmin' => $admin->id])->get(route('editSubject', $subject->id))
            ->assertOk()->assertSee('All Classes')->assertSee('checked', false);
    }

    public function test_ui_dry_run_and_apply_reuse_transactional_split_engine(): void
    {
        [$admin, $classes] = $this->baseFixture();
        $source = Subject::create(['subjectName' => 'English 1st Paper', 'alias' => 'english_1st_paper', 'subjectType' => 'Main',
            'assign_class' => $classes[0]->id.','.$classes[1]->id, 'passingSystem' => '1', 'CQ' => 100]);
        $destination = Subject::create(['subjectName' => ' ENGLISH  1ST PAPER ', 'alias' => 'english_1st_paper', 'subjectType' => 'Main',
            'assign_class' => '', 'passingSystem' => '1', 'CQ' => 100]);
        foreach ($classes->take(2) as $class) {
            DB::table('subject_class_scopes')->insert(['subject_id' => $source->id, 'class_id' => $class->id, 'created_at' => now(), 'updated_at' => now()]);
        }
        $mark = Marksheet::create(['studentId' => 10, 'sessionId' => 2, 'classId' => $classes[1]->id, 'groupId' => 1,
            'examId' => 3, 'subjectId' => $source->id, 'subjectMarks' => 35.5]);
        DB::table('curriculum_subject_mappings')->insert(['session_id' => 2, 'class_id' => $classes[1]->id, 'section_id' => 1,
            'department_id' => 1, 'subject_id' => $source->id, 'mapping_type' => 'main', 'sort_order' => 1,
            'is_active' => 1, 'source' => 'test', 'created_at' => now(), 'updated_at' => now()]);

        $payload = ['remain' => [$classes[0]->id],
            'destination_mode' => 'existing', 'destination_id' => $destination->id];
        $this->withSession(['cultivationAdmin' => $admin->id])->get(route('subject.scope.split', $source->id))
            ->assertOk()->assertSee('Split/Migrate Subject Scope')->assertSee('English 1st Paper')
            ->assertSee('Classes that will REMAIN with this subject')->assertDontSee('name="migrate[]"', false);
        $this->withSession(['cultivationAdmin' => $admin->id])->post(route('subject.scope.split.preview', $source->id), $payload)
            ->assertOk()->assertSee('Dry-Run Preview')->assertSee('Affected marks')
            ->assertSee('Class Six')->assertSee('Class Seven')->assertDontSee('name="migrate[]"', false);
        $this->assertSame($source->id, (int) $mark->fresh()->subjectId);

        $this->withSession(['cultivationAdmin' => $admin->id])->post(route('subject.scope.split.apply', $source->id), $payload + ['confirmation' => 'APPLY'])
            ->assertRedirect(route('subject.scope.split', $source->id))->assertSessionHas('success');
        $this->assertSame($destination->id, (int) $mark->fresh()->subjectId);
        $this->assertSame('35.5', (string) $mark->fresh()->subjectMarks);
        $this->assertDatabaseHas('curriculum_subject_mappings', ['class_id' => (string) $classes[1]->id, 'subject_id' => $destination->id]);
        $this->assertDatabaseCount('subject_scope_migration_audits', 1);
    }

    public function test_no_class_is_separated_and_must_be_explicitly_kept_before_apply(): void
    {
        [$admin, $classes] = $this->baseFixture();
        $noClass = new ClassManage(); $noClass->forceFill(['className' => 'No Class']); $noClass->save();
        $source = Subject::create(['subjectName'=>'Legacy Scope Subject','alias'=>'legacy_scope','subjectType'=>'Main',
            'assign_class'=>$classes[0]->id.','.$classes[1]->id.','.$noClass->id,'passingSystem'=>'1','CQ'=>100]);
        $destination = Subject::create(['subjectName'=>' LEGACY  SCOPE SUBJECT ','alias'=>'legacy_scope','subjectType'=>'Main',
            'assign_class'=>'','passingSystem'=>'1','CQ'=>100]);
        foreach([$classes[0]->id,$classes[1]->id,$noClass->id] as $id) DB::table('subject_class_scopes')->insert(['subject_id'=>$source->id,'class_id'=>$id,'created_at'=>now(),'updated_at'=>now()]);
        $payload=['remain'=>[$classes[0]->id],'destination_mode'=>'existing','destination_id'=>$destination->id];
        $this->withSession(['cultivationAdmin'=>$admin->id])->get(route('subject.scope.split',$source->id))
            ->assertOk()->assertSee('Unresolved / legacy scope')->assertSee('No Class');
        $this->withSession(['cultivationAdmin'=>$admin->id])->post(route('subject.scope.split.preview',$source->id),$payload)
            ->assertOk()->assertSee('Manual resolution required before Apply');
        $this->withSession(['cultivationAdmin'=>$admin->id])->post(route('subject.scope.split.apply',$source->id),$payload+['confirmation'=>'APPLY'])
            ->assertSessionHasErrors('legacy_scope_resolution');
        $this->withSession(['cultivationAdmin'=>$admin->id])->post(route('subject.scope.split.apply',$source->id),$payload+[
            'confirmation'=>'APPLY','legacy_scope_resolution'=>[$noClass->id=>'keep_source']])->assertSessionHas('success');
        $this->assertDatabaseHas('subject_class_scopes',['subject_id'=>$source->id,'class_id'=>$noClass->id]);
        $this->assertDatabaseMissing('subject_class_scopes',['subject_id'=>$destination->id,'class_id'=>$noClass->id]);
    }

    private function baseFixture(): array
    {
        $admin = new CultivationAdmin();
        $admin->forceFill(['adminName' => 'General Admin', 'adminUser' => 'scope-admin', 'adminMail' => 'scope@example.test',
            'adminMobile' => '01700000001', 'userType' => CultivationAdmin::ROLE_GENERAL, 'loginPassword' => Hash::make('secret')]);
        $admin->save();
        $classes = collect(['Six', 'Seven', 'Eight'])->map(function ($name) {
            $class = new ClassManage();
            $class->forceFill(['className' => 'Class '.$name]);
            $class->save();
            return $class;
        });
        return [$admin, $classes];
    }
}
