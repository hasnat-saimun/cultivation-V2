<?php

namespace Tests\Feature;

use App\Exceptions\ResultLifecycleException;
use App\Models\CultivationAdmin;
use App\Models\Marksheet;
use App\Models\MarksScopeState;
use App\Models\ResultPublish;
use App\Services\ResultMarksConfirmationService;
use App\Services\ResultMarksDraftService;
use App\Services\ResultLifecycleEventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Tests\Support\CreatesResultLifecycleScope;
use Tests\TestCase;

class ResultMarksConfirmationTest extends TestCase
{
    use RefreshDatabase, CreatesResultLifecycleScope;

    public function test_admin_and_teacher_confirmation_reconcile_stale_derived_cache_without_changing_raw_marks(): void
    {
        foreach ([CultivationAdmin::ROLE_GENERAL, CultivationAdmin::ROLE_TEACHER] as $role) {
            $data = $this->lifecycleScope();
            $draftActor = $this->lifecycleActor();
            app(ResultMarksDraftService::class)->save($this->lifecycleInput($data, 44), $draftActor, null, true);
            $mark = Marksheet::where('subjectId', (string) $data['subject']->id)->firstOrFail();
            $rawBefore = $mark->only(['subjectMarks', 'objectMarks', 'practicalMarks']);
            $this->assertSame(44.0, (float) $rawBefore['subjectMarks']);
            $mark->forceFill(['totalMarks' => 12, 'laterGrade' => 'F', 'gradePoint' => 0])->save();
            $authoritative = app(\App\Services\ResultCalculation\BoardResultCalculator::class)
                ->calculateSubject($data['students']->first(), $data['exam'], $mark->fresh(), $data['subject']);
            $this->assertSame(44.0, $authoritative->obtainedMarks);
            $this->assertSame('C', $authoritative->letterGrade);

            $confirmActor = $role === CultivationAdmin::ROLE_GENERAL
                ? $draftActor
                : $this->lifecycleActor(CultivationAdmin::ROLE_TEACHER);
            if ($role === CultivationAdmin::ROLE_TEACHER) {
                DB::table('teacher_class_subjects')->insert([
                    'teacher_id' => $confirmActor->id,
                    'session_id' => $data['session']->id,
                    'class_id' => $data['class']->id,
                    'section_id' => $data['section']->id,
                    'group_id' => null,
                    'subject_id' => $data['subject']->id,
                    'gender_scope' => 'all',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $result = app(ResultMarksConfirmationService::class)->confirm(
                $this->lifecycleInput($data, 44) + ['scope_revision' => 2],
                $confirmActor,
            );

            $mark->refresh();
            $this->assertSame('confirmed', $result['status']);
            $this->assertSame($rawBefore, $mark->only(array_keys($rawBefore)));
            $eventChanges = json_decode((string) DB::table('result_lifecycle_events')
                ->where('action', 'subject_confirmed')->latest('id')->value('change_set'), true);
            $this->assertSame(1, (int) data_get($eventChanges, 'derived_cache_reconciled_count', 0));
            $this->assertSame(44.0, (float) $mark->totalMarks);
            $this->assertSame('C', $mark->laterGrade);
            $this->assertSame(2.0, (float) $mark->gradePoint);
            $this->assertSame(2, (int) MarksScopeState::firstOrFail()->revision);

            $this->refreshDatabase();
        }
    }

    public function test_derived_cache_repair_rolls_back_when_confirmation_transaction_fails(): void
    {
        $data = $this->lifecycleScope();
        $actor = $this->lifecycleActor();
        app(ResultMarksDraftService::class)->save($this->lifecycleInput($data, 44), $actor, null, true);
        Marksheet::firstOrFail()->forceFill(['totalMarks' => 12, 'laterGrade' => 'F', 'gradePoint' => 0])->save();

        $this->mock(ResultLifecycleEventService::class, function (MockInterface $mock) {
            $mock->shouldReceive('correlationUuid')->once()->andReturn('rollback-test');
            $mock->shouldReceive('append')->once()->andThrow(new \RuntimeException('event write failed'));
        });

        try {
            app(ResultMarksConfirmationService::class)->confirm(
                $this->lifecycleInput($data, 44) + ['scope_revision' => 2],
                $actor,
            );
            $this->fail('Confirmation must roll back when its lifecycle event cannot be written.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('event write failed', $exception->getMessage());
        }

        $mark = Marksheet::firstOrFail();
        $this->assertSame(44.0, (float) $mark->subjectMarks);
        $this->assertSame(12.0, (float) $mark->totalMarks);
        $this->assertSame('F', $mark->laterGrade);
        $this->assertSame(0.0, (float) $mark->gradePoint);
        $this->assertSame(MarksScopeState::STATUS_DRAFT, MarksScopeState::firstOrFail()->status);
        $this->assertSame(2, (int) MarksScopeState::firstOrFail()->revision);
    }

    public function test_complete_pass_fail_and_zero_are_confirmable_without_mark_mutation(): void
    {
        foreach ([80, 20, 0] as $mark) {
            $data = $this->lifecycleScope();
            $actor = $this->lifecycleActor();
            app(ResultMarksDraftService::class)->save($this->lifecycleInput($data, $mark), $actor, null, true);
            $before = Marksheet::first()->only(['subjectMarks', 'objectMarks', 'practicalMarks']);
            $result = app(ResultMarksConfirmationService::class)->confirm(
                $this->lifecycleInput($data, $mark) + ['scope_revision' => 2],
                $actor,
            );
            $this->assertSame('confirmed', $result['status']);
            $this->assertSame($before, Marksheet::first()->only(array_keys($before)));
            $this->assertDatabaseHas('result_lifecycle_events', ['action' => 'subject_confirmed']);
            $this->refreshDatabase();
        }
    }

    public function test_missing_marks_and_incomplete_components_block_confirmation(): void
    {
        $data = $this->lifecycleScope();
        $actor = $this->lifecycleActor();
        MarksScopeState::create([
            'sessionId' => $data['session']->id, 'classId' => $data['class']->id,
            'groupId' => $data['section']->id, 'examId' => $data['exam']->id,
            'subjectId' => $data['subject']->id, 'status' => 'draft', 'revision' => 1,
        ]);
        try {
            app(ResultMarksConfirmationService::class)->confirm(
                $this->lifecycleInput($data) + ['scope_revision' => 1],
                $actor,
            );
            $this->fail('Missing marks should block.');
        } catch (ResultLifecycleException $exception) {
            $this->assertSame('BlankMarksConfirmationRequired', $exception->failure);
        }

        app(ResultMarksDraftService::class)->save(
            $this->lifecycleInput($data, null) + ['scope_revision' => 1],
            $actor,
        );
        try {
            app(ResultMarksConfirmationService::class)->confirm(
                $this->lifecycleInput($data) + ['scope_revision' => 2],
                $actor,
            );
            $this->fail('Blank marks without explicit override should request confirmation override.');
        } catch (ResultLifecycleException $exception) {
            $this->assertSame('BlankMarksConfirmationRequired', $exception->failure);
        }
    }

    public function test_confirm_with_blanks_override_succeeds_and_keeps_blank_and_zero_semantics(): void
    {
        $data = $this->lifecycleScope(2);
        $actor = $this->lifecycleActor();

        app(ResultMarksDraftService::class)->save([
            'sessionId' => $data['session']->id,
            'classId' => $data['class']->id,
            'groupId' => $data['section']->id,
            'examId' => $data['exam']->id,
            'subjectId' => $data['subject']->id,
            'studentId' => $data['students']->pluck('id')->all(),
            'cqMarks' => ['', '0'],
            'mcqMarks' => ['', ''],
            'practical' => ['', ''],
            'gender' => 'all',
            'scope_revision' => 1,
        ], $actor);

        $this->assertDatabaseHas('marksheets', [
            'studentId' => (string) $data['students'][0]->id,
            'subjectMarks' => null,
        ]);
        $this->assertDatabaseHas('marksheets', [
            'studentId' => (string) $data['students'][1]->id,
            'subjectMarks' => 0.0,
        ]);

        $result = app(ResultMarksConfirmationService::class)->confirm(
            $this->lifecycleInput($data) + ['scope_revision' => 2, 'confirm_blank_marks' => 1],
            $actor,
        );

        $this->assertSame('confirmed', $result['status']);
        $this->assertDatabaseHas('marks_scope_states', [
            'sessionId' => $data['session']->id,
            'classId' => $data['class']->id,
            'groupId' => $data['section']->id,
            'examId' => $data['exam']->id,
            'subjectId' => $data['subject']->id,
            'status' => 'confirmed',
        ]);
        $this->assertDatabaseHas('marksheets', [
            'studentId' => (string) $data['students'][0]->id,
            'subjectMarks' => null,
        ]);
        $this->assertDatabaseHas('marksheets', [
            'studentId' => (string) $data['students'][1]->id,
            'subjectMarks' => 0.0,
        ]);
    }

    public function test_unauthorized_confirm_with_blanks_is_rejected(): void
    {
        $data = $this->lifecycleScope();
        $general = $this->lifecycleActor();
        app(ResultMarksDraftService::class)->save($this->lifecycleInput($data, null) + ['scope_revision' => 1], $general);

        try {
            app(ResultMarksConfirmationService::class)->confirm(
                $this->lifecycleInput($data) + ['scope_revision' => 2, 'confirm_blank_marks' => 1],
                $this->lifecycleActor(CultivationAdmin::ROLE_CASH),
            );
            $this->fail('Cash admin should not confirm with blank override.');
        } catch (ResultLifecycleException $exception) {
            $this->assertSame(403, $exception->httpStatus);
        }
    }

    public function test_confirmation_is_idempotent_but_stale_and_published_are_rejected(): void
    {
        $data = $this->lifecycleScope();
        $actor = $this->lifecycleActor();
        app(ResultMarksDraftService::class)->save($this->lifecycleInput($data), $actor, null, true);
        $input = $this->lifecycleInput($data) + ['scope_revision' => 2];
        app(ResultMarksConfirmationService::class)->confirm($input, $actor);
        $confirmedAt = MarksScopeState::first()->confirmed_at;
        $this->assertSame('confirmed', app(ResultMarksConfirmationService::class)->confirm($input, $actor)['status']);
        $this->assertDatabaseCount('result_lifecycle_events', 2);
        $this->assertEquals($confirmedAt, MarksScopeState::first()->confirmed_at);

        try {
            app(ResultMarksConfirmationService::class)->confirm(
                array_replace($input, ['scope_revision' => 1]),
                $actor,
            );
            $this->fail('Stale confirmation should fail.');
        } catch (ResultLifecycleException $exception) {
            $this->assertSame('ScopeRevisionConflict', $exception->failure);
        }
    }

    public function test_cash_admin_and_published_parent_are_denied(): void
    {
        $data = $this->lifecycleScope();
        $general = $this->lifecycleActor();
        app(ResultMarksDraftService::class)->save($this->lifecycleInput($data), $general, null, true);
        $input = $this->lifecycleInput($data) + ['scope_revision' => 2];

        try {
            app(ResultMarksConfirmationService::class)->confirm(
                $input,
                $this->lifecycleActor(CultivationAdmin::ROLE_CASH),
            );
            $this->fail('Cash admin should fail.');
        } catch (ResultLifecycleException $exception) {
            $this->assertSame(403, $exception->httpStatus);
        }

        ResultPublish::create([
            'sessionId' => $data['session']->id, 'classId' => $data['class']->id,
            'groupId' => null, 'examId' => $data['exam']->id, 'status' => 'published',
        ]);
        $this->expectException(ResultLifecycleException::class);
        app(ResultMarksConfirmationService::class)->confirm($input, $general);
    }

    public function test_teacher_requires_provable_complete_population_coverage(): void
    {
        $data = $this->lifecycleScope(2);
        $data['students'][1]->update(['gender' => '2']);
        $general = $this->lifecycleActor();
        app(ResultMarksDraftService::class)->save($this->lifecycleInput($data), $general, null, true);
        $input = $this->lifecycleInput($data) + ['scope_revision' => 2];

        $fullTeacher = $this->lifecycleActor(CultivationAdmin::ROLE_TEACHER);
        DB::table('teacher_class_subjects')->insert([
            'teacher_id' => $fullTeacher->id,
            'session_id' => $data['session']->id,
            'class_id' => $data['class']->id,
            'section_id' => $data['section']->id,
            'group_id' => null,
            'subject_id' => $data['subject']->id,
            'gender_scope' => 'all',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->assertSame(
            'confirmed',
            app(ResultMarksConfirmationService::class)->confirm($input, $fullTeacher)['status'],
        );

        MarksScopeState::first()->forceFill([
            'status' => 'draft', 'confirmed_by' => null, 'confirmed_at' => null,
        ])->save();
        $partialTeacher = $this->lifecycleActor(CultivationAdmin::ROLE_TEACHER);
        DB::table('teacher_class_subjects')->insert([
            'teacher_id' => $partialTeacher->id,
            'session_id' => $data['session']->id,
            'class_id' => $data['class']->id,
            'section_id' => $data['section']->id,
            'group_id' => null,
            'subject_id' => $data['subject']->id,
            'gender_scope' => 'male',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        try {
            app(ResultMarksConfirmationService::class)->confirm($input, $partialTeacher);
            $this->fail('Partial teacher coverage must fail closed.');
        } catch (ResultLifecycleException $exception) {
            $this->assertSame(403, $exception->httpStatus);
        }
    }
}
