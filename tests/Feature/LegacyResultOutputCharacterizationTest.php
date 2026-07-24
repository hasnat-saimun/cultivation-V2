<?php

namespace Tests\Feature;

use App\Http\Controllers\AdmissionController;
use App\Http\Controllers\MarksheetController;
use App\Http\Controllers\ResultArchiveController;
use App\Models\classManage;
use App\Models\Exam;
use App\Models\Marksheet;
use App\Models\newAdmission;
use App\Models\Placement;
use App\Models\ResultArchive;
use App\Models\sectionManage;
use App\Models\sessionManage;
use App\Models\Subject;
use App\Services\ResultCalculation\BulkTranscriptResultBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Executable baseline for legacy result outputs.
 *
 * Assertions intentionally preserve known inconsistencies. They are not the
 * desired Board-rule expectations and may change only behind reviewed flags.
 */
class LegacyResultOutputCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
            'cache.default' => 'array',
        ]);
    }

    public function test_single_transcript_caps_optional_bonus_at_five_and_normalizes_non_100_subject(): void
    {
        $scope = $this->scope();
        $main = $this->subject('Fifty Mark Main', 'Main', 50);
        $optional = $this->subject('Optional A Plus', 'Optional', 100);
        $student = $this->student($scope, '01', $optional->id);

        $this->mark($student, $scope, $main, 40, 5, 'A+');
        $this->mark($student, $scope, $optional, 80, 5, 'A+');

        $html = $this->singleTranscriptHtml($student, $scope['exam']);
        $summary = $this->summaryFromHtml($html);

        $this->assertSame('5.00', $summary['gpa']);
        $this->assertSame('A+', $summary['letter']);
        $this->assertStringContainsString('Fifty Mark Main', $html);
        $this->assertMatchesRegularExpression('/Fifty Mark Main.*?40.*?A\+.*?5\.00/s', $html);
    }

    public function test_single_transcript_uses_centralized_fail_and_incomplete_behaviors(): void
    {
        $scope = $this->scope(1);

        $failed = $this->student($scope, '02');
        $failedMain = $this->subject('Failed Main', 'Main', 100);
        $this->mark($failed, $scope, $failedMain, 32, 0, 'F');
        $this->assertSame(['gpa' => '0.00', 'letter' => 'F'], $this->summaryFromHtml($this->singleTranscriptHtml($failed, $scope['exam'])));

        $optionalF = $this->subject('Failed Optional', 'Optional', 100);
        $optionalStudent = $this->student($scope, '03', $optionalF->id);
        $passedMain = $this->subject('Passed Main', 'Main', 100);
        $this->mark($optionalStudent, $scope, $passedMain, 80, 5, 'A+');
        $this->mark($optionalStudent, $scope, $optionalF, 0, 0, 'F');
        $optionalHtml = $this->singleTranscriptHtml($optionalStudent, $scope['exam']);
        $this->assertSame(['gpa' => 'Incomplete', 'letter' => 'Incomplete'], $this->summaryFromHtml($optionalHtml));
        $this->assertStringContainsString('Failed Subjects (1)', $optionalHtml);

        $zero = $this->student($scope, '04');
        $zeroMain = $this->subject('Zero Main', 'Main', 100);
        $this->mark($zero, $scope, $zeroMain, 0, 0, 'F');
        $this->assertSame(['gpa' => 'Incomplete', 'letter' => 'Incomplete'], $this->summaryFromHtml($this->singleTranscriptHtml($zero, $scope['exam'])));

        $missing = $this->student($scope, '05');
        $this->assertSame(['gpa' => 'Incomplete', 'letter' => 'Incomplete'], $this->summaryFromHtml($this->singleTranscriptHtml($missing, $scope['exam'])));

        $theory = $this->student($scope, '06');
        $theorySubject = $this->subject('Theory Type', 'Theory', 100);
        $this->mark($theory, $scope, $theorySubject, 80, 5, 'A+');
        $theoryHtml = $this->singleTranscriptHtml($theory, $scope['exam']);
        $this->assertSame(['gpa' => 'Incomplete', 'letter' => 'Incomplete'], $this->summaryFromHtml($theoryHtml));
        $this->assertStringContainsString('Theory Type</td>', $theoryHtml);

        $paired = $this->student($scope, '07');
        $paper1 = $this->subject('Bangla 1st Paper', 'Main', 70, 30, 0, 'bangla_1st_paper');
        $paper2 = $this->subject('Bangla 2nd Paper', 'Main', 70, 30, 0, 'bangla_2nd_paper');
        $this->mark($paired, $scope, $paper1, 20, 0, 'F', 30);
        $this->mark($paired, $scope, $paper2, 70, 5, 'A+', 30);
        $pairedHtml = $this->singleTranscriptHtml($paired, $scope['exam']);
        $this->assertSame(['gpa' => 'Incomplete', 'letter' => 'Incomplete'], $this->summaryFromHtml($pairedHtml));
        $this->assertStringContainsString('(20 + 70) = 90', $pairedHtml);
    }

    public function test_bulk_transcript_renders_multiple_students_from_centralized_results(): void
    {
        $scope = $this->scope();
        $main = $this->subject('Main Subject', 'Main', 100);
        $optional = $this->subject('Optional Subject', 'Optional', 100);
        $fifty = $this->subject('Fifty Mark Subject', 'Main', 50);

        $bonus = $this->student($scope, '11', $optional->id);
        $this->mark($bonus, $scope, $main, 80, 5, 'A+');
        $this->mark($bonus, $scope, $optional, 80, 5, 'A+');

        $compulsoryFail = $this->student($scope, '12');
        $this->mark($compulsoryFail, $scope, $main, 32, 0, 'F');

        $optionalFail = $this->student($scope, '13', $optional->id);
        $this->mark($optionalFail, $scope, $fifty, 40, 5, 'A+');
        $this->mark($optionalFail, $scope, $optional, 0, 0, 'F');

        $missing = $this->student($scope, '14');
        $zero = $this->student($scope, '15');
        $this->mark($zero, $scope, $main, 0, 0, 'F');

        $html = $this->bulkTranscriptHtml([$bonus, $compulsoryFail, $optionalFail, $missing, $zero], $scope['exam']);
        preg_match_all('/Letter Grade:\s*([^<]+)<\/th>\s*<th[^>]*>Grade Point:\s*([^<]+)/', $html, $matches, PREG_SET_ORDER);
        $summaries = array_map(fn ($m) => [trim($m[2]), trim($m[1])], $matches);

        $this->assertSame([
            ['Incomplete', 'Incomplete'],
            ['Incomplete', 'Incomplete'],
            ['Incomplete', 'Incomplete'],
            ['Incomplete', 'Incomplete'],
            ['Incomplete', 'Incomplete'],
        ], $summaries);
    }

    public function test_promotion_archive_uses_only_selected_exam_centralized_result(): void
    {
        $scope = $this->scope();
        $secondExam = new Exam();
        $secondExam->examName = 'Second Exam';
        $secondExam->passingSystem = 2;
        $secondExam->save();
        $mainOne = $this->subject('Main One', 'Main', 100);
        $mainTwo = $this->subject('Main Two', 'Main', 100);
        $optional = $this->subject('Optional', 'Optional', 100);
        $student = $this->student($scope, '21', $optional->id);

        $this->mark($student, $scope, $mainOne, 80, 5, 'A+', null, $scope['exam']);
        $this->mark($student, $scope, $mainTwo, 40, 2, 'C', null, $secondExam);
        $this->mark($student, $scope, $optional, 0, 0, 'F');

        $archive = $this->promoteAndArchive($student, $scope);
        $snapshot = $archive->result_data;

        $this->assertNull($snapshot['gpa']);
        $this->assertSame('Incomplete', $snapshot['result']);
        $this->assertCount(3, $snapshot['subjects']);
        $this->assertSame(80.0, (float) $snapshot['total_marks']);
        $this->assertSame('board-v1', $snapshot['calculation_version']);

        // firstOrCreate preserves an existing snapshot with the same old academic identity.
        $this->assertDatabaseCount('result_archives', 1);
    }

    public function test_frozen_archive_render_uses_stored_snapshot_after_live_marks_change(): void
    {
        $scope = $this->scope();
        $subject = $this->subject('Live Mathematics', 'Main', 100);
        $student = $this->student($scope, '31');
        $mark = $this->mark($student, $scope, $subject, 20, 0, 'F');
        $archive = ResultArchive::create([
            'student_id' => $student->id,
            'old_class' => $scope['class']->id,
            'old_roll' => $student->rollNumber,
            'old_session' => $scope['session']->id,
            'old_section' => $scope['section']->id,
            'exam_id' => $scope['exam']->id,
            'result_data' => [
                'subjects' => [[
                    'id' => $subject->id, 'name' => 'Archived Mathematics', 'type' => 'Main',
                    'cq' => 70, 'mcq' => '-', 'practical' => '-', 'total' => 70,
                    'grade' => 'A', 'gradePoint' => 4,
                ]],
                'total_marks' => 70,
                'gpa' => 4.25,
                'result' => 'Archived Pass',
            ],
        ]);

        $mark->update(['subjectMarks' => 100, 'totalMarks' => 100, 'laterGrade' => 'A+', 'gradePoint' => 5]);
        $subject->update(['subjectName' => 'Changed Live Mathematics']);

        $response = app(ResultArchiveController::class)->transcript($archive->id);
        $html = $response->render();

        $this->assertStringContainsString('Archived Mathematics', $html);
        $this->assertStringContainsString('Grade Point: 4.25', $html);
        $this->assertStringContainsString('Remark- Archived Pass', $html);
        $this->assertStringNotContainsString('Changed Live Mathematics', $html);
        $this->assertStringNotContainsString('Grade Point: 5', $html);
    }

    public function test_result_summary_uses_centralized_optional_and_missing_subject_counts(): void
    {
        $scope = $this->scope();
        $main = $this->subject('Main', 'Main', 100);
        $optional = $this->subject('Optional', 'Optional', 100);
        $pass = $this->student($scope, '41');
        $optionalFail = $this->student($scope, '42', $optional->id);
        $absent = $this->student($scope, '43');
        $this->mark($pass, $scope, $main, 80, 5, 'A+');
        $this->mark($optionalFail, $scope, $main, 80, 5, 'A+');
        $this->mark($optionalFail, $scope, $optional, 0, 0, 'F');

        $response = app(MarksheetController::class)->resultSummary($this->resultRequest($scope));
        $data = $response->getData();

        $this->assertSame([
            'total' => 3, 'present' => 2, 'absent' => 1, 'pass' => 2, 'fail' => 0, 'incomplete' => 1,
            'passPercentage' => 66.67, 'failPercentage' => 0.0, 'incompletePercentage' => 33.33,
        ], $data['overallSummary']);
        $this->assertSame([], $data['failureBuckets']);
    }

    public function test_centralized_controller_outputs_agree_across_active_paths(): void
    {
        $scope = $this->scope();
        $main = $this->subject('Main A Plus', 'Main', 100);
        $optional = $this->subject('Optional A Plus', 'Optional', 100);
        $student = $this->student($scope, '51', $optional->id);
        $this->mark($student, $scope, $main, 80, 5, 'A+');
        $this->mark($student, $scope, $optional, 80, 5, 'A+');

        $single = $this->summaryFromHtml($this->singleTranscriptHtml($student, $scope['exam']));
        $bulkHtml = $this->bulkTranscriptHtml([$student], $scope['exam']);
        preg_match('/Letter Grade:\s*([^<]+)<\/th>\s*<th[^>]*>Grade Point:\s*([^<]+)/', $bulkHtml, $bulkMatch);
        $bulk = ['gpa' => trim($bulkMatch[2]), 'letter' => trim($bulkMatch[1])];

        $tabData = app(MarksheetController::class)->allMarksheet($this->resultRequest($scope))->getData();
        $tabulation = $tabData['passResults'][0];

        $this->post(route('placements.recalculate'), [
            'sessionId' => (string) $scope['session']->id,
            'classId' => (string) $scope['class']->id,
            'examId' => (string) $scope['exam']->id,
            'groupId' => (string) $scope['section']->id,
        ])->assertRedirect();
        $placement = Placement::where('studentId', $student->id)->firstOrFail();

        $archive = $this->promoteAndArchive($student, $scope);
        $archiveHtml = app(ResultArchiveController::class)->transcript($archive->id)->render();

        $this->assertSame(['gpa' => '5.00', 'letter' => 'A+'], $single);
        $this->assertSame(['gpa' => '5.00', 'letter' => 'A+'], $bulk);
        $this->assertSame('5.00', $tabulation['finalGpa']);
        $this->assertSame('Pass', $placement->status);
        $this->assertSame(5.0, (float) $placement->gpa);
        $this->assertSame(2, (int) $placement->subjectsCount);
        $this->assertSame(5.0, (float) $archive->result_data['gpa']);
        $this->assertSame('Pass', $archive->result_data['result']);
        $this->assertStringContainsString('Grade Point: 5', $archiveHtml);
    }

    private function scope(int $passingSystem = 2): array
    {
        $session = new sessionManage(); $session->session = '2026'; $session->save();
        $class = new classManage(); $class->className = 'Class 10'; $class->save();
        $section = new sectionManage(); $section->section = 'A'; $section->save();
        $exam = new Exam(); $exam->examName = 'Annual'; $exam->passingSystem = $passingSystem; $exam->save();
        return compact('session', 'class', 'section', 'exam');
    }

    private function student(array $scope, string $roll, ?int $fourthSubjectId = null): newAdmission
    {
        $student = new newAdmission();
        $student->stdId = random_int(100000, 999999999);
        $student->fullName = 'Output Student';
        $student->sureName = $roll;
        $student->sessName = $scope['session']->id;
        $student->className = $scope['class']->id;
        $student->sectionName = $scope['section']->id;
        $student->rollNumber = $roll;
        $student->fourthSubjectId = $fourthSubjectId;
        $student->save();
        return $student;
    }

    private function subject(string $name, string $type, float $cq, float $mcq = 0, float $practical = 0, ?string $alias = null): Subject
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

    private function mark(newAdmission $student, array $scope, Subject $subject, float $cq, float $gp, string $grade, ?float $mcq = null, ?Exam $exam = null): Marksheet
    {
        return Marksheet::create([
            'studentId' => $student->id,
            'classId' => $scope['class']->id,
            'sessionId' => $scope['session']->id,
            'groupId' => $scope['section']->id,
            'examId' => ($exam ?? $scope['exam'])->id,
            'subjectId' => $subject->id,
            'subjectMarks' => $cq,
            'objectMarks' => $mcq,
            'totalMarks' => $cq + ($mcq ?? 0),
            'gradePoint' => $gp,
            'laterGrade' => $grade,
        ]);
    }

    private function singleTranscriptHtml(newAdmission $student, Exam $exam): string
    {
        return app(MarksheetController::class)->generateMarksheet(Request::create('/marksheet/generate', 'GET', [
            'studentId' => $student->id,
            'examId' => $exam->id,
        ]))->render();
    }

    private function bulkTranscriptHtml(array $students, Exam $exam): string
    {
        foreach ($students as $student) {
            $student->load(['marksheet' => fn ($q) => $q->where('examId', $exam->id)->orderBy('subjectId')]);
        }
        $transcripts = app(BulkTranscriptResultBuilder::class)->build($students, $exam);

        return view('result.bulk-transcript-pdf', [
            'transcripts' => $transcripts,
            'bulkView' => [
                'title' => 'Academic Transcript',
                'examName' => $exam->examName,
                'institute' => ['name' => 'Test Institute', 'address' => '', 'mobile' => '', 'email' => '', 'logoUrl' => null],
                'principalSignatureUrl' => null,
                'gradeLegend' => [],
            ],
        ])->render();
    }

    private function summaryFromHtml(string $html): array
    {
        preg_match('/Letter Grade:\s*([^<]+)<\/th>\s*<th[^>]*>Grade Point:\s*([^<]+)/', $html, $match);
        $this->assertNotEmpty($match, 'Transcript summary row was not rendered.');
        return ['gpa' => trim($match[2]), 'letter' => trim($match[1])];
    }

    private function resultRequest(array $scope): Request
    {
        return Request::create('/marksheet/all', 'GET', [
            'sessionId' => $scope['session']->id,
            'classId' => $scope['class']->id,
            'sectionId' => $scope['section']->id,
            'examId' => $scope['exam']->id,
        ]);
    }

    private function promoteAndArchive(newAdmission $student, array $scope): ResultArchive
    {
        $targetSession = new sessionManage(); $targetSession->session = '2027'; $targetSession->save();
        $targetClass = new classManage(); $targetClass->className = 'Class 11'; $targetClass->save();
        $targetSection = new sectionManage(); $targetSection->section = 'B'; $targetSection->save();
        $token = 'promotion-'.bin2hex(random_bytes(8));
        session(['promotion_submit_token' => $token]);

        app(AdmissionController::class)->confirmPromotData(Request::create('/student/promote/confirm', 'POST', [
            'sessionId' => $scope['session']->id,
            'classId' => $scope['class']->id,
            'groupId' => $scope['section']->id,
            'type' => 'sectionwise',
            'promotSession' => $targetSession->id,
            'promotId' => $targetClass->id,
            'promotSection' => $targetSection->id,
            'selected_students' => [$student->id],
            'roll_numbers' => [$student->id => $student->rollNumber],
            'examId' => $scope['exam']->id,
            'submit_token' => $token,
        ]));

        return ResultArchive::where('student_id', $student->id)->firstOrFail();
    }
}
