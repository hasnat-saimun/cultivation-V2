<?php

namespace Tests\Feature;

use App\Exceptions\ResultPublicationException;
use App\Models\MarksScopeState;
use App\Models\Subject;
use App\Services\ResultPublicationReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesResultLifecycleScope;
use Tests\TestCase;

class ResultPublicationReadinessTest extends TestCase
{
    use RefreshDatabase, CreatesResultLifecycleScope;

    public function test_all_expected_subjects_confirmed_and_pass_fail_zero_are_ready(): void
    {
        foreach ([80, 20, 0] as $mark) {
            [$data, , $scope] = $this->confirmedLifecycleScope($mark);
            $evidence = app(ResultPublicationReadinessService::class)->prepare($scope);
            $this->assertSame(1, $evidence['student_count']);
            $this->assertSame(1, $evidence['subject_count']);
            $this->assertSame(2, $evidence['subject_revisions'][(string) $data['subject']->id]);
            $this->refreshDatabase();
        }
    }

    public function test_draft_or_missing_expected_confirmation_blocks_but_extra_irrelevant_state_does_not_help(): void
    {
        [$data, , $scope] = $this->confirmedLifecycleScope();
        MarksScopeState::first()->forceFill(['status' => 'draft'])->save();
        $extra = new Subject();
        $extra->subjectName = 'Irrelevant';
        $extra->subjectType = 'Theory';
        $extra->assign_class = '99';
        $extra->CQ = 100;
        $extra->save();
        $state = new MarksScopeState();
        $state->forceFill($scope + [
            'subjectId' => $extra->id, 'status' => 'confirmed', 'revision' => 1,
        ])->save();

        $this->expectException(ResultPublicationException::class);
        app(ResultPublicationReadinessService::class)->prepare($scope);
    }

    public function test_missing_marks_or_required_component_blocks_as_incomplete(): void
    {
        $data = $this->lifecycleScope();
        $scope = [
            'sessionId' => $data['session']->id, 'classId' => $data['class']->id,
            'groupId' => $data['section']->id, 'examId' => $data['exam']->id,
        ];
        $state = new MarksScopeState();
        $state->forceFill($scope + [
            'subjectId' => $data['subject']->id, 'status' => 'confirmed', 'revision' => 1,
        ])->save();
        try {
            app(ResultPublicationReadinessService::class)->prepare($scope);
            $this->fail('Missing marks must block publication.');
        } catch (ResultPublicationException $exception) {
            $this->assertSame('PublicationIncomplete', $exception->failure);
        }
    }

    public function test_section_specific_readiness_does_not_use_another_section(): void
    {
        [$data, , $scope] = $this->confirmedLifecycleScope();
        $wrong = $scope;
        $wrong['groupId'] = $data['section']->id + 999;
        $this->expectException(ResultPublicationException::class);
        app(ResultPublicationReadinessService::class)->prepare($wrong);
    }

    public function test_readiness_query_count_is_constant_for_one_twenty_five_and_one_hundred_students(): void
    {
        $counts = [];
        foreach ([1, 25, 100] as $studentCount) {
            [$data, , $scope] = $this->confirmedLifecycleScope(80, $studentCount);
            DB::flushQueryLog();
            DB::enableQueryLog();
            app(ResultPublicationReadinessService::class)->prepare($scope);
            $counts[] = count(DB::getQueryLog());
            DB::disableQueryLog();
            $this->refreshDatabase();
        }
        $this->assertSame([$counts[0], $counts[0], $counts[0]], $counts);
        $this->assertSame([7, 7, 7], $counts);
    }
}
