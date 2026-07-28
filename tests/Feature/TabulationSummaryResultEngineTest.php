<?php

namespace Tests\Feature;

use App\Http\Controllers\MarksheetController;
use App\Models\classManage;
use App\Models\Exam;
use App\Models\Department;
use App\Models\Marksheet;
use App\Models\newAdmission;
use App\Models\Placement;
use App\Models\ResultArchive;
use App\Models\ResultPublish;
use App\Models\sectionManage;
use App\Models\sessionManage;
use App\Models\Subject;
use App\Services\ResultCalculation\BulkTranscriptResultBuilder;
use App\Services\ResultCalculation\ResultCalculationBatchBuilder;
use App\Services\ResultCalculation\ResultCalculationInputBuilder;
use App\Services\ResultCalculation\TabulationResultPresenter;
use App\Services\ResultCalculation\TranscriptResultPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class TabulationSummaryResultEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=', 'cache.default' => 'array']);
    }

    public function test_retired_controller_uses_centralized_tabulation_and_summary_when_flags_are_disabled(): void
    {
        config(['result_engine.tabulation_enabled' => false, 'result_engine.summary_enabled' => false]);
        $scope = $this->scope(); $main = $this->subject('Main', 'Main', 100); $optional = $this->subject('Optional', 'Optional', 100);
        $student = $this->student($scope, '01', $optional->id); $this->mark($student, $scope, $main, 80); $this->mark($student, $scope, $optional, 80);

        $tab = app(MarksheetController::class)->allMarksheet($this->request($scope))->getData();
        $summary = app(MarksheetController::class)->resultSummary($this->request($scope))->getData();

        $this->assertSame('5.00', $tab['passResults'][0]['finalGpa']);
        $this->assertSame(1, $summary['overallSummary']['pass']);
        $this->assertTrue($tab['usingCentralizedTabulation']);
        $this->assertTrue($summary['usingCentralizedSummary']);
    }

    public function test_enabled_tabulation_and_summary_classify_every_scoped_student(): void
    {
        $scope = $this->scope(); $main = $this->subject('Main', 'Main', 100); $optional = $this->subject('Optional', 'Optional', 100);
        $pass = $this->student($scope, '01', $optional->id); $optionalFail = $this->student($scope, '02', $optional->id);
        $fail = $this->student($scope, '03'); $incomplete = $this->student($scope, '04');
        $this->mark($pass, $scope, $main, 80); $this->mark($pass, $scope, $optional, 80);
        $this->mark($optionalFail, $scope, $main, 80); $this->mark($optionalFail, $scope, $optional, 0);
        $this->mark($fail, $scope, $main, 32);
        config(['result_engine.tabulation_enabled' => true, 'result_engine.summary_enabled' => true]);

        $tab = app(MarksheetController::class)->allMarksheet($this->request($scope))->getData();
        $summary = app(MarksheetController::class)->resultSummary($this->request($scope))->getData();

        $this->assertCount(2, $tab['passResults']); $this->assertCount(1, $tab['failResults']); $this->assertCount(0, $tab['incompleteResults']);
        $this->assertCount(1, $tab['absentResults']);
        $this->assertSame(['Pass', 'Pass', 'Fail', 'Absent'], collect($tab['glanceRows'])->pluck('reportStatus')->all());
        $this->assertCount(2, $tab['reportSections']['Pass']);
        $this->assertCount(1, $tab['failedGroups'][1]);
        $this->assertArrayNotHasKey(0, $tab['failedGroups']);
        $this->assertSame('5.00', $tab['passResults'][0]['finalGpa']);
        $this->assertSame(0, $tab['passResults'][1]['subjectFails']);
        $this->assertSame(['total' => 4, 'present' => 3, 'absent' => 1, 'pass' => 2, 'fail' => 1, 'incomplete' => 0,
            'passPercentage' => 50.0, 'failPercentage' => 25.0, 'incompletePercentage' => 0.0], $summary['overallSummary']);
    }

    public function test_retired_controller_does_not_restore_legacy_paths_for_mixed_flags(): void
    {
        $scope = $this->scope(); $main = $this->subject('Main', 'Main', 100); $optional = $this->subject('Optional', 'Optional', 100);
        $student = $this->student($scope, '01', $optional->id); $this->mark($student, $scope, $main, 80); $this->mark($student, $scope, $optional, 0);

        config(['result_engine.tabulation_enabled' => true, 'result_engine.summary_enabled' => false]);
        $centralTab = app(MarksheetController::class)->allMarksheet($this->request($scope))->getData();
        $legacySummary = app(MarksheetController::class)->resultSummary($this->request($scope))->getData();
        $this->assertCount(1, $centralTab['passResults']);
        $this->assertSame(1, $legacySummary['overallSummary']['pass']);

        config(['result_engine.tabulation_enabled' => false, 'result_engine.summary_enabled' => true]);
        $legacyTab = app(MarksheetController::class)->allMarksheet($this->request($scope))->getData();
        $centralSummary = app(MarksheetController::class)->resultSummary($this->request($scope))->getData();
        $this->assertCount(1, $legacyTab['passResults']);
        $this->assertSame(1, $centralSummary['overallSummary']['pass']);
    }

    #[DataProvider('parityScenarioProvider')]
    public function test_cross_output_calculation_parity(string $scenario): void
    {
        [$student, $scope] = $this->scenario($scenario);
        $batch = app(ResultCalculationBatchBuilder::class)->build($scope['exam']->id, $scope['class']->id, $scope['session']->id, $scope['section']->id);
        $entry = $batch['entries'][$student->id]; $student = $entry['student']; $result = $entry['result'];
        $single = app(TranscriptResultPresenter::class)->present($result, $entry['subjects'], $student->marksheet);
        $bulk = app(BulkTranscriptResultBuilder::class)->build(collect([$student]), $scope['exam'])[0]['result'];
        $tabulation = app(TabulationResultPresenter::class)->present($batch['entries']);
        $row = collect($tabulation['rows'])->firstWhere('student.id', $student->id);
        $summary = app(TabulationResultPresenter::class)->summarize($tabulation['rows'], $tabulation['subjects']);

        $this->assertSame($single['gpa'], $bulk['gpa']);
        $this->assertSame($single['status'], $row['status']);
        $this->assertSame($single['letterGrade'], $row['finalLetter']);
        $this->assertSame($single['optionalBonus'], $row['optionalBonus']);
        $this->assertSame($single['failedSubjects'], collect($row['subjects'])->where('status', 'Fail')->pluck('name')->values()->all());
        $this->assertSame($single['missingSubjects'], collect($row['subjects'])->where('status', 'Incomplete')->pluck('name')->values()->all());
        $this->assertSame($this->rowData($single), $this->rowData(['mainRows' => $row['subjects'], 'optionalRows' => []]));
        $summaryKey = strtolower($row['classification'] === 'Complete' ? $result->status : $row['classification']);
        $this->assertSame(1, $summary['overallSummary'][$summaryKey]);
    }

    public static function parityScenarioProvider(): array
    {
        return array_map(fn ($scenario) => [$scenario], ['normal', 'optional_a_plus', 'optional_f', 'compulsory_f', 'missing', 'zero',
            'fifty_mark', 'theory', 'pair', 'cq_failure', 'mcq_failure', 'practical_failure']);
    }

    public function test_summary_distributions_and_subject_statistics_use_centralized_results(): void
    {
        $scope = $this->scope(); $main = $this->subject('Main', 'Main', 100); $optional = $this->subject('Optional', 'Optional', 100);
        $pass = $this->student($scope, '01', $optional->id); $fail = $this->student($scope, '02'); $missing = $this->student($scope, '03');
        $this->mark($pass, $scope, $main, 80); $this->mark($pass, $scope, $optional, 0); $this->mark($fail, $scope, $main, 0);
        config(['result_engine.summary_enabled' => true]);

        $data = app(MarksheetController::class)->resultSummary($this->request($scope))->getData();
        $stats = collect($data['subjectStats'])->keyBy('subjectName');

        $this->assertSame(1, $data['gpaDistribution']['5.00']);
        $this->assertSame(1, $data['gpaDistribution']['Fail']);
        $this->assertSame(0, $data['gpaDistribution']['Incomplete']);
        $this->assertSame(1, $data['gpaDistribution']['Absent']);
        $this->assertSame(['A+' => 1, 'F' => 1, 'Absent' => 1], $data['gradeDistribution']);
        $this->assertSame(1, $stats['Main']['fail']); $this->assertSame(1, $stats['Main']['missing']);
        $this->assertSame(1, $stats['Optional']['fail']);
        $this->assertSame([1 => 1], $data['failureBuckets']);
    }

    public function test_zero_student_summary_is_safe(): void
    {
        $scope = $this->scope(); config(['result_engine.summary_enabled' => true]);
        $data = app(MarksheetController::class)->resultSummary($this->request($scope))->getData();
        $this->assertSame(0, $data['overallSummary']['total']);
        $this->assertSame(0.0, $data['overallSummary']['passPercentage']);
        $this->assertFalse($data['hasData']);
    }

    public function test_selected_exam_and_academic_filters_prevent_student_or_mark_leakage(): void
    {
        $scope = $this->scope(); $otherExam = new Exam(); $otherExam->examName = 'Other'; $otherExam->passingSystem = 2; $otherExam->save();
        $subject = $this->subject('Scoped Main', 'Main', 100); $student = $this->student($scope, '01'); $outside = $this->student($scope, '02');
        $outside->sectionName = 999; $outside->save(); $this->mark($student, $scope, $subject, 80); $this->mark($student, $scope, $subject, 0, null, null, $otherExam);
        $this->mark($outside, $scope, $subject, 32);
        config(['result_engine.tabulation_enabled' => true, 'result_engine.summary_enabled' => true]);

        $tab = app(MarksheetController::class)->allMarksheet($this->request($scope))->getData();
        $summary = app(MarksheetController::class)->resultSummary($this->request($scope))->getData();

        $this->assertCount(1, $tab['passResults']); $this->assertSame(80.0, $tab['passResults'][0]['subjects'][0]['total']);
        $this->assertSame(1, $summary['overallSummary']['total']);
    }

    public function test_religious_and_assigned_fourth_subject_rules_are_retained(): void
    {
        $scope = $this->scope(); $main = $this->subject('Main', 'Main', 100);
        $religionA = $this->subject('Religion A', 'Main', 100); $religionA->isReligious = true; $religionA->save();
        $religionB = $this->subject('Religion B', 'Main', 100); $religionB->isReligious = true; $religionB->save();
        $optionalA = $this->subject('Optional A', 'Optional', 100); $optionalB = $this->subject('Optional B', 'Optional', 100);
        $student = $this->student($scope, '01', $optionalA->id); $student->religiousSubjectId = $religionA->id; $student->save();
        foreach ([$main, $religionA, $religionB, $optionalA, $optionalB] as $subject) $this->mark($student, $scope, $subject, 80);
        config(['result_engine.tabulation_enabled' => true]);

        $data = app(MarksheetController::class)->allMarksheet($this->request($scope))->getData();
        $names = collect($data['passResults'][0]['subjects'])->pluck('name')->all();

        $this->assertContains('Religion A', $names); $this->assertNotContains('Religion B', $names);
        $this->assertContains('Optional A', $names); $this->assertNotContains('Optional B', $names);
    }

    public function test_tabulation_and_summary_fail_closed_on_batch_exception(): void
    {
        $scope = $this->scope(); $subject = $this->subject('Main', 'Main', 100); $student = $this->student($scope, '01'); $this->mark($student, $scope, $subject, 80);
        $fake = new class(app(\App\Services\ResultCalculation\BoardResultCalculator::class), app(ResultCalculationInputBuilder::class)) extends ResultCalculationBatchBuilder {
            public function build(int $examId, int $classId, int $sessionId, ?int $sectionId = null, ?int $departmentId = null): array { throw new RuntimeException('simulated'); }
        };
        $this->app->instance(ResultCalculationBatchBuilder::class, $fake);
        config(['result_engine.tabulation_enabled' => true, 'result_engine.summary_enabled' => true]);

        $tab = app(MarksheetController::class)->allMarksheet($this->request($scope));
        $summary = app(MarksheetController::class)->resultSummary($this->request($scope));

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $tab);
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $summary);
    }

    public function test_tabulation_and_summary_are_read_only(): void
    {
        $scope = $this->scope(); $main = $this->subject('Main', 'Main', 50); $optional = $this->subject('Optional', 'Optional', 100);
        $student = $this->student($scope, '01', $optional->id); $this->mark($student, $scope, $main, 40, 0, 0); $this->mark($student, $scope, $optional, 80);
        $before = Marksheet::orderBy('id')->get()->map->only(['id', 'subjectMarks', 'objectMarks', 'practicalMarks', 'totalMarks', 'laterGrade', 'gradePoint'])->all();
        config(['result_engine.tabulation_enabled' => true, 'result_engine.summary_enabled' => true]);

        app(MarksheetController::class)->allMarksheet($this->request($scope))->render();
        app(MarksheetController::class)->resultSummary($this->request($scope))->render();

        $this->assertSame($before, Marksheet::orderBy('id')->get()->map->only(['id', 'subjectMarks', 'objectMarks', 'practicalMarks', 'totalMarks', 'laterGrade', 'gradePoint'])->all());
        $this->assertSame($optional->id, $student->fresh()->fourthSubjectId); $this->assertSame(0, Placement::count());
        $this->assertSame(0, ResultArchive::count()); $this->assertSame(0, ResultPublish::count());
    }

    public function test_complete_tabulation_and_summary_render_execute_no_queries(): void
    {
        $scope = $this->scope(); $subject = $this->subject('Main', 'Main', 100);
        $student = $this->student($scope, '01'); $this->mark($student, $scope, $subject, 80);
        $tabulation = app(MarksheetController::class)->allMarksheet($this->request($scope));
        $summary = app(MarksheetController::class)->resultSummary($this->request($scope));
        $queries = [];
        DB::listen(function ($query) use (&$queries): void { $queries[] = $query->sql; });

        $tabulation->render();
        $summary->render();

        $this->assertSame([], $queries);
    }

    public function test_batch_and_presenter_query_count_is_bounded_for_class_sized_scope(): void
    {
        $scope = $this->scope(); $subject = $this->subject('Main', 'Main', 100);
        foreach (range(1, 25) as $roll) {
            $student = $this->student($scope, str_pad((string) $roll, 2, '0', STR_PAD_LEFT));
            $this->mark($student, $scope, $subject, 80);
        }
        DB::enableQueryLog();
        DB::flushQueryLog();
        $batch = app(ResultCalculationBatchBuilder::class)->build(
            $scope['exam']->id, $scope['class']->id, $scope['session']->id, $scope['section']->id
        );
        app(TabulationResultPresenter::class)->present($batch['entries']);
        $classQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(12, $classQueries);
    }

    public function test_exact_active_path_query_counts_for_one_five_and_twenty_five_students(): void
    {
        $metrics = [];
        foreach ([1, 5, 25] as $size) {
            $scope = $this->scope(); $subject = $this->subject('Measured '.$size, 'Main', 100);
            foreach (range(1, $size) as $roll) {
                $student = $this->student($scope, str_pad((string) $roll, 2, '0', STR_PAD_LEFT));
                $this->mark($student, $scope, $subject, 80);
            }
            foreach (['tabulation' => 'allMarksheet', 'glance' => 'atGlanceResult', 'summary' => 'result.summary'] as $label => $routeName) {
                $request = $this->request($scope);
                $request->setRouteResolver(fn () => Route::getRoutes()->getByName($routeName));
                DB::enableQueryLog(); DB::flushQueryLog();
                $response = $label === 'summary'
                    ? app(MarksheetController::class)->resultSummary($request)
                    : app(MarksheetController::class)->allMarksheet($request);
                $preparation = count(DB::getQueryLog());
                DB::flushQueryLog();
                $response->render();
                $metrics[$label][$size] = [$preparation, count(DB::getQueryLog())];
                DB::disableQueryLog();
            }
        }

        $expected = [
            'tabulation' => [1 => [21, 0], 5 => [21, 0], 25 => [21, 0]],
            'glance' => [1 => [21, 0], 5 => [21, 0], 25 => [21, 0]],
            'summary' => [1 => [21, 0], 5 => [21, 0], 25 => [21, 0]],
        ];
        $this->assertSame($expected, $metrics);
    }

    public function test_complete_scope_population_includes_partial_no_mark_and_inactive_students(): void
    {
        $scope = $this->scope(); $first = $this->subject('First', 'Main', 100); $second = $this->subject('Second', 'Main', 100);
        $complete = $this->student($scope, '01'); $partial = $this->student($scope, '02');
        $noMarks = $this->student($scope, '03'); $inactive = $this->student($scope, '04'); $inactive->status = 'inactive'; $inactive->save();
        $outsideSection = $this->student($scope, '05'); $outsideSection->sectionName = 999; $outsideSection->save();
        $this->mark($complete, $scope, $first, 80); $this->mark($complete, $scope, $second, 80);
        $this->mark($partial, $scope, $first, 80);
        $otherExam = new Exam(); $otherExam->examName = 'Other'; $otherExam->passingSystem = 2; $otherExam->save();
        $this->mark($noMarks, $scope, $first, 80, null, null, $otherExam);

        $tab = app(MarksheetController::class)->allMarksheet($this->request($scope))->getData();
        $summary = app(MarksheetController::class)->resultSummary($this->request($scope))->getData();
        $rows = collect($tab['tabulationRows'])->keyBy('student.id');

        $this->assertSame([$complete->id, $partial->id, $noMarks->id, $inactive->id], $rows->keys()->all());
        $this->assertSame('Pass', $rows[$complete->id]['status']);
        $this->assertSame('Incomplete', $rows[$partial->id]['status']);
        $this->assertSame('Incomplete', $rows[$noMarks->id]['status']);
        $this->assertSame('Incomplete', $rows[$inactive->id]['status']);
        $this->assertSame('Incomplete', $rows[$partial->id]['classification']);
        $this->assertSame('Absent', $rows[$noMarks->id]['classification']);
        $this->assertSame('Absent', $rows[$inactive->id]['classification']);
        $this->assertSame(4, $summary['overallSummary']['total']);
    }

    public function test_approved_summary_denominator_contract_for_seventy_ten_twenty(): void
    {
        $rows = [];
        foreach ([['Pass', 'Complete', 70], ['Fail', 'Complete', 10], ['Incomplete', 'Incomplete', 20]] as [$status, $classification, $count]) {
            foreach (range(1, $count) as $index) {
                $rows[] = ['status' => $status, 'classification' => $classification, 'finalGpa' => $status === 'Pass' ? '5.00' : '0.00',
                    'finalLetter' => $status === 'Pass' ? 'A+' : ($status === 'Fail' ? 'F' : 'Incomplete'),
                    'subjectFails' => $status === 'Fail' ? 1 : 0, 'subjects' => []];
            }
        }
        $summary = app(TabulationResultPresenter::class)->summarize($rows, collect());

        $this->assertSame([
            'total' => 100, 'present' => 100, 'absent' => 0, 'pass' => 70, 'fail' => 10, 'incomplete' => 20,
            'passPercentage' => 70.0, 'failPercentage' => 10.0, 'incompletePercentage' => 20.0,
        ], $summary['overallSummary']);
    }

    public function test_route_name_deterministically_selects_each_tabulation_view(): void
    {
        $scope = $this->scope();
        $tabRequest = $this->request($scope);
        $tabRequest->setRouteResolver(fn () => Route::getRoutes()->getByName('allMarksheet'));
        $glanceRequest = $this->request($scope);
        $glanceRequest->setRouteResolver(fn () => Route::getRoutes()->getByName('atGlanceResult'));

        $this->assertSame('result.allMarksheet', app(MarksheetController::class)->allMarksheet($tabRequest)->name());
        $this->assertSame('result.atGlanceResult', app(MarksheetController::class)->allMarksheet($glanceRequest)->name());
    }

    public function test_expected_subject_columns_remain_aligned_when_marks_are_missing_and_pairs_merge(): void
    {
        $scope = $this->scope();
        $missing = $this->subject('Expected Missing', 'Main', 100);
        $paperOne = $this->subject('Bangla 1st Paper', 'Main', 100, 0, 0, 'bangla_1st_paper');
        $paperTwo = $this->subject('Bangla 2nd Paper', 'Main', 100, 0, 0, 'bangla_2nd_paper');
        $first = $this->student($scope, '01'); $second = $this->student($scope, '02');
        $this->mark($first, $scope, $paperOne, 80); $this->mark($first, $scope, $paperTwo, 80);
        $this->mark($second, $scope, $paperOne, 70); $this->mark($second, $scope, $paperTwo, 70);

        $data = app(MarksheetController::class)->allMarksheet($this->request($scope))->getData();
        $columns = $data['subjects']->pluck('subjectName')->all();

        $this->assertNotContains($missing->subjectName, $columns);
        $this->assertCount(2, $data['tabulationRows']);
        $this->assertSame(array_keys($data['tabulationRows'][0]['cells']), array_keys($data['tabulationRows'][1]['cells']));
        $this->assertCount(1, $data['tabulationRows'][0]['subjects']);
    }

    public function test_presenter_subject_column_order_keeps_common_before_science_and_science_sequence(): void
    {
        $scope = $this->scope();
        $bangla1 = $this->subject('Bangla 1st Paper', 'Main', 100, 0, 0, 'bangla_1st_paper');
        $bangla2 = $this->subject('Bangla 2nd Paper', 'Main', 100, 0, 0, 'bangla_2nd_paper');
        $english1 = $this->subject('English 1st Paper', 'Main', 100, 0, 0, 'english_1st_paper');
        $english2 = $this->subject('English 2nd Paper', 'Main', 100, 0, 0, 'english_2nd_paper');
        $math = $this->subject('Mathematics', 'Main', 100);
        $ict = $this->subject('ICT', 'Main', 100);
        $bgs = $this->subject('Bangladesh and Global Studies', 'Main', 100);
        $religion = $this->subject('Islam and moral education-111', 'Main', 100); $religion->isReligious = true; $religion->save();
        $physics = $this->subject('Physics', 'Main', 100);
        $chemistry = $this->subject('Chemistry', 'Main', 100);
        $biology = $this->subject('Biology', 'Main', 100);

        $student = $this->student($scope, '01');
        $student->religiousSubjectId = $religion->id;
        $student->save();

        foreach ([
            [$bangla1, 1], [$bangla2, 2], [$english1, 3], [$english2, 4], [$math, 5],
            [$ict, 6], [$bgs, 7], [$religion, 8], [$physics, 20], [$chemistry, 21], [$biology, 22],
        ] as [$subject, $order]) {
            $this->mapWithOrder($scope, $subject, $order);
            $this->mark($student, $scope, $subject, 80);
        }

        $data = app(MarksheetController::class)->allMarksheet($this->request($scope))->getData();
        $columns = collect($data['subjects'])->pluck('subjectName')->values()->all();

        $this->assertSame(['Bangla', 'English', 'Mathematics', 'ICT', 'Bangladesh and Global Studies', 'Islam and moral education-111', 'Physics', 'Chemistry', 'Biology'], $columns);
    }

    public function test_summary_pagination_metadata_uses_full_scope_aggregates(): void
    {
        $scope = $this->scope(); $student = $this->student($scope, '01');
        foreach (range(1, 23) as $index) {
            $subject = $this->subject('Long Subject '.$index, 'Main', 100);
            $this->mark($student, $scope, $subject, 80);
        }
        $data = app(MarksheetController::class)->resultSummary($this->request($scope))->getData();

        $this->assertCount(2, $data['summaryView']['subjectPages']);
        $this->assertSame(1, $data['overallSummary']['total']);
        $this->assertSame(1, $data['overallSummary']['pass']);
        $this->assertSame(23, collect($data['summaryView']['subjectPages'])->sum(fn ($page) => count($page['subjectRows'])));
    }

    public function test_current_presenter_supplies_the_central_merit_contract(): void
    {
        $scope = $this->scope(); $subject = $this->subject('Main', 'Main', 100);
        $secondRoll = $this->student($scope, '02'); $firstRoll = $this->student($scope, '01');
        $failed = $this->student($scope, '03'); $incomplete = $this->student($scope, '04');
        $this->mark($secondRoll, $scope, $subject, 80); $this->mark($firstRoll, $scope, $subject, 80);
        $this->mark($failed, $scope, $subject, 0);

        $data = app(MarksheetController::class)->allMarksheet($this->request($scope))->getData();

        $this->assertSame(['01', '02', '03', '04'], collect($data['tabulationRows'])->pluck('studentIdentity.roll')->all());
        foreach ($data['tabulationRows'] as $row) {
            $this->assertArrayHasKey('meritPosition', $row);
            $this->assertArrayNotHasKey('classMerit', $row);
        }
    }

    public function test_subject_wise_and_at_glance_sections_are_exclusive_and_ordered(): void
    {
        $scope = $this->scope();
        $first = $this->subject('Required One', 'Main', 100);
        $second = $this->subject('Required Two', 'Main', 100);
        $optional = $this->subject('Fourth Subject', 'Optional', 100);
        $pass = $this->student($scope, '01', $optional->id);
        $failTwo = $this->student($scope, '02');
        $incomplete = $this->student($scope, '03');
        $absent = $this->student($scope, '04');
        foreach ([$first, $second] as $subject) $this->mark($pass, $scope, $subject, 80);
        $this->mark($pass, $scope, $optional, 0);
        foreach ([$first, $second] as $subject) $this->mark($failTwo, $scope, $subject, 0);
        $this->mark($incomplete, $scope, $first, 80);

        $data = app(MarksheetController::class)->allMarksheet($this->request($scope))->getData();
        $ids = collect($data['reportSections'])->flatten(1)->pluck('studentIdentity.id');

        $this->assertSame(['Pass', 'Fail', 'Incomplete', 'Absent'], collect($data['glanceRows'])->pluck('reportStatus')->unique()->values()->all());
        $this->assertCount(1, $data['reportSections']['Pass']);
        $this->assertCount(1, $data['failedGroups'][2]);
        $this->assertCount(1, $data['reportSections']['Incomplete']);
        $this->assertCount(1, $data['reportSections']['Absent']);
        $this->assertSame(4, $ids->unique()->count());
        $this->assertSame(4, $ids->count());
        $this->assertSame(0, $data['reportSections']['Pass'][0]['subjectFails']);
    }

    public function test_rendered_views_restore_a4_pages_wrappers_headers_signatures_and_include_merit(): void
    {
        $scope = $this->scope(); $subject = $this->subject('Professional Long Subject Name', 'Main', 100);
        $student = $this->student($scope, '01'); $this->mark($student, $scope, $subject, 80);
        $tabRequest = $this->request($scope);
        $tabRequest->setRouteResolver(fn () => Route::getRoutes()->getByName('allMarksheet'));
        $glanceRequest = $this->request($scope);
        $glanceRequest->setRouteResolver(fn () => Route::getRoutes()->getByName('atGlanceResult'));
        $tabHtml = app(MarksheetController::class)->allMarksheet($tabRequest)->render();
        $glanceHtml = app(MarksheetController::class)->allMarksheet($glanceRequest)->render();
        $summaryHtml = app(MarksheetController::class)->resultSummary($this->request($scope))->render();

        $this->assertStringContainsString('@page { size: A4 landscape', $tabHtml);
        $this->assertStringContainsString('@page{size:A4 landscape', $glanceHtml);
        $this->assertStringContainsString('@page{size:A4 portrait', $summaryHtml);
        foreach ([$tabHtml, $glanceHtml, $summaryHtml] as $html) {
            $this->assertStringContainsString('result-print-page', $html);
            $this->assertStringContainsString('page-break-after:auto', $html);
            $this->assertStringContainsString('table-header-group', $html);
            $this->assertStringContainsString('Class Teacher', $html);
            $this->assertStringContainsString('Principal/Head Master', $html);
            $this->assertStringNotContainsString('Class Merit', $html);
            if ($html !== $summaryHtml) $this->assertStringContainsString('Merit Position', $html);
        }
    }

    public function test_glance_component_columns_are_subject_specific_and_zero_differs_from_missing(): void
    {
        $scope = $this->scope();
        $cqOnly = $this->subject('CQ Only', 'Main', 100);
        $cqMcq = $this->subject('CQ MCQ', 'Main', 70, 30);
        $all = $this->subject('All Components', 'Main', 50, 25, 25);
        $student = $this->student($scope, '01');
        $this->mark($student, $scope, $cqOnly, 0);
        $this->mark($student, $scope, $cqMcq, 40, 20);
        $request = $this->request($scope);
        $request->setRouteResolver(fn () => Route::getRoutes()->getByName('atGlanceResult'));
        $response = app(MarksheetController::class)->allMarksheet($request);
        $data = $response->getData();
        $columns = $data['subjects']->keyBy('subjectName');
        $html = $response->render();

        $this->assertSame(['CQ'], collect($columns['CQ Only']->componentColumns)->pluck('label')->all());
        $this->assertSame(['CQ', 'MCQ'], collect($columns['CQ MCQ']->componentColumns)->pluck('label')->all());
        $this->assertArrayNotHasKey('All Components', $columns->all());
        $this->assertSame(0.0, $data['tabulationRows'][0]['cells']['CQ Only']['cq']);
        $this->assertArrayNotHasKey('All Components', $data['tabulationRows'][0]['cells']);
        $this->assertStringContainsString('CQ Only', $html);
    }

    public function test_summary_print_uses_prepared_pages_with_page_numbers_and_full_totals(): void
    {
        $scope = $this->scope(); $student = $this->student($scope, '01');
        foreach (range(1, 23) as $index) {
            $subject = $this->subject('Paged Subject '.$index, 'Main', 100);
            $this->mark($student, $scope, $subject, 80);
        }
        $html = app(MarksheetController::class)->resultSummary($this->request($scope))->render();

        $this->assertSame(2, substr_count($html, '<section class="result-print-page">'));
        $this->assertStringContainsString('Page 1 of 2', $html);
        $this->assertStringContainsString('Page 2 of 2', $html);
        $this->assertGreaterThanOrEqual(2, substr_count($html, '<td>1</td><td>1</td><td>0</td>'));
    }

    private function scenario(string $scenario): array
    {
        $scope = $this->scope(str_ends_with($scenario, '_failure') ? 1 : 2); $student = $this->student($scope, '01');
        if ($scenario === 'pair') { $a = $this->subject('Bangla 1st Paper', 'Main', 100, 0, 0, 'bangla_1st_paper'); $b = $this->subject('Bangla 2nd Paper', 'Main', 100, 0, 0, 'bangla_2nd_paper'); $this->mark($student, $scope, $a, 80); $this->mark($student, $scope, $b, 80); }
        elseif (str_ends_with($scenario, '_failure')) { $subject = $this->subject('Component', 'Main', 50, 25, 25); $marks = ['cq' => 30, 'mcq' => 15, 'practical' => 15]; $marks[str_replace('_failure', '', $scenario)] = 1; $this->mark($student, $scope, $subject, $marks['cq'], $marks['mcq'], $marks['practical']); }
        else { $main = $this->subject('Scenario Main', $scenario === 'theory' ? 'Theory' : 'Main', $scenario === 'fifty_mark' ? 50 : 100); if ($scenario !== 'missing') $this->mark($student, $scope, $main, match($scenario){'compulsory_f'=>32,'zero'=>0,'fifty_mark'=>40,default=>80}); if(in_array($scenario,['optional_a_plus','optional_f'])){$optional=$this->subject('Scenario Optional','Optional',100);$student->fourthSubjectId=$optional->id;$student->save();$this->mark($student,$scope,$optional,$scenario==='optional_f'?0:80);} }
        return [$student, $scope];
    }

    private function rowData(array $presented): array { return collect(array_merge($presented['mainRows'], $presented['optionalRows']))->map(fn($r)=>[$r['name'],$r['total'],$r['grade'],$r['gradePoint'],$r['status'],$r['componentFailures']])->all(); }
    private function request(array $scope): Request { return Request::create('/marksheet/all','GET',['examId'=>$scope['exam']->id,'classId'=>$scope['class']->id,'sessionId'=>$scope['session']->id,'sectionId'=>$scope['section']->id]); }
    private function scope(int $passingSystem=2): array { $session=new sessionManage();$session->session='2026';$session->save();$class=new classManage();$class->className='Class 10';$class->save();$section=new sectionManage();$section->section='A';$section->save();$department=new Department();$department->departmentName='Science';$department->save();$exam=new Exam();$exam->examName='Annual';$exam->passingSystem=$passingSystem;$exam->save();return compact('session','class','section','department','exam'); }
    private function student(array $scope,string $roll,?int $fourth=null): newAdmission { $s=new newAdmission();$s->stdId=(string)random_int(100000,999999999);$s->fullName='Output Student';$s->sessName=$scope['session']->id;$s->className=$scope['class']->id;$s->sectionName=$scope['section']->id;$s->departmentName=$scope['department']->id;$s->rollNumber=$roll;$s->fourthSubjectId=$fourth;$s->save();return $s; }
    private function subject(string $name,string $type,float $cq,float $mcq=0,float $pr=0,?string $alias=null): Subject { $s=new Subject();$s->subjectName=$name;$s->alias=$alias??strtolower(str_replace(' ','_',$name));$s->subjectType=$type;$s->assign_class='0';$s->CQ=$cq;$s->MCQ=$mcq;$s->Practical=$pr;$s->save();return $s; }
    private function mark(newAdmission $student,array $scope,Subject $subject,float $cq,?float $mcq=null,?float $pr=null,?Exam $exam=null): Marksheet { $this->ensureCurriculumMapping($scope,$subject); return Marksheet::create(['studentId'=>$student->id,'classId'=>$scope['class']->id,'sessionId'=>$scope['session']->id,'groupId'=>$scope['section']->id,'examId'=>($exam??$scope['exam'])->id,'subjectId'=>$subject->id,'subjectMarks'=>$cq,'objectMarks'=>$mcq,'practicalMarks'=>$pr,'totalMarks'=>$cq+($mcq??0)+($pr??0),'gradePoint'=>99,'laterGrade'=>'Stored']); }
    private function ensureCurriculumMapping(array $scope, Subject $subject): void { $exists=DB::table('curriculum_subject_mappings')->where('session_id',(string)$scope['session']->id)->where('class_id',(string)$scope['class']->id)->where('section_id',(string)$scope['section']->id)->where('department_id',(string)$scope['department']->id)->where('subject_id',(int)$subject->id)->exists(); if($exists) return; $next=(int)(DB::table('curriculum_subject_mappings')->where('session_id',(string)$scope['session']->id)->where('class_id',(string)$scope['class']->id)->where('section_id',(string)$scope['section']->id)->where('department_id',(string)$scope['department']->id)->max('sort_order')??0)+1; DB::table('curriculum_subject_mappings')->insert(['session_id'=>(string)$scope['session']->id,'class_id'=>(string)$scope['class']->id,'section_id'=>(string)$scope['section']->id,'department_id'=>(string)$scope['department']->id,'subject_id'=>(int)$subject->id,'mapping_type'=>'main','sort_order'=>$next,'is_active'=>1,'source'=>'test-fixture','created_at'=>now(),'updated_at'=>now()]); }

    private function mapWithOrder(array $scope, Subject $subject, int $sortOrder): void
    {
        DB::table('curriculum_subject_mappings')
            ->where('session_id', (string) $scope['session']->id)
            ->where('class_id', (string) $scope['class']->id)
            ->where('section_id', (string) $scope['section']->id)
            ->where('department_id', (string) $scope['department']->id)
            ->where('subject_id', (int) $subject->id)
            ->delete();

        DB::table('curriculum_subject_mappings')->insert([
            'session_id' => (string) $scope['session']->id,
            'class_id' => (string) $scope['class']->id,
            'section_id' => (string) $scope['section']->id,
            'department_id' => (string) $scope['department']->id,
            'subject_id' => (int) $subject->id,
            'mapping_type' => 'main',
            'sort_order' => $sortOrder,
            'is_active' => 1,
            'source' => 'test-fixture',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
