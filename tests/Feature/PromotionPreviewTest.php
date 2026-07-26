<?php

namespace Tests\Feature;

use App\Models\classManage;
use App\Models\Exam;
use App\Models\Marksheet;
use App\Models\newAdmission;
use App\Models\Placement;
use App\Models\PromotionAuditLog;
use App\Models\ResultArchive;
use App\Models\ResultPublish;
use App\Models\sectionManage;
use App\Models\sessionManage;
use App\Models\Subject;
use App\Services\ResultCalculation\PromotionPreviewBuilder;
use App\Services\ResultCalculation\ResultCalculationBatchBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array']);
    }

    public function test_legacy_is_manual_while_centralized_categories_pass_fail_and_incomplete(): void
    {
        $scope = $this->scope();
        $subject = $this->subject('Main', 'Main', 100);
        $pass = $this->student($scope, '01');
        $fail = $this->student($scope, '02');
        $incomplete = $this->student($scope, '03');
        $this->mark($pass, $scope, $subject, 80);
        $this->mark($fail, $scope, $subject, 32);

        $preview = $this->preview($scope);
        $rows = collect($preview['rows'])->keyBy('studentId');

        $this->assertTrue($rows[$pass->id]['legacyEligible']);
        $this->assertSame('Academically eligible', $rows[$pass->id]['eligibilityCategory']);
        $this->assertSame('Academically failed', $rows[$fail->id]['eligibilityCategory']);
        $this->assertContains('CENTRALIZED_FAIL', $rows[$fail->id]['blockingReasons']);
        $this->assertSame('Academically incomplete', $rows[$incomplete->id]['eligibilityCategory']);
        $this->assertContains('CENTRALIZED_INCOMPLETE', $rows[$incomplete->id]['blockingReasons']);
        $this->assertSame(1, $preview['summary']['phase9WouldBeEligible']);
    }

    public function test_selected_exam_and_student_scope_are_strict(): void
    {
        $scope = $this->scope();
        $otherExam = new Exam(); $otherExam->examName = 'Other'; $otherExam->passingSystem = 2; $otherExam->save();
        $subject = $this->subject('Main', 'Main', 100);
        $selected = $this->student($scope, '01');
        $otherStudent = $this->student($scope, '02');
        $this->mark($selected, $scope, $subject, 80);
        $this->mark($selected, $scope, $subject, 0, $otherExam);
        $this->mark($otherStudent, $scope, $subject, 32);
        $this->placement($selected, $scope, 1, $otherExam);
        ResultArchive::create(['student_id'=>$selected->id,'old_class'=>$scope['class']->id,'old_session'=>$scope['session']->id,
            'old_section'=>$scope['section']->id,'old_roll'=>'01','exam_id'=>$otherExam->id,'result_data'=>[]]);

        $preview = app(PromotionPreviewBuilder::class)->build(
            $scope['exam']->id, $scope['class']->id, $scope['session']->id,
            $scope['toClass']->id, $scope['toSession']->id, $scope['section']->id, null, $scope['toSection']->id,
            null, $selected->id
        );
        $row = $preview['rows'][0];

        $this->assertCount(1, $preview['rows']);
        $this->assertSame('Pass', $row['centralizedStatus']);
        $this->assertNull($row['placementStatus']);
        $this->assertSame(0, $row['archiveCount']);
    }

    public function test_optional_failure_and_cross_output_result_parity(): void
    {
        $scope = $this->scope();
        $main = $this->subject('Main', 'Main', 100);
        $optional = $this->subject('Optional', 'Optional', 100);
        $student = $this->student($scope, '01', $optional->id);
        $this->mark($student, $scope, $main, 80);
        $this->mark($student, $scope, $optional, 0);

        $row = $this->preview($scope)['rows'][0];
        $batchResult = app(ResultCalculationBatchBuilder::class)->build(
            $scope['exam']->id, $scope['class']->id, $scope['session']->id, $scope['section']->id
        )['entries'][$student->id]['result'];

        $this->assertSame('Pass', $row['centralizedStatus']);
        $this->assertSame($batchResult->gpa, $row['centralizedGpa']);
        $this->assertSame($batchResult->toArray(), $row['_result']->toArray());
        $this->assertContains('OPTIONAL_F_OR_DENOMINATOR_DIFFERENCE', $row['differenceReasons']);
    }

    public function test_destination_roll_student_history_and_archive_conflicts_are_reported(): void
    {
        $scope = $this->scope();
        $subject = $this->subject('Main', 'Main', 100);
        $student = $this->student($scope, '07');
        $this->mark($student, $scope, $subject, 80);
        newAdmission::create(['stdId'=>$student->stdId,'fullName'=>'Destination duplicate','sessName'=>$scope['toSession']->id,
            'className'=>$scope['toClass']->id,'sectionName'=>$scope['toSection']->id,'rollNumber'=>'07']);
        ResultArchive::create(['student_id'=>$student->id,'old_class'=>$scope['class']->id,'old_session'=>$scope['session']->id,
            'old_section'=>$scope['section']->id,'old_roll'=>'07','exam_id'=>$scope['exam']->id,'result_data'=>[]]);
        PromotionAuditLog::create(['promotion_id'=>'one','student_id'=>$student->id,'old_session'=>$scope['session']->id,
            'old_class'=>$scope['class']->id,'old_section'=>$scope['section']->id,'old_roll'=>'07',
            'new_session'=>$scope['toSession']->id,'new_class'=>$scope['toClass']->id,'new_section'=>$scope['toSection']->id,'new_roll'=>'07']);

        $row = $this->preview($scope)['rows'][0];

        $this->assertTrue($row['alreadyPromoted']);
        $this->assertTrue($row['destinationConflict']);
        $this->assertContains('DESTINATION_ROLL_CONFLICT', $row['blockingReasons']);
        $this->assertContains('DESTINATION_STUDENT_CONFLICT', $row['blockingReasons']);
        $this->assertContains('ALREADY_PROMOTED', $row['blockingReasons']);
        $this->assertSame(1, $row['archiveCount']);
    }

    public function test_scope_conflicts_block_phase9_safety_without_writes(): void
    {
        $scope = $this->scope();
        $subject = $this->subject('Main', 'Main', 100);
        $student = $this->student($scope, '01');
        $this->mark($student, $scope, $subject, 80);

        $preview = app(PromotionPreviewBuilder::class)->build(
            $scope['exam']->id, $scope['class']->id, $scope['session']->id,
            $scope['class']->id, $scope['session']->id, $scope['section']->id, null, $scope['section']->id
        );

        $this->assertFalse($preview['summary']['phase9WriteSafe']);
        $this->assertContains('SAME_ACADEMIC_SCOPE', collect($preview['summary']['scopeBlockers'])->pluck('code')->all());
        $this->assertSame($scope['class']->id, (int) $student->fresh()->className);
    }

    public function test_command_and_service_are_strictly_read_only(): void
    {
        $scope = $this->scope();
        $subject = $this->subject('Main', 'Main', 100);
        $student = $this->student($scope, '01');
        $mark = $this->mark($student, $scope, $subject, 80);
        $placement = $this->placement($student, $scope, 1);
        ResultPublish::create(['examId'=>$scope['exam']->id,'classId'=>$scope['class']->id,'sessionId'=>$scope['session']->id,'groupId'=>$scope['section']->id]);
        $beforeStudent = $student->fresh()->getAttributes();
        $beforeMark = $mark->fresh()->getAttributes();
        $beforePlacement = $placement->fresh()->getAttributes();

        $this->preview($scope);
        $this->artisan('result-engine:promotion-preview', [
            '--exam'=>$scope['exam']->id,'--class'=>$scope['class']->id,'--session'=>$scope['session']->id,
            '--to-class'=>$scope['toClass']->id,'--to-session'=>$scope['toSession']->id,
            '--section'=>$scope['section']->id,'--to-section'=>$scope['toSection']->id,'--all'=>true,
        ])->assertSuccessful()->expectsOutputToContain('no records were modified');

        $this->assertSame($beforeStudent, $student->fresh()->getAttributes());
        $this->assertSame($beforeMark, $mark->fresh()->getAttributes());
        $this->assertSame($beforePlacement, $placement->fresh()->getAttributes());
        $this->assertSame(0, ResultArchive::count());
        $this->assertSame(0, PromotionAuditLog::count());
        $this->assertSame(1, ResultPublish::count());
    }

    public function test_one_student_calculation_error_is_retained_and_blocks_scope_safety(): void
    {
        $scope = $this->scope();
        $subject = $this->subject('Main', 'Main', 100);
        $safe = $this->student($scope, '01');
        $broken = $this->student($scope, '02');
        $this->mark($safe, $scope, $subject, 80);
        $this->mark($broken, $scope, $subject, 80);
        $batch = app(ResultCalculationBatchBuilder::class)->buildTolerant(
            $scope['exam']->id, $scope['class']->id, $scope['session']->id, $scope['section']->id
        );
        $brokenEntry = $batch['entries'][$broken->id];
        unset($batch['entries'][$broken->id]);
        $batch['errors'][$broken->id] = ['student'=>$brokenEntry['student'], 'exception'=>\RuntimeException::class];
        $fake = new class($batch) extends ResultCalculationBatchBuilder {
            public function __construct(private array $fixture) {}
            public function buildTolerant(int $examId, int $classId, int $sessionId, ?int $sectionId = null, ?int $departmentId = null): array
            {
                return $this->fixture;
            }
        };

        $preview = (new PromotionPreviewBuilder($fake))->build(
            $scope['exam']->id, $scope['class']->id, $scope['session']->id,
            $scope['toClass']->id, $scope['toSession']->id, $scope['section']->id, null, $scope['toSection']->id
        );
        $rows = collect($preview['rows'])->keyBy('studentId');

        $this->assertSame('Pass', $rows[$safe->id]['centralizedStatus']);
        $this->assertSame('CalculationError', $rows[$broken->id]['centralizedStatus']);
        $this->assertSame(1, $preview['summary']['calculationErrors']);
        $this->assertFalse($preview['summary']['phase9WriteSafe']);
    }

    private function preview(array $scope): array
    {
        return app(PromotionPreviewBuilder::class)->build(
            $scope['exam']->id, $scope['class']->id, $scope['session']->id,
            $scope['toClass']->id, $scope['toSession']->id, $scope['section']->id, null, $scope['toSection']->id
        );
    }

    private function scope(): array
    {
        $session = new sessionManage(); $session->session = '2026'; $session->save();
        $toSession = new sessionManage(); $toSession->session = '2027'; $toSession->save();
        $class = new classManage(); $class->className = 'Class 8'; $class->save();
        $toClass = new classManage(); $toClass->className = 'Class 11'; $toClass->save();
        $section = new sectionManage(); $section->section = 'A'; $section->save();
        $toSection = new sectionManage(); $toSection->section = 'B'; $toSection->save();
        $exam = new Exam(); $exam->examName = 'Annual'; $exam->passingSystem = 2; $exam->save();
        return compact('session','toSession','class','toClass','section','toSection','exam');
    }

    private function student(array $scope, string $roll, ?int $fourth = null): newAdmission
    {
        return newAdmission::create(['stdId'=>(string)random_int(100000,9999999),'fullName'=>'Preview Student',
            'sessName'=>$scope['session']->id,'className'=>$scope['class']->id,'sectionName'=>$scope['section']->id,
            'rollNumber'=>$roll,'fourthSubjectId'=>$fourth]);
    }

    private function subject(string $name, string $type, float $cq): Subject
    {
        return Subject::create(['subjectName'=>$name,'alias'=>strtolower($name),'subjectType'=>$type,'assign_class'=>'0','CQ'=>$cq,'MCQ'=>0,'Practical'=>0]);
    }

    private function mark(newAdmission $student, array $scope, Subject $subject, float $cq, ?Exam $exam = null): Marksheet
    {
        $this->ensureCurriculumMapping($scope, $subject);

        return Marksheet::create(['studentId'=>$student->id,'classId'=>$scope['class']->id,'sessionId'=>$scope['session']->id,
            'groupId'=>$scope['section']->id,'examId'=>($exam ?? $scope['exam'])->id,'subjectId'=>$subject->id,
            'subjectMarks'=>$cq,'totalMarks'=>$cq,'gradePoint'=>$cq >= 80 ? 5 : 0,'laterGrade'=>$cq >= 33 ? 'A+' : 'F']);
    }

    private function ensureCurriculumMapping(array $scope, Subject $subject): void
    {
        $exists = \Illuminate\Support\Facades\DB::table('curriculum_subject_mappings')
            ->where('session_id', (string) $scope['session']->id)
            ->where('class_id', (string) $scope['class']->id)
            ->where('section_id', (string) $scope['section']->id)
            ->whereNull('department_id')
            ->where('subject_id', (int) $subject->id)
            ->exists();

        if ($exists) {
            return;
        }

        $nextOrder = (int) (\Illuminate\Support\Facades\DB::table('curriculum_subject_mappings')
            ->where('session_id', (string) $scope['session']->id)
            ->where('class_id', (string) $scope['class']->id)
            ->where('section_id', (string) $scope['section']->id)
            ->whereNull('department_id')
            ->max('sort_order') ?? 0) + 1;

        \Illuminate\Support\Facades\DB::table('curriculum_subject_mappings')->insert([
            'session_id' => (string) $scope['session']->id,
            'class_id' => (string) $scope['class']->id,
            'section_id' => (string) $scope['section']->id,
            'department_id' => null,
            'subject_id' => (int) $subject->id,
            'mapping_type' => 'main',
            'sort_order' => $nextOrder,
            'is_active' => 1,
            'source' => 'test-fixture',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function placement(newAdmission $student, array $scope, int $position, ?Exam $exam = null): Placement
    {
        return Placement::create(['studentId'=>$student->id,'classId'=>$scope['class']->id,'sessionId'=>$scope['session']->id,
            'groupId'=>$scope['section']->id,'examId'=>($exam ?? $scope['exam'])->id,'subjectsCount'=>1,
            'totalGradePoints'=>5,'gpa'=>5,'totalMarks'=>80,'position'=>$position,'status'=>'Pass']);
    }
}
