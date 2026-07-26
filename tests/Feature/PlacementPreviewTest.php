<?php

namespace Tests\Feature;

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
use App\Services\ResultCalculation\PlacementPreviewBuilder;
use App\Services\ResultCalculation\ResultCalculationBatchBuilder;
use App\Services\ResultCalculation\TabulationResultPresenter;
use App\Services\ResultCalculation\TranscriptResultPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PlacementPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=', 'cache.default' => 'array']);
    }

    public function test_legacy_recalculation_uses_stored_rows_optional_and_unique_ordinal_roll_tiebreak(): void
    {
        $scope = $this->scope(); $main = $this->subject('Main', 'Main', 100); $optional = $this->subject('Optional', 'Optional', 100);
        $rollFive = $this->student($scope, '05', $optional->id); $rollTwo = $this->student($scope, '02', $optional->id);
        foreach ([$rollFive, $rollTwo] as $student) { $this->mark($student, $scope, $main, 80, 5); $this->mark($student, $scope, $optional, 0, 0); }

        $this->post(route('placements.recalculate'), $this->scopePayload($scope))->assertRedirect();
        $rows = Placement::orderBy('position')->get();

        $this->assertSame($rollTwo->id, (int) $rows[0]->studentId);
        $this->assertSame([1, 2], $rows->pluck('position')->map(fn ($v) => (int) $v)->all());
        $this->assertSame(2, (int) $rows[0]->subjectsCount);
        $this->assertSame(2.5, (float) $rows[0]->gpa);
        $this->assertSame(80, (int) $rows[0]->totalMarks);
        $this->assertSame('Fail', $rows[0]->status);

        $this->post(route('placements.recalculate'), $this->scopePayload($scope))->assertRedirect();
        $this->assertDatabaseCount('exam_placements', 2);
    }

    public function test_legacy_recalculation_excludes_other_exam_marks(): void
    {
        $scope = $this->scope(); $other = new Exam(); $other->examName = 'Other'; $other->passingSystem = 2; $other->save();
        $subject = $this->subject('Main', 'Main', 100); $student = $this->student($scope, '01');
        $this->mark($student, $scope, $subject, 80, 5); $this->mark($student, $scope, $subject, 0, 0, null, null, $other);

        $this->post(route('placements.recalculate'), $this->scopePayload($scope))->assertRedirect();
        $placement = Placement::firstOrFail();

        $this->assertSame(5.0, (float) $placement->gpa);
        $this->assertSame(1, (int) $placement->subjectsCount);
        $this->assertSame(80, (int) $placement->totalMarks);
    }

    public function test_preview_compares_legacy_and_centralized_eligibility_and_ranks(): void
    {
        $scope = $this->scope(); $main = $this->subject('Main', 'Main', 100); $optional = $this->subject('Optional', 'Optional', 100);
        $pass = $this->student($scope, '03', $optional->id); $optionalFail = $this->student($scope, '02', $optional->id);
        $compulsoryFail = $this->student($scope, '01'); $incomplete = $this->student($scope, '04');
        $this->mark($pass, $scope, $main, 80, 5); $this->mark($pass, $scope, $optional, 80, 5);
        $this->mark($optionalFail, $scope, $main, 80, 5); $this->mark($optionalFail, $scope, $optional, 0, 0);
        $this->mark($compulsoryFail, $scope, $main, 32, 0);
        $preview = $this->preview($scope); $rows = collect($preview['rows'])->keyBy('studentId');

        $this->assertSame(5.0, $rows[$pass->id]['centralizedGpa']);
        $this->assertSame(1, $rows[$pass->id]['centralizedCompulsorySubjectCount']);
        $this->assertSame(3.0, $rows[$pass->id]['optionalBonus']);
        $this->assertSame('Pass', $rows[$optionalFail->id]['centralizedStatus']);
        $this->assertNotNull($rows[$optionalFail->id]['previewRank']);
        $this->assertSame('Fail', $rows[$compulsoryFail->id]['centralizedStatus']);
        $this->assertNull($rows[$compulsoryFail->id]['previewRank']);
        $this->assertSame('Incomplete', $rows[$incomplete->id]['centralizedStatus']);
        $this->assertNull($rows[$incomplete->id]['previewRank']);
        $this->assertContains('OPTIONAL_F_NO_LONGER_FAILS', $rows[$optionalFail->id]['reasons']);
    }

    public function test_preview_uses_current_ordinal_tie_policy_for_equal_academic_inputs(): void
    {
        $scope = $this->scope(); $subject = $this->subject('Main', 'Main', 100);
        $rollFive = $this->student($scope, '05'); $rollTwo = $this->student($scope, '02');
        $this->mark($rollFive, $scope, $subject, 80, 5); $this->mark($rollTwo, $scope, $subject, 80, 5);
        $rows = collect($this->preview($scope)['rows'])->keyBy('studentId');

        $this->assertSame(1, $rows[$rollTwo->id]['previewRank']);
        $this->assertSame(2, $rows[$rollFive->id]['previewRank']);
    }

    #[DataProvider('centralizedScenarioProvider')]
    public function test_preview_reuses_cross_output_centralized_result(string $scenario): void
    {
        [$student, $scope] = $this->scenario($scenario);
        $batch = app(ResultCalculationBatchBuilder::class)->build($scope['exam']->id, $scope['class']->id, $scope['session']->id, $scope['section']->id);
        $entry = $batch['entries'][$student->id]; $student = $entry['student']; $result = $entry['result'];
        $single = app(TranscriptResultPresenter::class)->present($result, $entry['subjects'], $student->marksheet);
        $bulk = app(BulkTranscriptResultBuilder::class)->build([$student], $scope['exam'])[0]['result'];
        $tab = app(TabulationResultPresenter::class)->present($batch['entries'])['rows'][0];
        $preview = $this->preview($scope)['rows'][0];

        $this->assertSame($single['gpa'], $preview['centralizedGpa']);
        $this->assertSame($bulk['status'], $preview['centralizedStatus']);
        $this->assertSame($tab['optionalBonus'], $preview['optionalBonus']);
        $this->assertSame($result->failedCompulsorySubjects, $preview['_result']->failedCompulsorySubjects);
        $this->assertSame($result->missingCompulsorySubjects, $preview['_result']->missingCompulsorySubjects);
        $this->assertSame(array_map(fn ($item) => $item->gradePoint, $result->subjectResults), array_map(fn ($item) => $item->gradePoint, $preview['_result']->subjectResults));
        $this->assertSame(array_map(fn ($item) => $item->componentFailures, $result->subjectResults), array_map(fn ($item) => $item->componentFailures, $preview['_result']->subjectResults));
    }

    public static function centralizedScenarioProvider(): array
    {
        return array_map(fn ($scenario) => [$scenario], ['gpa_cap', 'optional_bonus', 'optional_f', 'compulsory_f', 'missing', 'theory',
            'fifty_mark', 'pair', 'cq_failure', 'mcq_failure', 'practical_failure', 'zero']);
    }

    public function test_preview_scopes_exam_student_academic_context_and_existing_placements(): void
    {
        $scope = $this->scope(); $otherExam = new Exam(); $otherExam->examName = 'Other'; $otherExam->passingSystem = 2; $otherExam->save();
        $subject = $this->subject('Main', 'Main', 100); $selected = $this->student($scope, '01'); $otherStudent = $this->student($scope, '02');
        $otherStudent->sectionName = 999; $otherStudent->save();
        $this->mark($selected, $scope, $subject, 80, 5); $this->mark($selected, $scope, $subject, 0, 0, null, null, $otherExam); $this->mark($otherStudent, $scope, $subject, 32, 0);
        $this->placement($selected, $scope, 1); $this->placement($selected, $scope, 9, $otherExam);

        $preview = app(PlacementPreviewBuilder::class)->build($scope['exam']->id, $scope['class']->id, $scope['session']->id, $scope['section']->id, null, $selected->id, 10);

        $this->assertCount(1, $preview['rows']);
        $this->assertSame(80.0, $preview['rows'][0]['legacyTotalMarks']);
        $this->assertSame(1, $preview['summary']['existingPlacementsFound']);
        $this->assertSame($selected->id, $preview['rows'][0]['studentId']);
    }

    public function test_preview_reports_duplicate_placements_and_rank_changes_without_duplicate_marks(): void
    {
        $scope = $this->scope(); $subject = $this->subject('Main', 'Main', 100); $student = $this->student($scope, '01');
        $this->mark($student, $scope, $subject, 80, 5);
        $this->placement($student, $scope, 7, null, true); $this->placement($student, $scope, 8, null, true);

        $preview = app(PlacementPreviewBuilder::class)->build($scope['exam']->id,$scope['class']->id,$scope['session']->id); $row = $preview['rows'][0];

        $this->assertNotContains('DUPLICATE_MARKS_ROWS', $row['reasons']);
        $this->assertSame(1, $preview['summary']['duplicatePlacementRows']);
        $this->assertTrue($row['rankChanged']);
    }

    public function test_command_requires_scope_outputs_differences_and_summary(): void
    {
        $this->artisan('result-engine:placement-preview')->assertExitCode(2)->expectsOutputToContain('--exam, --class and --session');
        $scope = $this->scope(); $subject = $this->subject('Main', 'Main', 100); $student = $this->student($scope, '01'); $this->mark($student, $scope, $subject, 80, 1);

        $this->artisan('result-engine:placement-preview', ['--exam' => $scope['exam']->id, '--class' => $scope['class']->id,
            '--session' => $scope['session']->id, '--section' => $scope['section']->id])
            ->assertSuccessful()->expectsOutputToContain('GPA diff')->expectsOutputToContain((string) $student->id);
    }

    public function test_preview_and_command_are_strictly_read_only(): void
    {
        $scope = $this->scope(); $main = $this->subject('Main', 'Main', 100); $optional = $this->subject('Optional', 'Optional', 100);
        $student = $this->student($scope, '01', $optional->id); $this->mark($student, $scope, $main, 80, 5); $this->mark($student, $scope, $optional, 80, 5); $this->placement($student, $scope, 4);
        $marksBefore = Marksheet::orderBy('id')->get()->map->only(['id','subjectMarks','objectMarks','practicalMarks','totalMarks','laterGrade','gradePoint'])->all();
        $placementsBefore = Placement::orderBy('id')->get()->toArray();

        $this->preview($scope);
        $this->artisan('result-engine:placement-preview', ['--exam'=>$scope['exam']->id,'--class'=>$scope['class']->id,'--session'=>$scope['session']->id,'--section'=>$scope['section']->id])->assertSuccessful();

        $this->assertSame($marksBefore, Marksheet::orderBy('id')->get()->map->only(['id','subjectMarks','objectMarks','practicalMarks','totalMarks','laterGrade','gradePoint'])->all());
        $this->assertSame($placementsBefore, Placement::orderBy('id')->get()->toArray());
        $this->assertSame($optional->id, $student->fresh()->fourthSubjectId);
        $this->assertSame(0, ResultArchive::count()); $this->assertSame(0, ResultPublish::count());
    }

    private function scenario(string $scenario): array
    {
        $scope=$this->scope(str_ends_with($scenario,'_failure')?1:2);$student=$this->student($scope,'01');
        if($scenario==='pair'){$a=$this->subject('Bangla 1st Paper','Main',100,0,0,'bangla_1st_paper');$b=$this->subject('Bangla 2nd Paper','Main',100,0,0,'bangla_2nd_paper');$this->mark($student,$scope,$a,80,5);$this->mark($student,$scope,$b,80,5);}
        elseif(str_ends_with($scenario,'_failure')){$s=$this->subject('Component','Main',50,25,25);$m=['cq'=>30,'mcq'=>15,'practical'=>15];$m[str_replace('_failure','',$scenario)]=1;$this->mark($student,$scope,$s,$m['cq'],5,$m['mcq'],$m['practical']);}
        else{$main=$this->subject('Scenario Main',$scenario==='theory'?'Theory':'Main',$scenario==='fifty_mark'?50:100);if($scenario!=='missing')$this->mark($student,$scope,$main,match($scenario){'compulsory_f'=>32,'zero'=>0,'fifty_mark'=>40,default=>80},5);if(in_array($scenario,['gpa_cap','optional_bonus','optional_f'])){$o=$this->subject('Scenario Optional','Optional',100);$student->fourthSubjectId=$o->id;$student->save();$this->mark($student,$scope,$o,$scenario==='optional_f'?0:80,$scenario==='optional_f'?0:5);}}
        return[$student,$scope];
    }

    private function preview(array $scope): array { return app(PlacementPreviewBuilder::class)->build($scope['exam']->id,$scope['class']->id,$scope['session']->id,$scope['section']->id); }
    private function scopePayload(array $scope): array { return ['sessionId'=>$scope['session']->id,'classId'=>$scope['class']->id,'examId'=>$scope['exam']->id,'groupId'=>$scope['section']->id]; }
    private function scope(int $passing=2): array {$session=new sessionManage();$session->session='2026';$session->save();$class=new classManage();$class->className='Class 8';$class->save();$section=new sectionManage();$section->section='A';$section->save();$exam=new Exam();$exam->examName='Annual';$exam->passingSystem=$passing;$exam->save();return compact('session','class','section','exam');}
    private function student(array $scope,string $roll,?int $fourth=null):newAdmission{$s=new newAdmission();$s->stdId=(string)random_int(100000,999999999);$s->fullName='Preview Student';$s->sessName=$scope['session']->id;$s->className=$scope['class']->id;$s->sectionName=$scope['section']->id;$s->rollNumber=$roll;$s->fourthSubjectId=$fourth;$s->save();return $s;}
    private function subject(string $name,string $type,float $cq,float $mcq=0,float $pr=0,?string $alias=null):Subject{$s=new Subject();$s->subjectName=$name;$s->alias=$alias??strtolower(str_replace(' ','_',$name));$s->subjectType=$type;$s->assign_class='0';$s->CQ=$cq;$s->MCQ=$mcq;$s->Practical=$pr;$s->save();return $s;}
    private function mark(newAdmission $student,array $scope,Subject $subject,float $cq,float $gp,?float $mcq=null,?float $pr=null,?Exam $exam=null):Marksheet{$this->ensureCurriculumMapping($scope,$subject);return Marksheet::create(['studentId'=>$student->id,'classId'=>$scope['class']->id,'sessionId'=>$scope['session']->id,'groupId'=>$scope['section']->id,'examId'=>($exam??$scope['exam'])->id,'subjectId'=>$subject->id,'subjectMarks'=>$cq,'objectMarks'=>$mcq,'practicalMarks'=>$pr,'totalMarks'=>$cq+($mcq??0)+($pr??0),'gradePoint'=>$gp,'laterGrade'=>$gp>0?'Stored':'F']);}
    private function ensureCurriculumMapping(array $scope, Subject $subject): void {$exists=\Illuminate\Support\Facades\DB::table('curriculum_subject_mappings')->where('session_id',(string)$scope['session']->id)->where('class_id',(string)$scope['class']->id)->where('section_id',(string)$scope['section']->id)->whereNull('department_id')->where('subject_id',(int)$subject->id)->exists(); if($exists)return; $next=(int)(\Illuminate\Support\Facades\DB::table('curriculum_subject_mappings')->where('session_id',(string)$scope['session']->id)->where('class_id',(string)$scope['class']->id)->where('section_id',(string)$scope['section']->id)->whereNull('department_id')->max('sort_order')??0)+1; \Illuminate\Support\Facades\DB::table('curriculum_subject_mappings')->insert(['session_id'=>(string)$scope['session']->id,'class_id'=>(string)$scope['class']->id,'section_id'=>(string)$scope['section']->id,'department_id'=>null,'subject_id'=>(int)$subject->id,'mapping_type'=>'main','sort_order'=>$next,'is_active'=>1,'source'=>'test-fixture','created_at'=>now(),'updated_at'=>now()]);}
    private function placement(newAdmission $student,array $scope,int $position,?Exam $exam=null,bool $nullGroup=false):Placement{return Placement::create(['studentId'=>(string)$student->id,'sessionId'=>(string)$scope['session']->id,'classId'=>(string)$scope['class']->id,'groupId'=>$nullGroup?null:(string)$scope['section']->id,'examId'=>(string)($exam??$scope['exam'])->id,'subjectsCount'=>1,'totalGradePoints'=>5,'gpa'=>5,'totalMarks'=>80,'position'=>$position,'status'=>'Pass']);}
}
