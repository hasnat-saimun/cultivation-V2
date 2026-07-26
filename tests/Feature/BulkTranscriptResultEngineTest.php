<?php

namespace Tests\Feature;

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
use App\Services\ResultCalculation\BoardResultCalculator;
use App\Services\ResultCalculation\BulkTranscriptResultBuilder;
use App\Services\ResultCalculation\ResultCalculationInputBuilder;
use App\Services\ResultCalculation\StudentResult;
use App\Services\ResultCalculation\TranscriptResultPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class BulkTranscriptResultEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=', 'cache.default' => 'array']);
    }

    public function test_bulk_view_remains_centralized_and_caps_gpa_when_flag_is_disabled(): void
    {
        config(['result_engine.bulk_transcript_enabled' => false]);
        $scope = $this->scope();
        $main = $this->subject('Main', 'Main', 100);
        $optional = $this->subject('Optional', 'Optional', 100);
        $student = $this->student($scope, '01', $optional->id);
        $this->mark($student, $scope, $main, 80);
        $this->mark($student, $scope, $optional, 80);
        $this->loadMarks([$student], $scope['exam']);

        $transcripts = app(BulkTranscriptResultBuilder::class)->build([$student], $scope['exam']);
        $html = $this->render($transcripts, $scope['exam']);

        $this->assertSummary($html, '5.00', 'A+');
        $this->assertStringContainsString('Remark- Pass', $html);
    }

    public function test_bulk_controller_always_invokes_centralized_builder(): void
    {
        $scope = $this->scope();
        $subject = $this->subject('Main', 'Main', 100);
        $student = $this->student($scope, '01');
        $this->mark($student, $scope, $subject, 80);
        $fake = new class(app(BoardResultCalculator::class), app(TranscriptResultPresenter::class), app(ResultCalculationInputBuilder::class)) extends BulkTranscriptResultBuilder {
            public int $calls = 0;
            public array $studentIds = [];
            public function buildWithGradeRows(iterable $students, Exam $exam, iterable $gradeRows): array
            {
                $this->calls++; $students = collect($students); $this->studentIds = $students->pluck('id')->all();
                return parent::buildWithGradeRows($students, $exam, $gradeRows);
            }
        };
        $this->app->instance(BulkTranscriptResultBuilder::class, $fake);
        $request = fn () => Request::create('/transcripts/bulk/pdf', 'POST', ['examId' => $scope['exam']->id, 'stdIds' => [$student->id]]);

        config(['result_engine.bulk_transcript_enabled' => false]);
        app(\App\Http\Controllers\MarksheetController::class)->bulkTranscriptPdf($request());
        $this->assertSame(1, $fake->calls);

        config(['result_engine.bulk_transcript_enabled' => true]);
        app(\App\Http\Controllers\MarksheetController::class)->bulkTranscriptPdf($request());
        $this->assertSame(2, $fake->calls);
        $this->assertSame([$student->id], $fake->studentIds);
    }

    public function test_enabled_bulk_processes_multiple_students_independently(): void
    {
        $scope = $this->scope();
        $main = $this->subject('Main', 'Main', 100);
        $optional = $this->subject('Optional', 'Optional', 100);
        $pass = $this->student($scope, '01', $optional->id);
        $optionalFail = $this->student($scope, '02', $optional->id);
        $compulsoryFail = $this->student($scope, '03');
        $incomplete = $this->student($scope, '04');
        $this->mark($pass, $scope, $main, 80); $this->mark($pass, $scope, $optional, 80);
        $this->mark($optionalFail, $scope, $main, 80); $this->mark($optionalFail, $scope, $optional, 0);
        $this->mark($compulsoryFail, $scope, $main, 32);
        $this->loadMarks([$pass, $optionalFail, $compulsoryFail, $incomplete], $scope['exam']);

        $transcripts = app(BulkTranscriptResultBuilder::class)->build([$pass, $optionalFail, $compulsoryFail, $incomplete], $scope['exam']);

        $this->assertSame(['Pass', 'Pass', 'Fail', 'Incomplete'], array_column(array_column($transcripts, 'result'), 'status'));
        $this->assertSame([5.0, 5.0, 0.0, null], array_column(array_column($transcripts, 'result'), 'gpa'));
        $this->assertSame(1, $transcripts[0]['result']['mainRows'] ? count($transcripts[0]['result']['mainRows']) : 0);
        $this->assertSame(3.0, $transcripts[0]['result']['optionalBonus']);
        $this->assertSame(0.0, $transcripts[1]['result']['optionalBonus']);
    }

    #[DataProvider('parityScenarioProvider')]
    public function test_single_and_bulk_prepared_results_have_calculation_parity(string $scenario): void
    {
        [$student, $scope] = $this->scenario($scenario);
        $this->loadMarks([$student], $scope['exam']);
        $inputBuilder = app(ResultCalculationInputBuilder::class);
        $subjects = $inputBuilder->subjectsForStudent($student);
        $single = app(TranscriptResultPresenter::class)->present(
            app(BoardResultCalculator::class)->calculate($student, $scope['exam'], $student->marksheet, $subjects),
            $subjects,
            $student->marksheet,
        );
        $bulk = app(BulkTranscriptResultBuilder::class)->build([$student], $scope['exam'])[0]['result'];

        foreach (['gpa', 'status', 'letterGrade', 'optionalBonus', 'failedSubjects', 'missingSubjects', 'componentFailures'] as $key) {
            $this->assertSame($single[$key], $bulk[$key], "Parity failed for {$scenario}:{$key}");
        }
        $this->assertSame($this->rowCalculationData($single), $this->rowCalculationData($bulk));
    }

    public static function parityScenarioProvider(): array
    {
        return array_map(fn ($scenario) => [$scenario], [
            'normal', 'optional_a_plus', 'optional_f', 'compulsory_f', 'missing',
            'fifty_mark', 'theory', 'pair', 'cq_failure', 'mcq_failure', 'practical_failure', 'zero',
        ]);
    }

    public function test_selected_students_and_exam_scoping_prevent_mark_leakage(): void
    {
        $scope = $this->scope();
        $otherExam = new Exam(); $otherExam->examName = 'Other'; $otherExam->passingSystem = 2; $otherExam->save();
        $subject = $this->subject('Scoped Subject', 'Main', 100);
        $selected = $this->student($scope, '01');
        $notSelected = $this->student($scope, '02');
        $this->mark($selected, $scope, $subject, 80);
        $this->mark($selected, $scope, $subject, 0, null, null, $otherExam);
        $this->mark($notSelected, $scope, $subject, 32);
        $this->loadMarks([$selected], $scope['exam']);

        $transcripts = app(BulkTranscriptResultBuilder::class)->build([$selected], $scope['exam']);
        $html = $this->render($transcripts, $scope['exam']);

        $this->assertCount(1, $transcripts);
        $this->assertSame('Pass', $transcripts[0]['result']['status']);
        $this->assertSame(80.0, $transcripts[0]['result']['mainRows'][0]['total']);
        $this->assertStringContainsString($selected->stdId, $html);
        $this->assertStringNotContainsString($notSelected->stdId, $html);
    }

    public function test_student_list_filters_class_session_section_and_department(): void
    {
        $scope = $this->scope();
        $matching = $this->student($scope, '01'); $matching->departmentName = 7; $matching->save();
        $other = $this->student($scope, '02'); $other->sectionName = 999; $other->departmentName = 7; $other->save();
        $response = app(\App\Http\Controllers\MarksheetController::class)->transcriptList(Request::create('/transcripts/bulk', 'GET', [
            'examId' => $scope['exam']->id, 'classId' => $scope['class']->id, 'sessionId' => $scope['session']->id,
            'sectionId' => $scope['section']->id, 'departmentId' => 7,
        ]));

        $this->assertSame([$matching->id], $response->getData()['students']->pluck('id')->all());
    }

    public function test_one_student_calculation_failure_blocks_the_complete_batch(): void
    {
        $scope = $this->scope();
        $subject = $this->subject('Main', 'Main', 100);
        $first = $this->student($scope, '01'); $second = $this->student($scope, '02');
        $this->mark($first, $scope, $subject, 80); $this->mark($second, $scope, $subject, 70);
        $this->loadMarks([$first, $second], $scope['exam']);
        $presenter = new class extends TranscriptResultPresenter {
            private int $calls = 0;
            public function presentWithGradeRows(StudentResult $result, iterable $subjects, iterable $marks, iterable $gradeRows): array
            {
                if (++$this->calls === 1) throw new RuntimeException('simulated');
                return parent::presentWithGradeRows($result, $subjects, $marks, $gradeRows);
            }
        };
        $builder = new BulkTranscriptResultBuilder(app(BoardResultCalculator::class), $presenter, app(ResultCalculationInputBuilder::class));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('simulated');
        $builder->build([$first, $second], $scope['exam']);
    }

    public function test_enabled_bulk_render_is_read_only_and_preserves_page_structure(): void
    {
        $scope = $this->scope();
        $main = $this->subject('Main', 'Main', 50);
        $optional = $this->subject('Optional', 'Optional', 100);
        $first = $this->student($scope, '01', $optional->id); $second = $this->student($scope, '02');
        $this->mark($first, $scope, $main, 40, 0, 0); $this->mark($first, $scope, $optional, 80);
        $this->mark($second, $scope, $main, 0, 0, 0);
        $this->loadMarks([$first, $second], $scope['exam']);
        $before = Marksheet::orderBy('id')->get()->map->only(['id', 'subjectMarks', 'objectMarks', 'practicalMarks', 'totalMarks', 'laterGrade', 'gradePoint'])->all();
        $fourths = newAdmission::orderBy('id')->pluck('fourthSubjectId', 'id')->all();

        $transcripts = app(BulkTranscriptResultBuilder::class)->build([$first, $second], $scope['exam']);
        $html = $this->render($transcripts, $scope['exam']);

        $this->assertSame($before, Marksheet::orderBy('id')->get()->map->only(['id', 'subjectMarks', 'objectMarks', 'practicalMarks', 'totalMarks', 'laterGrade', 'gradePoint'])->all());
        $this->assertSame($fourths, newAdmission::orderBy('id')->pluck('fourthSubjectId', 'id')->all());
        $this->assertSame(0, Placement::count());
        $this->assertSame(0, ResultArchive::count());
        $this->assertSame(0, ResultPublish::count());
        $this->assertDatabaseCount('marksheets', 3);
        $this->assertSame(2, substr_count($html, 'transcript-page marksheet'));
        $this->assertStringContainsString('@page { size: A4 portrait', $html);
        $this->assertStringContainsString('Output Student', $html);
    }

    public function test_complete_bulk_blade_render_executes_no_database_queries(): void
    {
        $scope = $this->scope();
        $subject = $this->subject('Main', 'Main', 100);
        $student = $this->student($scope, '01');
        $this->mark($student, $scope, $subject, 80);
        $this->loadMarks([$student], $scope['exam']);
        $transcripts = app(BulkTranscriptResultBuilder::class)->build([$student], $scope['exam']);
        $viewData = $this->bulkView($scope['exam']);

        $queries = [];
        DB::listen(function ($query) use (&$queries): void { $queries[] = $query->sql; });
        view('result.bulk-transcript-pdf', compact('transcripts') + ['bulkView' => $viewData])->render();

        $this->assertSame([], $queries);
    }

    public function test_bulk_builder_query_count_is_bounded_for_class_sized_batch(): void
    {
        $scope = $this->scope();
        $subject = $this->subject('Main', 'Main', 100);
        $students = [];
        foreach (range(1, 25) as $roll) {
            $student = $this->student($scope, str_pad((string) $roll, 2, '0', STR_PAD_LEFT));
            $this->mark($student, $scope, $subject, 80);
            $students[] = $student;
        }
        $this->loadMarks($students, $scope['exam']);
        DB::enableQueryLog();
        DB::flushQueryLog();
        app(BulkTranscriptResultBuilder::class)->build([$students[0]], $scope['exam']);
        $oneStudentQueries = count(DB::getQueryLog());
        DB::flushQueryLog();
        app(BulkTranscriptResultBuilder::class)->build($students, $scope['exam']);
        $classQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual($oneStudentQueries + 2, $classQueries);
    }

    private function scenario(string $scenario): array
    {
        $feature = in_array($scenario, ['cq_failure', 'mcq_failure', 'practical_failure'], true);
        $scope = $this->scope($feature ? 1 : 2);
        $student = $this->student($scope, '01');
        if ($scenario === 'pair') {
            $a = $this->subject('Bangla 1st Paper', 'Main', 100, 0, 0, 'bangla_1st_paper');
            $b = $this->subject('Bangla 2nd Paper', 'Main', 100, 0, 0, 'bangla_2nd_paper');
            $this->mark($student, $scope, $a, 80); $this->mark($student, $scope, $b, 80);
        } elseif (str_ends_with($scenario, '_failure')) {
            $subject = $this->subject('Component', 'Main', 50, 25, 25);
            $marks = ['cq' => 30, 'mcq' => 15, 'practical' => 15]; $marks[str_replace('_failure', '', $scenario)] = 1;
            $this->mark($student, $scope, $subject, $marks['cq'], $marks['mcq'], $marks['practical']);
        } else {
            $type = $scenario === 'theory' ? 'Theory' : 'Main';
            $full = $scenario === 'fifty_mark' ? 50 : 100;
            $main = $this->subject('Scenario Main', $type, $full);
            if ($scenario !== 'missing') $this->mark($student, $scope, $main, match ($scenario) { 'compulsory_f' => 32, 'zero' => 0, 'fifty_mark' => 40, default => 80 });
            if (in_array($scenario, ['optional_a_plus', 'optional_f'], true)) {
                $optional = $this->subject('Scenario Optional', 'Optional', 100); $student->fourthSubjectId = $optional->id; $student->save();
                $this->mark($student, $scope, $optional, $scenario === 'optional_f' ? 0 : 80);
            }
        }
        return [$student, $scope];
    }

    private function rowCalculationData(array $result): array
    {
        return collect(array_merge($result['mainRows'], $result['optionalRows']))->map(fn ($row) => [
            $row['name'], $row['total'], $row['grade'], $row['gradePoint'], $row['status'], $row['componentFailures'],
        ])->all();
    }

    private function render(array $transcripts, Exam $exam): string
    {
        return view('result.bulk-transcript-pdf', compact('transcripts') + ['bulkView' => $this->bulkView($exam)])->render();
    }

    private function bulkView(Exam $exam): array
    {
        return [
            'title' => 'Academic Transcript',
            'examName' => $exam->examName,
            'institute' => ['name' => 'Test Institute', 'address' => '', 'mobile' => '', 'email' => '', 'logoUrl' => null],
            'principalSignatureUrl' => null,
            'gradeLegend' => [],
        ];
    }

    private function loadMarks(array $students, Exam $exam): void
    {
        collect($students)->each(fn ($student) => $student->load(['marksheet' => fn ($query) => $query->where('examId', $exam->id)->orderBy('subjectId')]));
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

    private function student(array $scope, string $roll, ?int $fourthSubjectId = null): newAdmission
    {
        $student = new newAdmission(); $student->stdId = (string) random_int(100000, 999999999); $student->fullName = 'Output Student';
        $student->sessName = $scope['session']->id; $student->className = $scope['class']->id; $student->sectionName = $scope['section']->id;
        $student->departmentName = $scope['department']->id;
        $student->rollNumber = $roll; $student->fourthSubjectId = $fourthSubjectId; $student->save(); return $student;
    }

    private function subject(string $name, string $type, float $cq, float $mcq = 0, float $practical = 0, ?string $alias = null): Subject
    {
        $subject = new Subject(); $subject->subjectName = $name; $subject->alias = $alias ?? strtolower(str_replace(' ', '_', $name));
        $subject->subjectType = $type; $subject->assign_class = '0'; $subject->CQ = $cq; $subject->MCQ = $mcq; $subject->Practical = $practical; $subject->save(); return $subject;
    }

    private function mark(newAdmission $student, array $scope, Subject $subject, float $cq, ?float $mcq = null, ?float $practical = null, ?Exam $exam = null): Marksheet
    {
        $this->ensureCurriculumMapping($scope, $subject);

        return Marksheet::create(['studentId' => $student->id, 'classId' => $scope['class']->id, 'sessionId' => $scope['session']->id,
            'groupId' => $scope['section']->id, 'examId' => ($exam ?? $scope['exam'])->id, 'subjectId' => $subject->id,
            'subjectMarks' => $cq, 'objectMarks' => $mcq, 'practicalMarks' => $practical, 'totalMarks' => $cq + ($mcq ?? 0) + ($practical ?? 0),
            'gradePoint' => 99, 'laterGrade' => 'Stored']);
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

    private function assertSummary(string $html, string $gpa, string $letter): void
    {
        preg_match('/Letter Grade:\s*([^<]+)<\/th>\s*<th[^>]*>Grade Point:\s*([^<]+)/', $html, $match);
        $this->assertNotEmpty($match); $this->assertSame($letter, trim($match[1])); $this->assertSame($gpa, trim($match[2]));
    }
}
