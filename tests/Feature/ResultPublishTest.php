<?php

namespace Tests\Feature;

use App\Exceptions\ResultPublicationException;
use App\Models\CultivationAdmin;
use App\Models\Marksheet;
use App\Models\MarksScopeState;
use App\Models\ResultLifecycleEvent;
use App\Models\ResultPublish;
use App\Models\Subject;
use App\Models\newAdmission;
use App\Models\sectionManage;
use App\Services\ResultCalculation\TabulationResultPresenter;
use App\Services\ResultMarksConfirmationService;
use App\Services\ResultMarksDraftService;
use App\Services\ResultCalculation\ResultCalculationBatchBuilder;
use App\Services\ResultPublishService;
use App\Services\ResultUnpublishService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesResultLifecycleScope;
use Tests\TestCase;

class ResultPublishTest extends TestCase
{
    use RefreshDatabase, CreatesResultLifecycleScope;

    public function test_general_and_super_admin_publish_complete_scope_without_mutating_marks_or_confirmations(): void
    {
        foreach ([CultivationAdmin::ROLE_GENERAL, 4] as $role) {
            [$data, , $input] = $this->confirmedLifecycleScope();
            $actor = $this->lifecycleActor($role);
            $marks = Marksheet::first()->getAttributes();
            $confirmation = MarksScopeState::first()->getAttributes();

            $result = app(ResultPublishService::class)->publish($input, $actor, '127.0.0.1');

            $this->assertFalse($result['idempotent']);
            $this->assertSame('published', $result['publications'][0]['status']);
            $this->assertSame(1, $result['publications'][0]['revision']);
            $this->assertSame($marks, Marksheet::first()->getAttributes());
            $this->assertSame($confirmation, MarksScopeState::first()->getAttributes());
            $this->assertDatabaseHas('result_lifecycle_events', ['action' => 'result_published']);
            $this->refreshDatabase();
        }
    }

    public function test_teacher_cash_unconfirmed_and_incomplete_scopes_are_denied(): void
    {
        [$data, , $input] = $this->confirmedLifecycleScope();
        foreach ([CultivationAdmin::ROLE_TEACHER, CultivationAdmin::ROLE_CASH] as $role) {
            try {
                app(ResultPublishService::class)->publish($input, $this->lifecycleActor($role));
                $this->fail('Role must not publish.');
            } catch (ResultPublicationException $exception) {
                $this->assertSame(403, $exception->httpStatus);
            }
        }

        MarksScopeState::first()->forceFill(['status' => 'draft'])->save();
        $this->expectException(ResultPublicationException::class);
        app(ResultPublishService::class)->publish($input, $this->lifecycleActor());
    }

    public function test_confirm_anyway_publishes_without_confirming_or_changing_non_ready_marks(): void
    {
        [$data, $actor, $input] = $this->confirmedLifecycleScope();
        $state = MarksScopeState::firstOrFail();
        $state->forceFill(['status' => MarksScopeState::STATUS_DRAFT])->save();
        $markBefore = Marksheet::firstOrFail()->getAttributes();
        $stateBefore = $state->fresh()->getAttributes();

        $result = app(ResultPublishService::class)->publish(
            $input + ['confirm_anyway' => true],
            $actor,
            '127.0.0.1',
        );

        $this->assertSame('published', $result['publications'][0]['status']);
        $this->assertSame($markBefore, Marksheet::firstOrFail()->getAttributes());
        $this->assertSame($stateBefore, $state->fresh()->getAttributes());
        $batch = app(ResultCalculationBatchBuilder::class)->build(
            $data['exam']->id,
            $data['class']->id,
            $data['session']->id,
            $data['section']->id,
        );
        $this->assertSame('Pass', $batch['entries'][$data['students']->first()->id]['result']->status);
        $event = ResultLifecycleEvent::where('action', 'result_published')->firstOrFail();
        $this->assertTrue((bool) data_get($event->change_set, 'confirmed_anyway'));
        $this->assertNotEmpty(data_get($event->change_set, 'non_ready_scopes'));
    }

    public function test_publication_guard_blocks_admin_then_unpublish_restores_draft_workflow(): void
    {
        [$data, $actor, $input] = $this->confirmedLifecycleScope();
        $state = MarksScopeState::firstOrFail();
        $state->forceFill(['status' => MarksScopeState::STATUS_DRAFT])->save();
        app(ResultPublishService::class)->publish($input + ['confirm_anyway' => true], $actor);

        try {
            app(ResultMarksDraftService::class)->save(
                $this->lifecycleInput($data) + ['scope_revision' => $state->revision],
                $actor,
            );
            $this->fail('Published marks must be read-only.');
        } catch (\App\Exceptions\ResultLifecycleException $exception) {
            $this->assertSame(
                'This result has already been published. Please unpublish it before making any changes.',
                $exception->getMessage(),
            );
        }

        app(ResultUnpublishService::class)->unpublish(
            $input + ['publication_revision' => 1, 'reason' => 'Correction required'],
            $actor,
        );
        $saved = app(ResultMarksDraftService::class)->save(
            $this->lifecycleInput($data) + ['scope_revision' => $state->revision],
            $actor,
        );
        $this->assertTrue($saved['success']);
    }

    public function test_already_published_is_idempotent_and_republish_increments_revision(): void
    {
        [$data, $actor, $input] = $this->confirmedLifecycleScope();
        app(ResultPublishService::class)->publish($input, $actor);
        $publishedAt = ResultPublish::first()->published_at;
        $events = ResultLifecycleEvent::count();
        $repeat = app(ResultPublishService::class)->publish($input + ['publication_revision' => 1], $actor);
        $this->assertTrue($repeat['idempotent']);
        $this->assertEquals($publishedAt, ResultPublish::first()->published_at);
        $this->assertSame($events, ResultLifecycleEvent::count());

        app(ResultUnpublishService::class)->unpublish(
            $input + ['publication_revision' => 1, 'reason' => 'Correction'],
            $actor,
        );
        $republished = app(ResultPublishService::class)->publish(
            $input + ['publication_revision' => 2],
            $actor,
        );
        $this->assertSame(3, $republished['publications'][0]['revision']);
        $this->assertDatabaseHas('result_publishes', [
            'status' => 'published', 'revision' => 3, 'legacyImported' => false,
            'unpublished_by' => null, 'unpublish_reason' => null,
        ]);
        $this->assertSame(2, ResultLifecycleEvent::where('action', 'result_published')->count());
    }

    public function test_all_sections_publish_atomically_and_roll_back_when_one_is_unconfirmed(): void
    {
        [$data, $actor, $input, $secondSection] = $this->twoSectionScope(false);
        try {
            app(ResultPublishService::class)->publish($input, $actor);
            $this->fail('One unconfirmed section must roll back all publications.');
        } catch (ResultPublicationException $exception) {
            $this->assertSame('PublicationUnconfirmed', $exception->failure);
        }
        $this->assertDatabaseCount('result_publishes', 0);

        $state = MarksScopeState::where('groupId', (string) $secondSection->id)->firstOrFail();
        app(ResultMarksConfirmationService::class)->confirm(
            array_replace($this->lifecycleInput($data), [
                'groupId' => $secondSection->id,
                'scope_revision' => $state->revision,
            ]),
            $actor,
        );
        DB::flushQueryLog();
        DB::enableQueryLog();
        $result = app(ResultPublishService::class)->publish($input, $actor);
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertCount(2, $result['publications']);
        $this->assertDatabaseCount('result_publishes', 2);
        $this->assertSame(1, ResultLifecycleEvent::where('action', 'result_published')
            ->pluck('correlation_uuid')->unique()->count());
        $this->assertLessThan(70, $queryCount);
        $this->assertSame(62, $queryCount);
    }

    public function test_genuinely_sectionless_scope_publishes_as_class_identity(): void
    {
        $data = $this->lifecycleScope();
        $data['students']->first()->update(['sectionName' => null]);
        DB::table('curriculum_subject_mappings')->insert([
            'session_id' => (string) $data['session']->id,
            'class_id' => (string) $data['class']->id,
            'section_id' => null,
            'department_id' => null,
            'subject_id' => (int) $data['subject']->id,
            'mapping_type' => 'main',
            'sort_order' => 2,
            'is_active' => 1,
            'source' => 'test-fixture',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $actor = $this->lifecycleActor();
        $marksInput = array_replace($this->lifecycleInput($data), ['groupId' => null]);
        app(ResultMarksDraftService::class)->save($marksInput, $actor, null, true);
        app(ResultMarksConfirmationService::class)->confirm(
            $marksInput + ['scope_revision' => 2],
            $actor,
        );
        $publishInput = [
            'sessionId' => $data['session']->id,
            'classId' => $data['class']->id,
            'groupId' => null,
            'examId' => $data['exam']->id,
        ];
        $result = app(ResultPublishService::class)->publish($publishInput, $actor);
        $this->assertNull($result['publications'][0]['scope']['groupId']);
        $this->assertDatabaseHas('result_publishes', ['groupId' => null, 'status' => 'published']);
    }

    public function test_single_section_publication_query_count_is_bounded(): void
    {
        [$data, $actor, $input] = $this->confirmedLifecycleScope(80, 25);
        DB::flushQueryLog();
        DB::enableQueryLog();
        app(ResultPublishService::class)->publish($input, $actor);
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertLessThan(40, $count);
        $this->assertSame(34, $count);
    }

    public function test_publish_lifecycle_preserves_authoritative_marks_and_classifications_when_confirm_anyway_is_used(): void
    {
        [$data, $actor, $input] = $this->confirmedLifecycleScope(80, 4);
        $students = $data['students']->values();
        $data['exam']->passingSystem = 2;
        $data['exam']->save();
        $data['subject']->subjectType = 'Main';
        $data['subject']->save();

        $secondSubject = new Subject();
        $secondSubject->subjectName = 'English';
        $secondSubject->subjectType = 'Main';
        $secondSubject->CQ = 100;
        $secondSubject->save();
        DB::table('curriculum_subject_mappings')->insert([
            'session_id' => (string) $data['session']->id,
            'class_id' => (string) $data['class']->id,
            'section_id' => (string) $data['section']->id,
            'department_id' => null,
            'subject_id' => (int) $secondSubject->id,
            'mapping_type' => 'main',
            'sort_order' => 2,
            'is_active' => 1,
            'source' => 'test-fixture',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Marksheet::query()->where('studentId', $students[1]->id)->update([
            'subjectMarks' => 0,
            'totalMarks' => 0,
        ]);
        Marksheet::query()->where('studentId', $students[3]->id)->delete();
        foreach ([$students[0], $students[1], $students[2]] as $student) {
            Marksheet::create([
                'studentId' => $student->id,
                'classId' => $data['class']->id,
                'sessionId' => $data['session']->id,
                'groupId' => $data['section']->id,
                'examId' => $data['exam']->id,
                'subjectId' => $secondSubject->id,
                'subjectMarks' => 80,
                'objectMarks' => null,
                'practicalMarks' => null,
                'totalMarks' => 80,
                'gradePoint' => 99,
                'laterGrade' => 'Stored',
            ]);
        }

        $finalScopeRevision = (int) MarksScopeState::query()->value('revision');
        MarksScopeState::query()->update([
            'status' => MarksScopeState::STATUS_DRAFT,
            'confirmed_at' => null,
            'confirmed_by' => null,
        ]);

        $snapshotBeforePublish = $this->scopeSnapshot($data);
        $this->assertSame(6, $snapshotBeforePublish['marksCount']);
        $this->assertSame(4, array_sum($snapshotBeforePublish['counts']));
        $this->assertGreaterThanOrEqual(1, $snapshotBeforePublish['counts']['Absent']);

        $published = app(ResultPublishService::class)->publish($input + ['confirm_anyway' => true], $actor);
        $this->assertFalse($published['idempotent']);

        $event = ResultLifecycleEvent::where('action', 'result_published')->latest('id')->firstOrFail();
        $this->assertTrue((bool) data_get($event->change_set, 'confirmed_anyway'));

        $snapshotAfterPublish = $this->scopeSnapshot($data);
        $this->assertSame($snapshotBeforePublish['marksCount'], $snapshotAfterPublish['marksCount']);
        $this->assertSame($snapshotBeforePublish['rowsByStudent'], $snapshotAfterPublish['rowsByStudent']);
        $this->assertSame($snapshotBeforePublish['counts'], $snapshotAfterPublish['counts']);

        $repeatPublish = app(ResultPublishService::class)->publish(
            $input + ['confirm_anyway' => true, 'publication_revision' => 1],
            $actor,
        );
        $this->assertTrue($repeatPublish['idempotent']);
        $this->assertSame($snapshotAfterPublish, $this->scopeSnapshot($data));

        app(ResultUnpublishService::class)->unpublish(
            $input + ['publication_revision' => 1, 'reason' => 'Regression verification'],
            $actor,
        );
        $snapshotAfterUnpublish = $this->scopeSnapshot($data);
        $this->assertSame($snapshotBeforePublish, $snapshotAfterUnpublish);

        app(ResultPublishService::class)->publish(
            $input + ['confirm_anyway' => true, 'publication_revision' => 2],
            $actor,
        );
        $snapshotAfterRepublish = $this->scopeSnapshot($data);
        $this->assertSame($snapshotBeforePublish, $snapshotAfterRepublish);

        $this->assertSame(MarksScopeState::STATUS_DRAFT, MarksScopeState::query()->value('status'));
        $this->assertSame($finalScopeRevision, (int) MarksScopeState::query()->value('revision'));
    }

    private function scopeSnapshot(array $data): array
    {
        $examId = (int) $data['exam']->id;
        $classId = (int) $data['class']->id;
        $sessionId = (int) $data['session']->id;
        $groupId = (int) $data['section']->id;

        $batch = app(ResultCalculationBatchBuilder::class)->build($examId, $classId, $sessionId, $groupId);
        $presented = app(TabulationResultPresenter::class)->present($batch['entries']);

        $rows = collect($presented['rows'])->keyBy('student.id');
        $studentIds = collect($data['students'])->pluck('id')->map(fn ($id) => (int) $id)->all();
        $rowsByStudent = [];
        foreach ($studentIds as $studentId) {
            $row = $rows[$studentId];
            $rowsByStudent[$studentId] = [
                'status' => $row['status'],
                'classification' => $row['classification'],
                'finalLetter' => $row['finalLetter'],
                'finalGpa' => $row['finalGpa'],
                'subjectFails' => (int) $row['subjectFails'],
                'totalMarks' => (float) $row['totalMarks'],
                'optionalBonus' => (float) $row['optionalBonus'],
            ];
        }

        $marksCount = Marksheet::query()
            ->where('examId', (string) $examId)
            ->where('classId', (string) $classId)
            ->where('sessionId', (string) $sessionId)
            ->where('groupId', (string) $groupId)
            ->count();

        return [
            'marksCount' => $marksCount,
            'counts' => [
                'Pass' => count($presented['reportSections']['Pass']),
                'Fail' => count($presented['reportSections']['Fail']),
                'Incomplete' => count($presented['reportSections']['Incomplete']),
                'Absent' => count($presented['reportSections']['Absent']),
            ],
            'rowsByStudent' => $rowsByStudent,
        ];
    }

    private function twoSectionScope(bool $confirmSecond): array
    {
        $data = $this->lifecycleScope();
        $secondSection = new sectionManage();
        $secondSection->section = 'B';
        $secondSection->save();
        $student = new newAdmission($data['students']->first()->only([
            'fullName', 'sureName', 'gender', 'sessName', 'className', 'departmentName',
        ]));
        $student->stdId = 9911;
        $student->sectionName = $secondSection->id;
        $student->rollNumber = '2';
        $student->save();
        $data['students']->push($student);
        DB::table('curriculum_subject_mappings')->insert([
            'session_id' => (string) $data['session']->id,
            'class_id' => (string) $data['class']->id,
            'section_id' => (string) $secondSection->id,
            'department_id' => null,
            'subject_id' => (int) $data['subject']->id,
            'mapping_type' => 'main',
            'sort_order' => 1,
            'is_active' => 1,
            'source' => 'test-fixture',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $actor = $this->lifecycleActor();
        $draft = $this->lifecycleInput($data);
        $draft['groupId'] = null;
        app(ResultMarksDraftService::class)->save($draft, $actor, null, true);
        foreach ([$data['section']->id, $secondSection->id] as $index => $sectionId) {
            if ($index === 1 && !$confirmSecond) continue;
            app(ResultMarksConfirmationService::class)->confirm(
                array_replace($this->lifecycleInput($data), [
                    'groupId' => $sectionId,
                    'scope_revision' => 2,
                ]),
                $actor,
            );
        }
        return [$data, $actor, [
            'sessionId' => $data['session']->id,
            'classId' => $data['class']->id,
            'groupId' => null,
            'examId' => $data['exam']->id,
        ], $secondSection];
    }
}
