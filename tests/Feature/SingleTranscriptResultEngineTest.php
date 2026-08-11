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
use App\Models\MarksScopeState;
use App\Models\PromotionAuditLog;
use App\Models\sectionManage;
use App\Models\sessionManage;
use App\Models\Subject;
use App\Services\ResultCalculation\StudentResult;
use App\Services\ResultCalculation\TranscriptSubjectOrderingService;
use App\Services\ResultCalculation\TranscriptResultPresenter;
use App\Services\AcademicAttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SingleTranscriptResultEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=', 'cache.default' => 'array']);
        $this->signInGeneralAdmin();
    }

    public function test_retired_controller_uses_centralized_single_transcript_when_flag_is_disabled(): void
    {
        config(['result_engine.transcript_enabled' => false]);
        $scope = $this->scope();
        $main = $this->subject('Main', 'Main', 100);
        $optional = $this->subject('Optional', 'Optional', 100);
        $student = $this->student($scope, $optional->id);
        $this->mark($student, $scope, $main, 80);
        $this->mark($student, $scope, $optional, 80);

        $html = $this->html($student, $scope['exam']);

        $this->assertSummary($html, '5.00', 'A+');
        $this->assertStringContainsString('Status- Pass', $html);
    }

    public function test_enabled_normal_pass_preserves_identity_and_layout_data(): void
    {
        [$html] = $this->singleMainResult('Mathematics', 'Main', 100, 80);

        $this->assertSummary($html, '5.00', 'A+');
        $this->assertStringContainsString('Status- Pass', $html);
        $this->assertStringContainsString('Output Student', $html);
        $this->assertStringContainsString('Annual', $html);
        $this->assertStringContainsString('Mathematics', $html);
        $this->assertStringContainsString('Main Subject', $html);
    }

    public function test_numeric_public_student_id_lookup_renders_transcript(): void
    {
        $scope = $this->scope();
        $subject = $this->subject('Numeric ID Main', 'Main', 100);
        $student = $this->student($scope);
        $student->stdId = '2025000175';
        $student->save();
        $this->mark($student, $scope, $subject, 80);

        $response = $this->get(route('marksheetGenerate', [
            'stdId' => $student->stdId,
            'examId' => $scope['exam']->id,
        ]));

        $response->assertOk()->assertSee('Merit Position');
    }

    public function test_view_receives_presenter_prepared_values_and_rendering_executes_no_queries(): void
    {
        $scope = $this->scope();
        $subject = $this->subject('Prepared Main', 'Main', 50);
        $student = $this->student($scope);
        $this->mark($student, $scope, $subject, 40);

        $response = $this->response($student, $scope['exam']);
        $data = $response->getData();

        $this->assertTrue($data['usingNewResultEngine']);
        $this->assertSame('A+', $data['transcriptResult']['mainRows'][0]['grade']);
        $this->assertSame('5.00', $data['transcriptResult']['mainRows'][0]['gradePoint']);
        $this->assertSame(40.0, $data['transcriptResult']['mainRows'][0]['total']);
        $this->assertSame('5.00', $data['transcriptResult']['gpaDisplay']);
        $this->assertSame('Pass', $data['transcriptResult']['status']);
        $this->assertSame('Annual', $data['transcriptView']['examName']);

        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });
        $html = $response->render();

        $this->assertStringContainsString('Prepared Main', $html);
        $this->assertSame([], $queries, 'The single-transcript Blade path executed a database query.');
    }

    public function test_single_transcript_shows_saved_academic_attendance_without_changing_result_or_merit(): void
    {
        $scope = $this->scope();
        $subject = $this->subject('Attendance Main', 'Main', 100);
        $student = $this->student($scope);
        $this->mark($student, $scope, $subject, 80);
        $before = $this->response($student, $scope['exam'])->getData();
        $this->assertNull($before['transcriptView']['academicAttendance']);
        $missingHtml = view('result.partials.academic-attendance', [
            'attendance' => $before['transcriptView']['academicAttendance'],
        ])->render();
        $this->assertStringContainsString('Academic Attendance', $missingHtml);
        $this->assertSame(3, substr_count($missingHtml, '—'));

        app(AcademicAttendanceService::class)->saveOne([
            'exam_id' => $scope['exam']->id, 'session_id' => $scope['session']->id,
            'class_id' => $scope['class']->id, 'section_id' => $scope['section']->id,
            'department_id' => $scope['department']->id, 'gender' => 'all',
        ], ['student_id' => $student->id, 'working_days' => 120, 'present_days' => 112, 'absent_days' => 8], 1);

        $after = $this->response($student, $scope['exam'])->getData();
        $html = $this->response($student, $scope['exam'])->render();
        $this->assertSame(['workingDays' => 120, 'presentDays' => 112, 'absentDays' => 8], $after['transcriptView']['academicAttendance']);
        $this->assertStringContainsString('Academic Attendance', $html);
        $this->assertStringContainsString('120', $html);
        $this->assertStringContainsString('112', $html);
        $this->assertStringContainsString('8', $html);
        $this->assertSame($before['transcriptResult'], $after['transcriptResult']);
        $this->assertSame($before['transcriptView']['meritRank'], $after['transcriptView']['meritRank']);
    }

    public function test_single_transcript_preserves_fractional_component_marks_and_total(): void
    {
        $scope = $this->scope(1);
        $subject = $this->subject('Fractional Subject', 'Main', 50, 25, 25);
        $student = $this->student($scope);
        $this->mark($student, $scope, $subject, 17.25, 8.5, 8.25);

        $data = $this->response($student, $scope['exam'])->getData()['transcriptResult'];

        $this->assertSame(34.0, $data['mainRows'][0]['total']);
        $this->assertSame(17.25, $data['mainRows'][0]['cq']);
        $this->assertSame(8.5, $data['mainRows'][0]['mcq']);
        $this->assertSame(8.25, $data['mainRows'][0]['practical']);
        $this->assertSame('Pass', $data['mainRows'][0]['status']);
    }

    public function test_enabled_gpa_is_capped_and_optional_a_plus_bonus_is_applied(): void
    {
        config(['result_engine.transcript_enabled' => true]);
        $scope = $this->scope();
        $main = $this->subject('Main B', 'Main', 100);
        $optional = $this->subject('Optional A Plus', 'Optional', 100);
        $student = $this->student($scope, $optional->id);
        $this->mark($student, $scope, $main, 50);
        $this->mark($student, $scope, $optional, 80);

        $html = $this->html($student, $scope['exam']);

        $this->assertSummary($html, '5.00', 'A+');
        $this->assertMatchesRegularExpression('/Optional A Plus.*?80.*?A\+.*?5\.00/s', $html);
    }

    public function test_enabled_optional_f_does_not_fail_overall(): void
    {
        config(['result_engine.transcript_enabled' => true]);
        $scope = $this->scope();
        $main = $this->subject('Main Pass', 'Main', 100);
        $optional = $this->subject('Optional Fail', 'Optional', 100);
        $student = $this->student($scope, $optional->id);
        $this->mark($student, $scope, $main, 80);
        $this->mark($student, $scope, $optional, 0);

        $html = $this->html($student, $scope['exam']);

        $this->assertSummary($html, '5.00', 'A+');
        $this->assertStringContainsString('Status- Pass', $html);
        $this->assertStringContainsString('Optional Fail', $html);
        $this->assertSame(2, substr_count($html, 'class="failed-grade-cell"'));
    }

    public function test_enabled_compulsory_f_causes_failure(): void
    {
        [$html] = $this->singleMainResult('Failed Main', 'Main', 100, 32);
        $this->assertSummary($html, '0.00', 'F');
        $this->assertStringContainsString('Status- Fail', $html);
        $this->assertSame(2, substr_count($html, 'class="failed-grade-cell"'));
    }

    public function test_enabled_normalizes_fifty_mark_subject(): void
    {
        [$html] = $this->singleMainResult('Fifty Mark Main', 'Main', 50, 40);
        $this->assertSummary($html, '5.00', 'A+');
        $this->assertMatchesRegularExpression('/Fifty Mark Main.*?40.*?A\+.*?5\.00/s', $html);
    }

    public function test_enabled_zero_is_an_entered_failure(): void
    {
        [$html] = $this->singleMainResult('Zero Main', 'Main', 100, 0);
        $this->assertSummary($html, '0.00', 'F');
        $this->assertStringNotContainsString('Incomplete: missing marks for Zero Main', $html);
    }

    public function test_enabled_missing_compulsory_marks_are_incomplete(): void
    {
        config(['result_engine.transcript_enabled' => true]);
        $scope = $this->scope();
        $this->subject('Missing Main', 'Main', 100);
        $student = $this->student($scope);

        $html = $this->html($student, $scope['exam']);

        $this->assertSummary($html, 'Absent', 'Absent');
        $this->assertStringContainsString('No marks were entered for this student in this exam.', $html);
        $this->assertStringContainsString('Status- Absent', $html);
    }

    public function test_enabled_includes_compulsory_theory_subject(): void
    {
        [$html] = $this->singleMainResult('Theory Type', 'Theory', 100, 80);
        $this->assertSummary($html, '5.00', 'A+');
        $this->assertStringContainsString('Theory Type', $html);
    }

    public function test_enabled_displays_configured_pair_as_one_result(): void
    {
        config(['result_engine.transcript_enabled' => true]);
        $scope = $this->scope();
        $first = $this->subject('Bangla 1st Paper', 'Main', 100, 0, 0, 'bangla_1st_paper');
        $second = $this->subject('Bangla 2nd Paper', 'Main', 100, 0, 0, 'bangla_2nd_paper');
        $student = $this->student($scope);
        $this->mark($student, $scope, $first, 80);
        $this->mark($student, $scope, $second, 80);

        $html = $this->html($student, $scope['exam']);

        $this->assertSummary($html, '5.00', 'A+');
        $this->assertStringContainsString('(80 + 80) = 160', $html);
        $this->assertSame(1, substr_count($html, '<td>Bangla</td>'));
    }

    public function test_enabled_paired_mcq_one_sided_displays_numeric_only(): void
    {
        config(['result_engine.transcript_enabled' => true]);
        $scope = $this->scope();
        $first = $this->subject('English 1st Paper', 'Main', 0, 50, 0, 'english_1st_paper');
        $second = $this->subject('English 2nd Paper', 'Main', 0, 50, 0, 'english_2nd_paper');
        $student = $this->student($scope);
        $this->mark($student, $scope, $first, 0, 16, null);
        $this->mark($student, $scope, $second, 0, null, null);

        $html = $this->html($student, $scope['exam']);

        $this->assertMatchesRegularExpression(
            '/English<\/td>\s*<td>-<\/td>\s*<td>16<\/td>\s*<td>-<\/td>/s',
            $html
        );
        $this->assertStringNotContainsString('(16 +', $html);
    }

    public function test_enabled_paired_confirmed_blank_displays_effective_zero(): void
    {
        config(['result_engine.transcript_enabled' => true]);
        $scope = $this->scope();
        $first = $this->subject('English 1st Paper', 'Main', 0, 50, 0, 'english_1st_paper');
        $second = $this->subject('English 2nd Paper', 'Main', 0, 50, 0, 'english_2nd_paper');
        $student = $this->student($scope);
        $mark1 = $this->mark($student, $scope, $first, 0, null, null);
        $mark2 = $this->mark($student, $scope, $second, 0, null, null);

        $this->annotateConfirmedBlankOverride($scope, $first);
        $this->annotateConfirmedBlankOverride($scope, $second);

        $html = $this->html($student, $scope['exam']);

        $this->assertStringContainsString('(0 + 0) = 0', $html);
        $this->assertStringContainsString('Total Marks: 0', $html);
        $this->assertNotNull($mark1->fresh());
        $this->assertNotNull($mark2->fresh());
    }

    public function test_presenter_orders_common_then_science_and_keeps_pairs_stable(): void
    {
        $ordering = app(TranscriptSubjectOrderingService::class);

        $rows = [
            [
                'id' => '44', 'name' => 'Physics', 'isOptional' => false, 'isReligious' => false,
                'mappingCategories' => ['department_group'], 'mappingSortOrder' => 1, 'sortOrder' => 1,
                'sourceIds' => ['44'],
            ],
            [
                'id' => '45', 'name' => 'Chemistry', 'isOptional' => false, 'isReligious' => false,
                'mappingCategories' => ['department_group'], 'mappingSortOrder' => 2, 'sortOrder' => 2,
                'sourceIds' => ['45'],
            ],
            [
                'id' => '46', 'name' => 'Biology', 'isOptional' => false, 'isReligious' => false,
                'mappingCategories' => ['department_group'], 'mappingSortOrder' => 3, 'sortOrder' => 3,
                'sourceIds' => ['46'],
            ],
            [
                'id' => 'pair:bangla', 'name' => 'Bangla', 'isOptional' => false, 'isReligious' => false,
                'mappingCategories' => ['common'], 'mappingSortOrder' => 10, 'sortOrder' => 10,
                'sourceIds' => ['11', '12'],
            ],
            [
                'id' => 'pair:english', 'name' => 'English', 'isOptional' => false, 'isReligious' => false,
                'mappingCategories' => ['common'], 'mappingSortOrder' => 20, 'sortOrder' => 20,
                'sourceIds' => ['21', '22'],
            ],
            [
                'id' => '31', 'name' => 'Mathematics', 'isOptional' => false, 'isReligious' => false,
                'mappingCategories' => ['common'], 'mappingSortOrder' => 30, 'sortOrder' => 30,
                'sourceIds' => ['31'],
            ],
            [
                'id' => '32', 'name' => 'ICT', 'isOptional' => false, 'isReligious' => false,
                'mappingCategories' => ['common'], 'mappingSortOrder' => 40, 'sortOrder' => 40,
                'sourceIds' => ['32'],
            ],
            [
                'id' => '33', 'name' => 'Bangladesh and Global Studies', 'isOptional' => false, 'isReligious' => false,
                'mappingCategories' => ['common'], 'mappingSortOrder' => 50, 'sortOrder' => 50,
                'sourceIds' => ['33'],
            ],
            [
                'id' => '41', 'name' => 'Islam and moral education-111', 'isOptional' => false, 'isReligious' => true,
                'mappingCategories' => ['common'], 'mappingSortOrder' => 8, 'sortOrder' => 8,
                'sourceIds' => ['41'],
            ],
        ];

        $sorted = $ordering->sortMainRows($rows);
        $sortedNames = collect($sorted)->pluck('name')->values()->all();

        $this->assertSame([
            'Bangla',
            'English',
            'Mathematics',
            'ICT',
            'Bangladesh and Global Studies',
            'Islam and moral education-111',
            'Physics',
            'Chemistry',
            'Biology',
        ], $sortedNames);

        $banglaRow = collect($sorted)->firstWhere('name', 'Bangla');
        $englishRow = collect($sorted)->firstWhere('name', 'English');
        $this->assertSame(['11', '12'], $banglaRow['sourceIds']);
        $this->assertSame(['21', '22'], $englishRow['sourceIds']);
    }

    public function test_single_print_uses_shared_pdf_header_information_grading_and_footer_structure(): void
    {
        [$html] = $this->singleMainResult('Grid Main', 'Main', 100, 80);

        $this->assertStringContainsString('class="report-header"', $html);
        $this->assertStringContainsString('class="title"', $html);
        $this->assertMatchesRegularExpression('/class="[^"]*\\bmeta-wrap\\b[^"]*"/', $html);
        $this->assertStringContainsString('class="student-info"', $html);
        $this->assertStringContainsString('class="grading-table-wrap"', $html);
        $this->assertStringContainsString('class="grading-table"', $html);
        $this->assertStringContainsString('marksheet transcript-page', $html);
        $this->assertStringContainsString('class="transcript-footer col-12"', $html);
        $this->assertStringContainsString('class="signature-row"', $html);
        $this->assertStringContainsString('@page { size: A4 portrait; margin: 8mm; }', $html);
        $this->assertStringContainsString('.d-print-none, .no-print { display: none !important;', $html);
    }

    #[DataProvider('failedComponentProvider')]
    public function test_enabled_feature_wise_component_failure(string $component): void
    {
        config(['result_engine.transcript_enabled' => true]);
        $scope = $this->scope(1);
        $subject = $this->subject('Component Subject', 'Main', 50, 25, 25);
        $student = $this->student($scope);
        $values = ['cq' => 30, 'mcq' => 15, 'practical' => 15];
        $values[$component] = 1;
        $this->mark($student, $scope, $subject, $values['cq'], $values['mcq'], $values['practical']);

        $html = $this->html($student, $scope['exam']);

        $this->assertSummary($html, '0.00', 'F');
        $this->assertStringContainsString('Status- Fail', $html);
    }

    public static function failedComponentProvider(): array
    {
        return [['cq'], ['mcq'], ['practical']];
    }

    public function test_presenter_exception_fails_closed_without_writes(): void
    {
        config(['result_engine.transcript_enabled' => true]);
        $this->app->bind(TranscriptResultPresenter::class, fn () => new class extends TranscriptResultPresenter {
            public function present(StudentResult $result, iterable $subjects, iterable $marks): array
            {
                throw new RuntimeException('simulated');
            }
        });
        $scope = $this->scope();
        $subject = $this->subject('Fallback Main', 'Main', 100);
        $student = $this->student($scope);
        $mark = $this->mark($student, $scope, $subject, 80);
        $before = $mark->only(['subjectMarks', 'objectMarks', 'practicalMarks', 'laterGrade', 'gradePoint']);

        $response = app(MarksheetController::class)->generateMarksheet(Request::create('/marksheet/generate', 'GET', [
            'studentId' => $student->id, 'examId' => $scope['exam']->id,
        ]));

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertEquals($before, $mark->fresh()->only(array_keys($before)));
        $this->assertDatabaseCount('marksheets', 1);
    }

    public function test_enabled_transcript_is_read_only(): void
    {
        config(['result_engine.transcript_enabled' => true]);
        $scope = $this->scope();
        $optional = $this->subject('Optional', 'Optional', 100);
        $main = $this->subject('Main', 'Main', 100);
        $student = $this->student($scope, $optional->id);
        $mark = $this->mark($student, $scope, $main, 80);
        $this->mark($student, $scope, $optional, 70);
        $beforeMarks = Marksheet::orderBy('id')->get()->map->only(['id', 'subjectMarks', 'objectMarks', 'practicalMarks', 'laterGrade', 'gradePoint'])->all();
        $fourth = $student->fourthSubjectId;

        $this->html($student, $scope['exam']);

        $this->assertSame($beforeMarks, Marksheet::orderBy('id')->get()->map->only(['id', 'subjectMarks', 'objectMarks', 'practicalMarks', 'laterGrade', 'gradePoint'])->all());
        $this->assertSame($fourth, $student->fresh()->fourthSubjectId);
        $this->assertDatabaseCount('marksheets', 2);
        $this->assertSame(0, Placement::count());
        $this->assertSame(0, ResultArchive::count());
        $this->assertSame(0, ResultPublish::count());
        $this->assertSame(0, PromotionAuditLog::count());
        $this->assertNotNull($mark->fresh());
    }

    private function singleMainResult(string $name, string $type, float $full, float $obtained): array
    {
        config(['result_engine.transcript_enabled' => true]);
        $scope = $this->scope();
        $subject = $this->subject($name, $type, $full);
        $student = $this->student($scope);
        $this->mark($student, $scope, $subject, $obtained);
        return [$this->html($student, $scope['exam']), $student, $scope];
    }

    private function scope(int $passingSystem = 2): array
    {
        $session = new sessionManage(); $session->session = '2026'; $session->save();
        $class = new classManage(); $class->className = 'Class 10'; $class->save();
        $section = new sectionManage(); $section->section = 'A'; $section->save();
        $department = new Department(); $department->departmentName = 'Science'; $department->save();
        $exam = new Exam(); $exam->examName = 'Annual'; $exam->passingSystem = $passingSystem; $exam->save();
        return compact('session', 'class', 'section', 'department', 'exam');
    }

    private function student(array $scope, ?int $fourthSubjectId = null): newAdmission
    {
        $student = new newAdmission();
        $student->stdId = random_int(100000, 999999999);
        $student->fullName = 'Output Student';
        $student->sessName = $scope['session']->id;
        $student->className = $scope['class']->id;
        $student->sectionName = $scope['section']->id;
        $student->departmentName = $scope['department']->id;
        $student->rollNumber = '01';
        $student->fourthSubjectId = $fourthSubjectId;
        $student->save();
        return $student;
    }

    private function subject(string $name, string $type, float $cq, float $mcq = 0, float $practical = 0, ?string $alias = null): Subject
    {
        $subject = new Subject();
        $subject->subjectName = $name; $subject->alias = $alias ?? strtolower(str_replace(' ', '_', $name));
        $subject->subjectType = $type; $subject->assign_class = '0';
        $subject->CQ = $cq; $subject->MCQ = $mcq; $subject->Practical = $practical; $subject->save();
        return $subject;
    }

    private function mark(newAdmission $student, array $scope, Subject $subject, float $cq, ?float $mcq = null, ?float $practical = null): Marksheet
    {
        $this->ensureCurriculumMapping($scope, $subject);

        return Marksheet::create([
            'studentId' => $student->id, 'classId' => $scope['class']->id, 'sessionId' => $scope['session']->id,
            'groupId' => $scope['section']->id, 'examId' => $scope['exam']->id, 'subjectId' => $subject->id,
            'subjectMarks' => $cq, 'objectMarks' => $mcq, 'practicalMarks' => $practical,
            'totalMarks' => $cq + ($mcq ?? 0) + ($practical ?? 0), 'gradePoint' => 99, 'laterGrade' => 'Stored',
        ]);
    }

    private function ensureCurriculumMapping(array $scope, Subject $subject): void
    {
        $exists = DB::table('curriculum_subject_mappings')
            ->where('session_id', (string) $scope['session']->id)
            ->where('class_id', (string) $scope['class']->id)
            ->where('section_id', (string) $scope['section']->id)
            ->where('department_id', (string) $scope['department']->id)
            ->where('subject_id', (int) $subject->id)
            ->exists();

        if ($exists) {
            return;
        }

        $nextOrder = (int) (DB::table('curriculum_subject_mappings')
            ->where('session_id', (string) $scope['session']->id)
            ->where('class_id', (string) $scope['class']->id)
            ->where('section_id', (string) $scope['section']->id)
            ->where('department_id', (string) $scope['department']->id)
            ->max('sort_order') ?? 0) + 1;

        DB::table('curriculum_subject_mappings')->insert([
            'session_id' => (string) $scope['session']->id,
            'class_id' => (string) $scope['class']->id,
            'section_id' => (string) $scope['section']->id,
            'department_id' => (string) $scope['department']->id,
            'subject_id' => (int) $subject->id,
            'mapping_type' => 'main',
            'sort_order' => $nextOrder,
            'is_active' => 1,
            'source' => 'test-fixture',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function mapSubjectWithOrder(array $scope, Subject $subject, int $sortOrder, bool $asCommon = false): void
    {
        $mappedDepartmentId = $asCommon ? null : (int) $scope['department']->id;

        $deleteQuery = DB::table('curriculum_subject_mappings')
            ->where('session_id', (string) $scope['session']->id)
            ->where('class_id', (string) $scope['class']->id)
            ->where('section_id', (string) $scope['section']->id)
            ->where('subject_id', (int) $subject->id);

        $deleteQuery->delete();

        DB::table('curriculum_subject_mappings')->insert([
            'session_id' => (string) $scope['session']->id,
            'class_id' => (string) $scope['class']->id,
            'section_id' => (string) $scope['section']->id,
            'department_id' => $mappedDepartmentId === null ? null : (string) $mappedDepartmentId,
            'subject_id' => (int) $subject->id,
            'mapping_type' => 'main',
            'sort_order' => $sortOrder,
            'is_active' => 1,
            'source' => 'test-fixture',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function annotateConfirmedBlankOverride(array $scope, Subject $subject): void
    {
        DB::table('marks_scope_states')->insert([
            'sessionId' => (string) $scope['session']->id,
            'classId' => (string) $scope['class']->id,
            'groupId' => (string) $scope['section']->id,
            'examId' => (string) $scope['exam']->id,
            'subjectId' => (string) $subject->id,
            'status' => MarksScopeState::STATUS_CONFIRMED,
            'revision' => 1,
            'confirmed_by' => null,
            'confirmed_at' => now(),
            'reopened_by' => null,
            'reopened_at' => null,
            'reopen_reason' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('result_lifecycle_events')->insert([
            'event_uuid' => (string) Str::uuid(),
            'correlation_uuid' => null,
            'actor_id' => null,
            'actor_role' => 'test',
            'action' => 'subject_confirmed',
            'entity_type' => 'subject_scope',
            'sessionId' => (string) $scope['session']->id,
            'classId' => (string) $scope['class']->id,
            'groupId' => (string) $scope['section']->id,
            'examId' => (string) $scope['exam']->id,
            'subjectId' => (string) $subject->id,
            'studentId' => null,
            'before_state' => json_encode(['status' => 'draft', 'revision' => 1]),
            'after_state' => json_encode(['status' => 'confirmed', 'revision' => 1]),
            'change_set' => json_encode(['blank_override' => true]),
            'reason' => null,
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);
    }

    private function html(newAdmission $student, Exam $exam): string
    {
        return $this->response($student, $exam)->render();
    }

    private function response(newAdmission $student, Exam $exam): \Illuminate\View\View
    {
        return app(MarksheetController::class)->generateMarksheet(Request::create('/marksheet/generate', 'GET', [
            'studentId' => $student->id, 'examId' => $exam->id,
        ]));
    }

    private function assertSummary(string $html, string $gpa, string $letter): void
    {
        preg_match('/Letter Grade:\s*([^<]+)/', $html, $letterMatch);
        preg_match('/(?:Grade Point|GPA):\s*([^<]+)/', $html, $gpaMatch);

        $this->assertNotEmpty($letterMatch, 'Transcript letter grade was not rendered.');
        $this->assertNotEmpty($gpaMatch, 'Transcript GPA was not rendered.');
        $this->assertSame($letter, trim($letterMatch[1]));
        $this->assertSame($gpa, trim($gpaMatch[1]));
    }

    private function extractSubjectNamesFromSection(string $html, string $heading): array
    {
        $escapedHeading = preg_quote($heading, '/');
        if (!preg_match('/<h3[^>]*>\s*'.$escapedHeading.'\s*<\/h3>\s*<table[^>]*>.*?<tbody>(.*?)<\/tbody>/si', $html, $sectionMatch)) {
            return [];
        }

        if (!preg_match_all('/<tr[^>]*>\s*<td[^>]*>(.*?)<\/td>/si', $sectionMatch[1], $rows)) {
            return [];
        }

        return array_values(array_map(
            fn ($value) => trim(html_entity_decode(strip_tags((string) $value))),
            $rows[1]
        ));
    }
}
