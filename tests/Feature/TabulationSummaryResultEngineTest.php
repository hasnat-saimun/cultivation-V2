<?php

namespace Tests\Feature;

use App\Http\Controllers\MarksheetController;
use App\Models\classManage;
use App\Models\Exam;
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

    public function test_disabled_flags_preserve_legacy_tabulation_and_summary(): void
    {
        config(['result_engine.tabulation_enabled' => false, 'result_engine.summary_enabled' => false]);
        $scope = $this->scope(); $main = $this->subject('Main', 'Main', 100); $optional = $this->subject('Optional', 'Optional', 100);
        $student = $this->student($scope, '01', $optional->id); $this->mark($student, $scope, $main, 80); $this->mark($student, $scope, $optional, 80);

        $tab = app(MarksheetController::class)->allMarksheet($this->request($scope))->getData();
        $summary = app(MarksheetController::class)->resultSummary($this->request($scope))->getData();

        $this->assertSame('8.00', $tab['passResults'][0]['finalGpa']);
        $this->assertSame(1, $summary['overallSummary']['pass']);
        $this->assertArrayNotHasKey('usingCentralizedTabulation', $tab);
        $this->assertArrayNotHasKey('usingCentralizedSummary', $summary);
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

        $this->assertCount(2, $tab['passResults']); $this->assertCount(1, $tab['failResults']); $this->assertCount(1, $tab['incompleteResults']);
        $this->assertSame('5.00', $tab['passResults'][0]['finalGpa']);
        $this->assertSame(0, $tab['passResults'][1]['subjectFails']);
        $this->assertSame(['total' => 4, 'present' => 3, 'absent' => 1, 'pass' => 2, 'fail' => 1, 'incomplete' => 1,
            'passPercentage' => 50.0, 'failPercentage' => 25.0, 'incompletePercentage' => 25.0], $summary['overallSummary']);
    }

    public function test_tabulation_and_summary_flags_are_independent(): void
    {
        $scope = $this->scope(); $main = $this->subject('Main', 'Main', 100); $optional = $this->subject('Optional', 'Optional', 100);
        $student = $this->student($scope, '01', $optional->id); $this->mark($student, $scope, $main, 80); $this->mark($student, $scope, $optional, 0);

        config(['result_engine.tabulation_enabled' => true, 'result_engine.summary_enabled' => false]);
        $centralTab = app(MarksheetController::class)->allMarksheet($this->request($scope))->getData();
        $legacySummary = app(MarksheetController::class)->resultSummary($this->request($scope))->getData();
        $this->assertCount(1, $centralTab['passResults']);
        $this->assertSame(1, $legacySummary['overallSummary']['fail']);

        config(['result_engine.tabulation_enabled' => false, 'result_engine.summary_enabled' => true]);
        $legacyTab = app(MarksheetController::class)->allMarksheet($this->request($scope))->getData();
        $centralSummary = app(MarksheetController::class)->resultSummary($this->request($scope))->getData();
        $this->assertCount(1, $legacyTab['failResults']);
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
        $this->assertSame(1, $summary['overallSummary'][strtolower($result->status)]);
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
        $this->assertSame(1, $data['gpaDistribution']['Incomplete']);
        $this->assertSame(['A+' => 1, 'F' => 1, 'Incomplete' => 1], $data['gradeDistribution']);
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

    public function test_tabulation_and_summary_use_complete_legacy_fallback_on_batch_exception(): void
    {
        $scope = $this->scope(); $subject = $this->subject('Main', 'Main', 100); $student = $this->student($scope, '01'); $this->mark($student, $scope, $subject, 80);
        $fake = new class(app(\App\Services\ResultCalculation\BoardResultCalculator::class), app(ResultCalculationInputBuilder::class)) extends ResultCalculationBatchBuilder {
            public function build(int $examId, int $classId, int $sessionId, ?int $sectionId = null, ?int $departmentId = null): array { throw new RuntimeException('simulated'); }
        };
        $this->app->instance(ResultCalculationBatchBuilder::class, $fake);
        config(['result_engine.tabulation_enabled' => true, 'result_engine.summary_enabled' => true]);

        $tab = app(MarksheetController::class)->allMarksheet($this->request($scope))->getData();
        $summary = app(MarksheetController::class)->resultSummary($this->request($scope))->getData();

        $this->assertSame('5.00', $tab['passResults'][0]['finalGpa']);
        $this->assertSame(1, $summary['overallSummary']['pass']);
        $this->assertArrayNotHasKey('usingCentralizedTabulation', $tab);
        $this->assertArrayNotHasKey('usingCentralizedSummary', $summary);
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
    private function scope(int $passingSystem=2): array { $session=new sessionManage();$session->session='2026';$session->save();$class=new classManage();$class->className='Class 10';$class->save();$section=new sectionManage();$section->section='A';$section->save();$exam=new Exam();$exam->examName='Annual';$exam->passingSystem=$passingSystem;$exam->save();return compact('session','class','section','exam'); }
    private function student(array $scope,string $roll,?int $fourth=null): newAdmission { $s=new newAdmission();$s->stdId=(string)random_int(100000,999999999);$s->fullName='Output Student';$s->sessName=$scope['session']->id;$s->className=$scope['class']->id;$s->sectionName=$scope['section']->id;$s->rollNumber=$roll;$s->fourthSubjectId=$fourth;$s->save();return $s; }
    private function subject(string $name,string $type,float $cq,float $mcq=0,float $pr=0,?string $alias=null): Subject { $s=new Subject();$s->subjectName=$name;$s->alias=$alias??strtolower(str_replace(' ','_',$name));$s->subjectType=$type;$s->assign_class='0';$s->CQ=$cq;$s->MCQ=$mcq;$s->Practical=$pr;$s->save();return $s; }
    private function mark(newAdmission $student,array $scope,Subject $subject,float $cq,?float $mcq=null,?float $pr=null,?Exam $exam=null): Marksheet { return Marksheet::create(['studentId'=>$student->id,'classId'=>$scope['class']->id,'sessionId'=>$scope['session']->id,'groupId'=>$scope['section']->id,'examId'=>($exam??$scope['exam'])->id,'subjectId'=>$subject->id,'subjectMarks'=>$cq,'objectMarks'=>$mcq,'practicalMarks'=>$pr,'totalMarks'=>$cq+($mcq??0)+($pr??0),'gradePoint'=>99,'laterGrade'=>'Stored']); }
}
