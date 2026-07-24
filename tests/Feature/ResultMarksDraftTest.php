<?php

namespace Tests\Feature;

use App\Exceptions\ResultLifecycleException;
use App\Models\MarksScopeState;
use App\Models\ResultLifecycleEvent;
use App\Models\ResultPublish;
use App\Models\newAdmission;
use App\Models\sectionManage;
use App\Services\ResultMarksDraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesResultLifecycleScope;
use Tests\TestCase;

class ResultMarksDraftTest extends TestCase
{
    use RefreshDatabase, CreatesResultLifecycleScope;

    public function test_first_save_uses_exact_identity_revision_and_batch_event(): void
    {
        $data = $this->lifecycleScope(2);
        $result = app(ResultMarksDraftService::class)->save(
            $this->lifecycleInput($data),
            $this->lifecycleActor(),
            '127.0.0.1',
            true,
        );

        $this->assertSame(2, $result['changed_student_count']);
        $this->assertSame(2, $result['current_revisions']['section:'.$data['section']->id]);
        $this->assertDatabaseCount('marksheets', 2);
        $this->assertDatabaseHas('marks_scope_states', ['status' => 'draft', 'revision' => 2]);
        $event = ResultLifecycleEvent::firstOrFail();
        $this->assertSame('draft_marks_created', $event->action);
        $this->assertCount(2, $event->change_set);
        $this->assertEquals(80.0, $event->change_set[0]['after']['subjectMarks']);
    }

    public function test_no_change_is_idempotent_and_stale_revision_is_rejected(): void
    {
        $data = $this->lifecycleScope();
        $actor = $this->lifecycleActor();
        app(ResultMarksDraftService::class)->save($this->lifecycleInput($data), $actor, null, true);
        $input = $this->lifecycleInput($data) + ['scope_revision' => 2];
        $result = app(ResultMarksDraftService::class)->save($input, $actor);
        $this->assertSame(0, $result['changed_student_count']);
        $this->assertSame(2, MarksScopeState::first()->revision);
        $this->assertDatabaseCount('result_lifecycle_events', 1);

        $this->expectException(ResultLifecycleException::class);
        app(ResultMarksDraftService::class)->save(
            array_replace($input, ['scope_revision' => 1, 'cqMarks' => [70]]),
            $actor,
        );
    }

    public function test_zero_and_missing_remain_distinct_and_incomplete_draft_is_allowed(): void
    {
        $data = $this->lifecycleScope();
        $actor = $this->lifecycleActor();
        app(ResultMarksDraftService::class)->save($this->lifecycleInput($data, 0), $actor, null, true);
        $this->assertDatabaseHas('marksheets', ['subjectMarks' => 0, 'laterGrade' => 'F']);

        app(ResultMarksDraftService::class)->save(
            $this->lifecycleInput($data, null) + ['scope_revision' => 2],
            $actor,
        );
        $this->assertDatabaseHas('marksheets', ['subjectMarks' => null, 'totalMarks' => null]);
    }

    public function test_confirmed_and_published_scopes_are_read_only_for_admin(): void
    {
        $data = $this->lifecycleScope();
        $actor = $this->lifecycleActor();
        $scope = $this->lifecycleInput($data);
        app(ResultMarksDraftService::class)->save($scope, $actor, null, true);
        MarksScopeState::first()->forceFill(['status' => 'confirmed'])->save();

        try {
            app(ResultMarksDraftService::class)->save($scope + ['scope_revision' => 2], $actor);
            $this->fail('Confirmed save should fail.');
        } catch (ResultLifecycleException $exception) {
            $this->assertSame('ScopeAlreadyConfirmed', $exception->failure);
        }

        MarksScopeState::first()->forceFill(['status' => 'draft'])->save();
        ResultPublish::create([
            'sessionId' => $data['session']->id, 'classId' => $data['class']->id,
            'groupId' => $data['section']->id, 'examId' => $data['exam']->id,
            'status' => 'published',
        ]);
        $this->expectException(ResultLifecycleException::class);
        app(ResultMarksDraftService::class)->save($scope + ['scope_revision' => 2], $actor);
    }

    public function test_class_wide_batch_partitions_actual_sections_with_one_correlation(): void
    {
        $data = $this->lifecycleScope();
        $secondSection = new sectionManage();
        $secondSection->section = 'B';
        $secondSection->save();
        $second = new newAdmission($data['students']->first()->only([
            'stdId', 'fullName', 'sureName', 'gender', 'sessName', 'className',
            'departmentName', 'rollNumber',
        ]));
        $second->stdId = 9002;
        $second->sectionName = $secondSection->id;
        $second->rollNumber = '2';
        $second->save();
        $data['students']->push($second);
        $input = $this->lifecycleInput($data);
        $input['groupId'] = null;

        $result = app(ResultMarksDraftService::class)->save($input, $this->lifecycleActor(), null, true);

        $this->assertSame(2, $result['changed_student_count']);
        $this->assertSame(
            ['section:'.$data['section']->id, 'section:'.$secondSection->id],
            $result['affected_scopes'],
        );
        $this->assertDatabaseCount('marks_scope_states', 2);
        $this->assertSame(1, ResultLifecycleEvent::pluck('correlation_uuid')->unique()->count());
    }
}
