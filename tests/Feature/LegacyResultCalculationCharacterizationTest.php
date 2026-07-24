<?php

namespace Tests\Feature;

use App\Http\Controllers\MarksheetController;
use App\Services\ResultCalculation\BoardResultCalculator;
use App\Models\classManage;
use App\Models\Exam;
use App\Models\GradeList;
use App\Models\Marksheet;
use App\Models\newAdmission;
use App\Models\Placement;
use App\Models\sectionManage;
use App\Models\sessionManage;
use App\Models\Subject;
use App\Models\CultivationAdmin;
use App\Models\MarksScopeState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/**
 * Locks the legacy behavior before a result engine is introduced.
 *
 * Several assertions intentionally describe known Board-rule deviations.
 * They must change only in a later, feature-flagged integration phase.
 */
class LegacyResultCalculationCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=']);
        $admin = new CultivationAdmin();
        $admin->adminName = 'Characterization Admin';
        $admin->adminUser = 'characterization';
        $admin->userType = CultivationAdmin::ROLE_GENERAL;
        $admin->loginPassword = Hash::make('secret');
        $admin->adminMobile = '01700000000';
        $admin->adminMail = 'characterization@example.test';
        $admin->save();
        Session::put('cultivationAdmin', $admin->id);
    }

    public function test_grade_resolver_current_fallback_boundaries(): void
    {
        $expected = [
            0 => ['F', 0.0], 32 => ['F', 0.0], 33 => ['D', 1.0],
            39 => ['D', 1.0], 40 => ['C', 2.0], 49 => ['C', 2.0],
            50 => ['B', 3.0], 59 => ['B', 3.0], 60 => ['A-', 3.5],
            69 => ['A-', 3.5], 70 => ['A', 4.0], 79 => ['A', 4.0],
            80 => ['A+', 5.0], 100 => ['A+', 5.0],
        ];

        foreach ($expected as $score => [$letter, $point]) {
            $grade = GradeList::forScore((float) $score);
            $this->assertSame($letter, $grade->gradeName, "Unexpected letter for {$score}");
            $this->assertSame($point, (float) $grade->gradePoint, "Unexpected point for {$score}");
        }
    }

    public function test_marks_submission_and_update_store_centralized_normalized_grade(): void
    {
        [$session, $class, $section, $exam, $student] = $this->academicScope();
        $subject = $this->subject('Fifty Mark Subject', 'Main', 50, 0, 0);

        $this->submitMarks($session, $class, $section, $exam, $subject, $student, 40, '', '');

        $mark = Marksheet::where('studentId', $student->id)->where('subjectId', $subject->id)->firstOrFail();
        $this->assertSame('A+', $mark->laterGrade);
        $this->assertSame(5.0, (float) $mark->gradePoint);
        $this->assertSame(40.0, (float) $mark->totalMarks);

        $this->submitMarks($session, $class, $section, $exam, $subject, $student, 50, '', '');

        $this->assertDatabaseCount('marksheets', 1);
        $mark->refresh();
        $this->assertSame('A+', $mark->laterGrade);
        $this->assertSame(5.0, (float) $mark->gradePoint);
    }

    public function test_zero_marks_are_stored_as_an_attempt_and_blank_marks_clear_existing_row(): void
    {
        [$session, $class, $section, $exam, $student] = $this->academicScope();
        $subject = $this->subject('General Mathematics', 'Main', 100, 0, 0);

        $this->submitMarks($session, $class, $section, $exam, $subject, $student, 0, '', '');

        $mark = Marksheet::firstOrFail();
        $this->assertSame('0', (string) $mark->subjectMarks);
        $this->assertSame('F', $mark->laterGrade);
        $this->assertSame(0.0, (float) $mark->gradePoint);

        $this->submitMarks($session, $class, $section, $exam, $subject, $student, '', '', '');

        $mark->refresh();
        $this->assertNull($mark->subjectMarks);
        $this->assertNull($mark->objectMarks);
        $this->assertNull($mark->practicalMarks);
        $this->assertNull($mark->totalMarks);
        $this->assertNull($mark->laterGrade);
        $this->assertNull($mark->gradePoint);
    }

    public function test_component_marks_are_summed_before_the_stored_grade_is_resolved(): void
    {
        [$session, $class, $section, $exam, $student] = $this->academicScope();
        $subject = $this->subject('Science', 'Main', 50, 25, 25);

        $this->submitMarks($session, $class, $section, $exam, $subject, $student, 30, 20, 20);

        $mark = Marksheet::firstOrFail();
        $this->assertSame(70.0, (float) $mark->totalMarks);
        $this->assertSame('A', $mark->laterGrade);
        $this->assertSame(4.0, (float) $mark->gradePoint);
    }

    public function test_tabulation_ignores_an_optional_f_for_overall_status(): void
    {
        [$session, $class, $section, $exam, $student] = $this->academicScope();
        $main = $this->subject('Mathematics', 'Main', 100, 0, 0);
        $optional = $this->subject('Higher Mathematics', 'Optional', 100, 0, 0);
        $student->fourthSubjectId = $optional->id;
        $student->save();

        $this->mark($student, $session, $class, $section, $exam, $main, 80, 5, 'A+');
        $this->mark($student, $session, $class, $section, $exam, $optional, 0, 0, 'F');

        $data = $this->tabulation($session, $class, $section, $exam);

        $this->assertCount(1, $data['passResults']);
        $this->assertCount(0, $data['failResults']);
        $this->assertSame('A+', $data['passResults'][0]['finalLetter']);
        $this->assertSame('5.00', $data['passResults'][0]['finalGpa']);
    }

    public function test_tabulation_marks_a_missing_compulsory_subject_incomplete(): void
    {
        [$session, $class, $section, $exam, $student] = $this->academicScope();
        $present = $this->subject('Mathematics', 'Main', 100, 0, 0);
        $missing = $this->subject('English', 'Main', 100, 0, 0);
        $other = $this->student($session, $class, $section, '02');

        $this->mark($student, $session, $class, $section, $exam, $present, 80, 5, 'A+');
        $this->mark($other, $session, $class, $section, $exam, $present, 70, 4, 'A');
        $this->mark($other, $session, $class, $section, $exam, $missing, 70, 4, 'A');

        $data = $this->tabulation($session, $class, $section, $exam);
        $result = collect($data['incompleteResults'])->first(fn (array $row) => $row['student']->id === $student->id);

        $this->assertNotNull($result);
        $this->assertTrue($result['isIncomplete']);
        $this->assertNull($result['finalGpa']);
    }

    public function test_paired_subjects_are_calculated_by_the_centralized_calculator(): void
    {
        $paperOne = $this->subject('Bangla 1st Paper', 'Main', 70, 30, 0, 'bangla_1st_paper');
        $paperTwo = $this->subject('Bangla 2nd Paper', 'Main', 70, 30, 0, 'bangla_2nd_paper');
        $result = app(BoardResultCalculator::class)->calculate(
            (object) [],
            (object) ['passingSystem' => 'Total Mark'],
            [
                (object) ['id' => 1, 'subjectId' => $paperOne->id, 'subjectMarks' => 20, 'objectMarks' => 30, 'practicalMarks' => 0],
                (object) ['id' => 2, 'subjectId' => $paperTwo->id, 'subjectMarks' => 70, 'objectMarks' => 30, 'practicalMarks' => 0],
            ],
            [$paperOne, $paperTwo],
        );

        $this->assertCount(1, $result->subjectResults);
        $this->assertSame('A', $result->subjectResults[0]->letterGrade);
        $this->assertSame(4.0, $result->subjectResults[0]->gradePoint);
    }

    public function test_placement_currently_counts_optional_row_and_optional_f_as_failure(): void
    {
        $student = newAdmission::create(['fullName' => 'Legacy Placement', 'rollNumber' => 1]);
        Marksheet::create(['studentId' => $student->id, 'classId' => '10', 'sessionId' => '2026', 'examId' => '1', 'subjectId' => '1', 'gradePoint' => 5, 'totalMarks' => 80]);
        Marksheet::create(['studentId' => $student->id, 'classId' => '10', 'sessionId' => '2026', 'examId' => '1', 'subjectId' => '2', 'gradePoint' => 5, 'totalMarks' => 80]);
        Marksheet::create(['studentId' => $student->id, 'classId' => '10', 'sessionId' => '2026', 'examId' => '1', 'subjectId' => '3', 'gradePoint' => 0, 'totalMarks' => 20]);

        $this->post(route('placements.recalculate'), [
            'sessionId' => '2026', 'classId' => '10', 'examId' => '1',
        ])->assertRedirect();

        $placement = Placement::firstOrFail();
        $this->assertSame(3, (int) $placement->subjectsCount);
        $this->assertSame(3.33, (float) $placement->gpa);
        $this->assertSame('Fail', $placement->status);
    }

    private function academicScope(): array
    {
        $session = new sessionManage();
        $session->session = '2026';
        $session->save();
        $class = new classManage();
        $class->className = 'Class 10';
        $class->save();
        $section = new sectionManage();
        $section->section = 'A';
        $section->save();
        $exam = new Exam();
        $exam->examName = 'Annual';
        $exam->passingSystem = 2;
        $exam->save();
        $student = $this->student($session, $class, $section, '01');

        return [$session, $class, $section, $exam, $student];
    }

    private function student(sessionManage $session, classManage $class, sectionManage $section, string $roll): newAdmission
    {
        $student = new newAdmission();
        $student->stdId = random_int(100000, 999999999);
        $student->fullName = 'Characterization';
        $student->sureName = $roll;
        $student->sessName = (string) $session->id;
        $student->className = $class->id;
        $student->sectionName = $section->id;
        $student->rollNumber = $roll;
        $student->save();

        return $student;
    }

    private function subject(string $name, string $type, float $cq, float $mcq, float $practical, ?string $alias = null): Subject
    {
        $subject = new Subject();
        $subject->subjectName = $name;
        $subject->alias = $alias ?? strtolower(str_replace(' ', '_', $name));
        $subject->subjectType = $type;
        $subject->assign_class = '0';
        $subject->CQ = $cq;
        $subject->MCQ = $mcq;
        $subject->Practical = $practical;
        $subject->save();

        return $subject;
    }

    private function submitMarks($session, $class, $section, $exam, Subject $subject, newAdmission $student, $cq, $mcq, $practical): void
    {
        $revision = MarksScopeState::query()
            ->where('sessionId', (string) $session->id)
            ->where('classId', (string) $class->id)
            ->where('groupId', (string) $section->id)
            ->where('examId', (string) $exam->id)
            ->where('subjectId', (string) $subject->id)
            ->value('revision');
        app(MarksheetController::class)->confirmMarks(Request::create('/marks/add/confirm', 'POST', [
            'sessionId' => $session->id,
            'classId' => $class->id,
            'groupId' => $section->id,
            'optionalGroupId' => null,
            'gender' => 'all',
            'examId' => $exam->id,
            'subjectId' => $subject->id,
            'studentId' => [$student->id],
            'cqMarks' => [$cq],
            'mcqMarks' => [$mcq],
            'practical' => [$practical],
            'scope_revision' => $revision,
        ]));
    }

    private function mark($student, $session, $class, $section, $exam, Subject $subject, float $cq, float $gp, string $letter): void
    {
        Marksheet::create([
            'studentId' => $student->id,
            'classId' => $class->id,
            'sessionId' => $session->id,
            'groupId' => $section->id,
            'examId' => $exam->id,
            'subjectId' => $subject->id,
            'subjectMarks' => $cq,
            'totalMarks' => $cq,
            'gradePoint' => $gp,
            'laterGrade' => $letter,
        ]);
    }

    private function tabulation($session, $class, $section, $exam): array
    {
        $response = app(MarksheetController::class)->allMarksheet(Request::create('/marksheet/all', 'GET', [
            'sessionId' => $session->id,
            'classId' => $class->id,
            'sectionId' => $section->id,
            'examId' => $exam->id,
        ]));

        return $response->getData();
    }

    private function resultRow(Subject $subject, float $cq, float $mcq, float $practical, float $total, string $grade, float $gp, string $cqGrade, string $mcqGrade): array
    {
        return [
            'id' => $subject->id,
            'name' => $subject->subjectName,
            'type' => $subject->subjectType,
            'cq' => $cq,
            'mcq' => $mcq,
            'practical' => $practical,
            'total' => $total,
            'grade' => $grade,
            'gradePoint' => number_format($gp, 2),
            'cqGrade' => $cqGrade,
            'mcqGrade' => $mcqGrade,
            'prGrade' => '-',
        ];
    }
}
