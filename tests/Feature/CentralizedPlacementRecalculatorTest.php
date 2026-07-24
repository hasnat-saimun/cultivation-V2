<?php

namespace Tests\Feature;

use App\Models\classManage;
use App\Models\Exam;
use App\Models\Marksheet;
use App\Models\newAdmission;
use App\Models\Placement;
use App\Models\ResultPublish;
use App\Models\sectionManage;
use App\Models\ServerConfig;
use App\Models\sessionManage;
use App\Models\Subject;
use App\Services\ResultCalculation\CentralizedPlacementRecalculator;
use App\Services\ResultCalculation\PlacementRecalculationException;
use App\Services\ResultCalculation\RankingMethodResolver;
use App\Http\Controllers\CultivationController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class CentralizedPlacementRecalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['result_engine.placement_enabled' => true, 'cache.default' => 'array']);
    }

    public function test_migration_setting_and_runtime_fallback_contract(): void
    {
        $this->assertTrue(Schema::hasColumn('server_configs', 'ranking_method'));
        $config = new ServerConfig();
        $config->instituteName = 'Existing Institute';
        $config->address = 'Unchanged';
        $config->save();
        $this->assertSame('grading', $config->fresh()->ranking_method);
        $this->assertSame('Unchanged', $config->fresh()->address);

        $config->ranking_method = 'total_marks';
        $config->save();
        $this->assertSame('total_marks', app(RankingMethodResolver::class)->resolve()['method']);

        $config->ranking_method = 'invalid';
        $config->save();
        $resolved = app(RankingMethodResolver::class)->resolve();
        $this->assertSame('grading', $resolved['method']);
        $this->assertSame('RANKING_METHOD_INVALID', $resolved['warnings'][0]['code']);

        $html = view('cultivation.configuration', ['errors' => new ViewErrorBag()])->render();
        $this->assertStringContainsString('Merit Ranking Method', $html);
        $this->assertStringContainsString('Total Marks Method', $html);
    }

    public function test_settings_workflow_accepts_supported_values_and_rejects_invalid_value(): void
    {
        foreach (['grading', 'total_marks'] as $method) {
            $request = Request::create('/configuration/save', 'POST', ['ranking_method' => $method]);
            $request->setLaravelSession(app('session')->driver());
            app(CultivationController::class)->saveConfig($request);
            $this->assertSame($method, ServerConfig::query()->latest('id')->value('ranking_method'));
        }

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $request = Request::create('/configuration/save', 'POST', ['ranking_method' => 'arbitrary']);
        $request->setLaravelSession(app('session')->driver());
        app(CultivationController::class)->saveConfig($request);
    }

    public function test_grading_method_uses_competition_ranking_and_roll_only_orders_ties(): void
    {
        $scope = $this->scope('grading');
        $subject = $this->subject('Main', 'Main', 100);
        $students = [
            $this->student($scope, '09'), $this->student($scope, '04'),
            $this->student($scope, '02'), $this->student($scope, '01'),
        ];
        foreach ([80, 70, 70, 60] as $index => $mark) $this->mark($students[$index], $scope, $subject, $mark);

        $report = $this->service()->recalculate(...$this->args($scope));
        $rows = Placement::where('status', 'Pass')->orderBy('position')->orderBy('studentId')->get()->keyBy('studentId');

        $this->assertSame([1, 2, 2, 4], array_map(fn ($student) => (int) $rows[(string) $student->id]->position, $students));
        $tiedDisplay = Placement::where('position', 2)->get()->sortBy(fn ($row) => (int) $row->studentId)->pluck('studentId')->all();
        $this->assertCount(2, $tiedDisplay);
        $this->assertSame(4, $report['passedRanked']);
    }

    public function test_setting_changes_pass_order_but_not_existing_rows_until_recalculation(): void
    {
        $scope = $this->scope('grading');
        $main = $this->subject('Main', 'Main', 100);
        $optional = $this->subject('Fourth', 'Optional', 100);
        $bonusStudent = $this->student($scope, '01', $optional->id);
        $marksStudent = $this->student($scope, '02');
        $this->mark($bonusStudent, $scope, $main, 70);
        $this->mark($bonusStudent, $scope, $optional, 80);
        $this->mark($marksStudent, $scope, $main, 79);

        $this->service()->recalculate(...$this->args($scope));
        $this->assertSame(1, (int) Placement::where('studentId', $bonusStudent->id)->value('position'));
        ServerConfig::latest('id')->first()->update(['ranking_method' => 'total_marks']);
        $this->assertSame(1, (int) Placement::where('studentId', $bonusStudent->id)->value('position'));

        $dry = $this->service()->recalculate(...$this->args($scope, dryRun: true));
        $this->assertSame('total_marks', $dry['rankingMethod']);
        $this->service()->recalculate(...$this->args($scope));
        $this->assertSame(1, (int) Placement::where('studentId', $marksStudent->id)->value('position'));
        $this->assertSame(70, (int) Placement::where('studentId', $bonusStudent->id)->value('totalMarks'));
    }

    public function test_fail_series_is_separate_and_incomplete_is_unranked(): void
    {
        $scope = $this->scope('total_marks');
        $subject = $this->subject('Main', 'Main', 100);
        $pass = $this->student($scope, '01');
        $failA = $this->student($scope, '02');
        $failB = $this->student($scope, '03');
        $incomplete = $this->student($scope, '04');
        $this->mark($pass, $scope, $subject, 80);
        $this->mark($failA, $scope, $subject, 32);
        $this->mark($failB, $scope, $subject, 20);

        $this->service()->recalculate(...$this->args($scope));
        $this->assertSame(1, (int) Placement::where('studentId', $pass->id)->value('position'));
        $this->assertSame(1, (int) Placement::where('studentId', $failA->id)->value('position'));
        $this->assertSame(2, (int) Placement::where('studentId', $failB->id)->value('position'));
        $this->assertSame('Incomplete', Placement::where('studentId', $incomplete->id)->value('status'));
        $this->assertNull(Placement::where('studentId', $incomplete->id)->value('position'));
    }

    public function test_publication_flag_and_dry_run_preserve_existing_scope_after_database_duplicate_prevention(): void
    {
        $scope = $this->scope('grading');
        $subject = $this->subject('Main', 'Main', 100);
        $student = $this->student($scope, '01');
        $this->mark($student, $scope, $subject, 80);
        $existing = $this->placement($student, $scope, 9);

        ResultPublish::create(['examId'=>$scope['exam']->id,'classId'=>$scope['class']->id,'sessionId'=>$scope['session']->id,'groupId'=>$scope['section']->id]);
        ResultPublish::first()->forceFill(['status' => ResultPublish::STATUS_UNPUBLISHED])->save();
        $this->assertFalse($this->service()->recalculate(...$this->args($scope, dryRun: true))['publicationLocked']);
        ResultPublish::first()->forceFill(['status' => ResultPublish::STATUS_PUBLISHED])->save();
        $dry = $this->service()->recalculate(...$this->args($scope, dryRun: true));
        $this->assertTrue($dry['publicationLocked']);
        $this->assertTrue($dry['forceRequired']);
        $this->assertTrue($dry['noRecordsModified']);
        $this->assertDatabaseHas('exam_placements', ['id' => $existing->id, 'position' => 9]);

        $this->expectException(PlacementRecalculationException::class);
        $this->service()->recalculate(...$this->args($scope));
    }

    public function test_force_never_bypasses_preflight_and_flag_off_allows_only_dry_run(): void
    {
        $scope = $this->scope('grading');
        $subject = $this->subject('Main', 'Main', 100);
        $student = $this->student($scope, '01');
        $this->mark($student, $scope, $subject, 80);
        ResultPublish::create(['examId'=>$scope['exam']->id,'classId'=>$scope['class']->id,'sessionId'=>$scope['session']->id,'groupId'=>$scope['section']->id]);

        Log::spy();
        $this->service()->recalculate(...$this->args($scope, force: true));
        Log::shouldHaveReceived('info')->once();

        config(['result_engine.placement_enabled' => false]);
        $this->assertTrue($this->service()->recalculate(...$this->args($scope, dryRun: true, force: true))['noRecordsModified']);
        $this->expectException(PlacementRecalculationException::class);
        $this->service()->recalculate(...$this->args($scope, force: true));
    }

    public function test_command_dry_run_is_flag_independent_and_real_write_is_explicit(): void
    {
        $scope = $this->scope('grading');
        $subject = $this->subject('Main', 'Main', 100);
        $student = $this->student($scope, '01');
        $this->mark($student, $scope, $subject, 80);
        config(['result_engine.placement_enabled' => false]);

        $options = ['--exam'=>$scope['exam']->id, '--class'=>$scope['class']->id, '--session'=>$scope['session']->id,
            '--section'=>$scope['section']->id, '--engine'=>'centralized'];
        $this->artisan('placements:recalculate', $options + ['--dry-run'=>true])
            ->assertSuccessful()->expectsOutputToContain('no records were modified');
        $this->assertDatabaseCount('exam_placements', 0);
        $this->artisan('placements:recalculate', $options)->assertFailed()->expectsOutputToContain('PLACEMENT_ENGINE_DISABLED');
    }

    public function test_insert_failure_rolls_back_original_scope_and_marks_remain_unchanged(): void
    {
        $scope = $this->scope('grading');
        $subject = $this->subject('Main', 'Main', 100);
        $student = $this->student($scope, '01');
        $mark = $this->mark($student, $scope, $subject, 80);
        $existing = $this->placement($student, $scope, 9);
        $before = $mark->only(['subjectMarks','objectMarks','practicalMarks','totalMarks','laterGrade','gradePoint']);
        $service = new class(
            app(\App\Services\ResultCalculation\ResultCalculationBatchBuilder::class),
            app(RankingMethodResolver::class),
        ) extends CentralizedPlacementRecalculator {
            protected function insertPlacementRows(array $rows): void
            {
                throw new \RuntimeException('Injected insert failure.');
            }
        };

        try {
            $service->recalculate(...$this->args($scope));
            $this->fail('Injected insert failure should abort.');
        } catch (PlacementRecalculationException $exception) {
            $this->assertContains('RECALCULATION_EXCEPTION', collect($exception->report['blockingErrors'])->pluck('code')->all());
        }

        $this->assertDatabaseHas('exam_placements', ['id'=>$existing->id, 'position'=>9]);
        $this->assertEquals($before, $mark->fresh()->only(array_keys($before)));
        $this->assertSame(0, ResultPublish::count());
    }

    private function service(): CentralizedPlacementRecalculator { return app(CentralizedPlacementRecalculator::class); }

    private function args(array $scope, bool $dryRun = false, bool $force = false): array
    {
        return [$scope['exam']->id, $scope['class']->id, $scope['session']->id, $scope['section']->id, null, $dryRun, $force, 'test'];
    }

    private function scope(string $method): array
    {
        ServerConfig::create(['ranking_method' => $method]);
        $session = new sessionManage(); $session->session = '2026'; $session->save();
        $class = new classManage(); $class->className = 'Class 10'; $class->save();
        $section = new sectionManage(); $section->section = 'A'; $section->save();
        $exam = new Exam(); $exam->examName = 'Annual'; $exam->passingSystem = 2; $exam->save();
        return compact('session', 'class', 'section', 'exam');
    }

    private function student(array $scope, string $roll, ?int $fourth = null): newAdmission
    {
        return newAdmission::create(['stdId'=>(string)random_int(100000,9999999),'fullName'=>'Student','sessName'=>$scope['session']->id,
            'className'=>$scope['class']->id,'sectionName'=>$scope['section']->id,'rollNumber'=>$roll,'fourthSubjectId'=>$fourth]);
    }

    private function subject(string $name, string $type, float $cq): Subject
    {
        return Subject::create(['subjectName'=>$name,'alias'=>strtolower($name),'subjectType'=>$type,'assign_class'=>'0','CQ'=>$cq,'MCQ'=>0,'Practical'=>0]);
    }

    private function mark(newAdmission $student, array $scope, Subject $subject, float $cq): Marksheet
    {
        return Marksheet::create(['studentId'=>$student->id,'classId'=>$scope['class']->id,'sessionId'=>$scope['session']->id,
            'groupId'=>$scope['section']->id,'examId'=>$scope['exam']->id,'subjectId'=>$subject->id,'subjectMarks'=>$cq,
            'totalMarks'=>$cq,'gradePoint'=>0,'laterGrade'=>'Stored']);
    }

    private function placement(newAdmission $student, array $scope, int $position): Placement
    {
        return Placement::create(['studentId'=>$student->id,'classId'=>$scope['class']->id,'sessionId'=>$scope['session']->id,
            'groupId'=>$scope['section']->id,'examId'=>$scope['exam']->id,'subjectsCount'=>1,'totalGradePoints'=>5,
            'gpa'=>5,'totalMarks'=>80,'position'=>$position,'status'=>'Pass']);
    }
}
