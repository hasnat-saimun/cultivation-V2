<?php

namespace Tests\Feature;

use App\Models\GradeList;
use App\Models\Marksheet;
use App\Models\MarksScopeState;
use App\Models\ResultPublish;
use App\Models\Subject;
use App\Services\ResultCalculation\BoardResultCalculator;
use App\Services\ResultCalculation\BulkTranscriptResultBuilder;
use App\Services\ResultCalculation\ResultCalculationBatchBuilder;
use App\Services\ResultCalculation\ResultCalculationInputBuilder;
use App\Services\ResultCalculation\TabulationResultPresenter;
use App\Services\ResultCalculation\TranscriptResultPresenter;
use App\Services\ResultMarksConfirmationService;
use App\Services\ResultMarksDraftService;
use App\Services\ResultPublishService;
use App\Services\ResultUnpublishService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesResultLifecycleScope;
use Tests\TestCase;

class PairedLifecycleMatrixTest extends TestCase
{
    use RefreshDatabase, CreatesResultLifecycleScope;

    public function test_all_paired_scenarios_preserve_surface_parity_through_full_lifecycle_matrix(): void
    {
        foreach ($this->scenarioMatrix() as $scenario) {
            [$data, $actor, $scope, $scenario] = $this->buildScenarioFixture($scenario);
            $student = $data['students']->first();

            $marksBefore = $this->marksSnapshot($scope, $student->id);
            $pre = $this->surfaceSnapshot($data, $scenario);

            $this->assertSame($scenario['expected']['cq'], $pre['single']['cq'], $scenario['id'].' pre cq mismatch');
            $this->assertSame($scenario['expected']['mcq'], $pre['single']['mcq'], $scenario['id'].' pre mcq mismatch');
            $this->assertSame($scenario['expected']['practical'], $pre['single']['practical'], $scenario['id'].' pre practical mismatch');
            $this->assertSame($scenario['expectedStatus'], $pre['single']['status'], $scenario['id'].' pre status mismatch');
            $this->assertSame($scenario['expectedClassification'], $pre['single']['classification'], $scenario['id'].' pre classification mismatch');

            $this->assertSurfaceParity($pre, $scenario['id'].' pre');
            $this->assertNotSame('Absent', $pre['single']['status'], $scenario['id'].' pre must not become Absent');

            $publishOne = app(ResultPublishService::class)->publish(
                $scope + $this->publishOptions($scenario),
                $actor,
            );

            $this->assertFalse($publishOne['idempotent']);
            $this->assertSame('published', $publishOne['publications'][0]['status']);
            $this->assertSame(1, $publishOne['publications'][0]['revision']);

            $afterPublish = $this->surfaceSnapshot($data, $scenario);
            $this->assertSurfaceParity($afterPublish, $scenario['id'].' published');
            $this->assertEquivalentStage($pre, $afterPublish, $scenario['id'].' pre->published');
            $this->assertTotalsConsistent($afterPublish['single']);
            $this->assertTotalsConsistent($afterPublish['bulk']);
            $this->assertTotalsConsistent($afterPublish['tabulation']);

            $publishRepeat = app(ResultPublishService::class)->publish(
                $scope + $this->publishOptions($scenario) + ['publication_revision' => 1],
                $actor,
            );

            $this->assertTrue($publishRepeat['idempotent']);
            $this->assertSame(1, $publishRepeat['publications'][0]['revision']);

            $afterRepublish = $this->surfaceSnapshot($data, $scenario);
            $this->assertEquivalentStage($afterPublish, $afterRepublish, $scenario['id'].' published->republish-idempotent');

            $unpublish = app(ResultUnpublishService::class)->unpublish(
                $scope + ['publication_revision' => 1, 'reason' => 'Lifecycle matrix validation'],
                $actor,
            );

            $this->assertFalse($unpublish['idempotent']);
            $this->assertSame('unpublished', $unpublish['publications'][0]['status']);
            $this->assertSame(2, $unpublish['publications'][0]['revision']);

            $afterUnpublish = $this->surfaceSnapshot($data, $scenario);
            $this->assertEquivalentStage($pre, $afterUnpublish, $scenario['id'].' pre->unpublished');

            $publishTwo = app(ResultPublishService::class)->publish(
                $scope + $this->publishOptions($scenario) + ['publication_revision' => 2],
                $actor,
            );

            $this->assertFalse($publishTwo['idempotent']);
            $this->assertSame('published', $publishTwo['publications'][0]['status']);
            $this->assertSame(3, $publishTwo['publications'][0]['revision']);

            $afterPublishTwo = $this->surfaceSnapshot($data, $scenario);
            $this->assertEquivalentStage($pre, $afterPublishTwo, $scenario['id'].' pre->second-publish');

            $marksAfter = $this->marksSnapshot($scope, $student->id);
            $this->assertSame($marksBefore, $marksAfter, $scenario['id'].' marks rows changed across lifecycle');

            $publication = ResultPublish::query()
                ->where('sessionId', (string) $scope['sessionId'])
                ->where('classId', (string) $scope['classId'])
                ->where('groupId', (string) $scope['groupId'])
                ->where('examId', (string) $scope['examId'])
                ->firstOrFail();

            $this->assertSame('published', $publication->status, $scenario['id'].' final publication status');
            $this->assertSame(3, (int) $publication->revision, $scenario['id'].' final publication revision');
        }
    }

    public function test_archive_view_prefers_canonical_components_and_matches_live_row_for_all_scenarios(): void
    {
        foreach ($this->scenarioMatrix() as $scenario) {
            [$data, , , $scenario] = $this->buildScenarioFixture($scenario);
            $live = $this->surfaceSnapshot($data, $scenario)['single'];

            $subjectRow = [
                'name' => 'Archive '.$scenario['id'],
                'type' => 'Main',
                'paired' => true,
                'paper1' => ['cq' => 1, 'mcq' => 2, 'practical' => 3],
                'paper2' => ['cq' => 4, 'mcq' => 5, 'practical' => 6],
                'cq' => $live['cq'],
                'mcq' => $live['mcq'],
                'practical' => $live['practical'],
                'total' => $live['total'],
                'grade' => $live['grade'],
                'gradePoint' => $live['point'],
            ];

            $html = $this->renderArchive([$subjectRow], $live['total'], $live['gpa'], $live['status']);
            $cells = $this->firstMainRowCells($html);

            $this->assertSame((string) $live['cq'], $cells['theory'], $scenario['id'].' archive cq mismatch');
            $this->assertSame((string) $live['mcq'], $cells['mcq'], $scenario['id'].' archive mcq mismatch');
            $this->assertSame((string) $live['practical'], $cells['practical'], $scenario['id'].' archive practical mismatch');
            $this->assertSame((string) $live['total'], $cells['total'], $scenario['id'].' archive total mismatch');
            $this->assertSame((string) $live['grade'], $cells['grade'], $scenario['id'].' archive grade mismatch');
            $this->assertSame((string) $live['point'], $cells['point'], $scenario['id'].' archive point mismatch');
        }
    }

    public function test_archive_view_legacy_fallback_recomputes_when_canonical_components_are_missing(): void
    {
        $subjectRow = [
            'name' => 'Legacy Paired',
            'type' => 'Main',
            'paired' => true,
            'paper1' => ['cq' => 10, 'mcq' => 7, 'practical' => null],
            'paper2' => ['cq' => 9, 'mcq' => null, 'practical' => null],
            'total' => 26,
            'grade' => 'B',
            'gradePoint' => '3.00',
        ];

        $html = $this->renderArchive([$subjectRow], 26, '3.00', 'Pass');
        $cells = $this->firstMainRowCells($html);

        $this->assertSame('(10 + 9) = 19', $cells['theory']);
        $this->assertSame('7', $cells['mcq']);
        $this->assertSame('-', $cells['practical']);
        $this->assertSame('26', $cells['total']);
    }

    private function scenarioMatrix(): array
    {
        return [
            [
                'id' => 's1',
                'pair' => 'bangla',
                'expected' => ['cq' => '(21 + 19) = 40', 'mcq' => '-', 'practical' => '-'],
                'expectedStatus' => 'Pass',
                'expectedClassification' => 'Complete',
                'confirm_anyway' => false,
                'paper1' => ['cqFull' => 50, 'mcqFull' => 0, 'prFull' => 0, 'cq' => 21, 'mcq' => null, 'pr' => null, 'blankOverride' => false],
                'paper2' => ['cqFull' => 50, 'mcqFull' => 0, 'prFull' => 0, 'cq' => 19, 'mcq' => null, 'pr' => null, 'blankOverride' => false],
            ],
            [
                'id' => 's2',
                'pair' => 'english',
                'expected' => ['cq' => '-', 'mcq' => '(18 + 17) = 35', 'practical' => '-'],
                'expectedStatus' => 'Pass',
                'expectedClassification' => 'Complete',
                'confirm_anyway' => false,
                'paper1' => ['cqFull' => 0, 'mcqFull' => 25, 'prFull' => 0, 'cq' => null, 'mcq' => 18, 'pr' => null, 'blankOverride' => false],
                'paper2' => ['cqFull' => 0, 'mcqFull' => 25, 'prFull' => 0, 'cq' => null, 'mcq' => 17, 'pr' => null, 'blankOverride' => false],
            ],
            [
                'id' => 's3',
                'pair' => 'english',
                'expected' => ['cq' => '-', 'mcq' => '16', 'practical' => '-'],
                'expectedStatus' => 'Incomplete',
                'expectedClassification' => 'Incomplete',
                'confirm_anyway' => true,
                'paper1' => ['cqFull' => 0, 'mcqFull' => 25, 'prFull' => 0, 'cq' => null, 'mcq' => 16, 'pr' => null, 'blankOverride' => false],
                'paper2' => ['cqFull' => 0, 'mcqFull' => 25, 'prFull' => 0, 'cq' => null, 'mcq' => null, 'pr' => null, 'blankOverride' => false, 'confirm' => false],
            ],
            [
                'id' => 's4',
                'pair' => 'english',
                'expected' => ['cq' => '-', 'mcq' => '14', 'practical' => '-'],
                'expectedStatus' => 'Incomplete',
                'expectedClassification' => 'Incomplete',
                'confirm_anyway' => true,
                'paper1' => ['cqFull' => 0, 'mcqFull' => 25, 'prFull' => 0, 'cq' => null, 'mcq' => null, 'pr' => null, 'blankOverride' => false, 'confirm' => false],
                'paper2' => ['cqFull' => 0, 'mcqFull' => 25, 'prFull' => 0, 'cq' => null, 'mcq' => 14, 'pr' => null, 'blankOverride' => false],
            ],
            [
                'id' => 's5',
                'pair' => 'english',
                'expected' => ['cq' => '-', 'mcq' => '-', 'practical' => '-'],
                'expectedStatus' => 'Incomplete',
                'expectedClassification' => 'Absent',
                'confirm_anyway' => true,
                'paper1' => ['cqFull' => 0, 'mcqFull' => 25, 'prFull' => 0, 'cq' => null, 'mcq' => null, 'pr' => null, 'blankOverride' => false, 'confirm' => false],
                'paper2' => ['cqFull' => 0, 'mcqFull' => 25, 'prFull' => 0, 'cq' => null, 'mcq' => null, 'pr' => null, 'blankOverride' => false, 'confirm' => false],
            ],
            [
                'id' => 's6',
                'pair' => 'english',
                'expected' => ['cq' => '-', 'mcq' => '0', 'practical' => '-'],
                'expectedStatus' => 'Incomplete',
                'expectedClassification' => 'Incomplete',
                'confirm_anyway' => true,
                'paper1' => ['cqFull' => 0, 'mcqFull' => 25, 'prFull' => 0, 'cq' => null, 'mcq' => 0, 'pr' => null, 'blankOverride' => false],
                'paper2' => ['cqFull' => 0, 'mcqFull' => 25, 'prFull' => 0, 'cq' => null, 'mcq' => null, 'pr' => null, 'blankOverride' => false, 'confirm' => false],
            ],
            [
                'id' => 's7',
                'pair' => 'english',
                'expected' => ['cq' => '-', 'mcq' => '(0 + 0) = 0', 'practical' => '-'],
                'expectedStatus' => 'Fail',
                'expectedClassification' => 'Complete',
                'confirm_anyway' => false,
                'paper1' => ['cqFull' => 0, 'mcqFull' => 25, 'prFull' => 0, 'cq' => null, 'mcq' => null, 'pr' => null, 'blankOverride' => true],
                'paper2' => ['cqFull' => 0, 'mcqFull' => 25, 'prFull' => 0, 'cq' => null, 'mcq' => null, 'pr' => null, 'blankOverride' => true],
            ],
            [
                'id' => 's8',
                'pair' => 'bangla',
                'expected' => ['cq' => '-', 'mcq' => '-', 'practical' => '-'],
                'expectedStatus' => 'Incomplete',
                'expectedClassification' => 'Absent',
                'confirm_anyway' => true,
                'paper1' => ['cqFull' => 0, 'mcqFull' => 0, 'prFull' => 0, 'cq' => null, 'mcq' => null, 'pr' => null, 'blankOverride' => false, 'confirm' => false],
                'paper2' => ['cqFull' => 0, 'mcqFull' => 0, 'prFull' => 0, 'cq' => null, 'mcq' => null, 'pr' => null, 'blankOverride' => false, 'confirm' => false],
            ],
            [
                'id' => 's9',
                'pair' => 'bangla',
                'expected' => ['cq' => '-', 'mcq' => '-', 'practical' => '11'],
                'expectedStatus' => 'Incomplete',
                'expectedClassification' => 'Incomplete',
                'confirm_anyway' => true,
                'paper1' => ['cqFull' => 0, 'mcqFull' => 0, 'prFull' => 15, 'cq' => null, 'mcq' => null, 'pr' => 11, 'blankOverride' => false],
                'paper2' => ['cqFull' => 0, 'mcqFull' => 0, 'prFull' => 15, 'cq' => null, 'mcq' => null, 'pr' => null, 'blankOverride' => false, 'confirm' => false],
            ],
            [
                'id' => 's10',
                'pair' => 'bangla',
                'expected' => ['cq' => '-', 'mcq' => '-', 'practical' => '(11 + 9) = 20'],
                'expectedStatus' => 'Pass',
                'expectedClassification' => 'Complete',
                'confirm_anyway' => false,
                'paper1' => ['cqFull' => 0, 'mcqFull' => 0, 'prFull' => 15, 'cq' => null, 'mcq' => null, 'pr' => 11, 'blankOverride' => false],
                'paper2' => ['cqFull' => 0, 'mcqFull' => 0, 'prFull' => 15, 'cq' => null, 'mcq' => null, 'pr' => 9, 'blankOverride' => false],
            ],
        ];
    }

    private function buildScenarioFixture(array $scenario): array
    {
        $data = $this->lifecycleScope();
        $actor = $this->lifecycleActor();

        [$paperOne, $paperTwo, $cellKey] = $this->pairedSubjects($data, $scenario);

        $this->saveAndConfirmPaper($data, $actor, $paperOne, $scenario['paper1']);
        $this->saveAndConfirmPaper($data, $actor, $paperTwo, $scenario['paper2']);

        $scope = [
            'sessionId' => $data['session']->id,
            'classId' => $data['class']->id,
            'groupId' => $data['section']->id,
            'examId' => $data['exam']->id,
        ];

        $scenario['cellKey'] = $cellKey;
        return [$data, $actor, $scope, $scenario];
    }

    private function pairedSubjects(array $data, array &$scenario): array
    {
        if ($scenario['pair'] === 'english') {
            $data['subject']->update([
                'subjectName' => 'English 1st Paper',
                'alias' => 'english_1st_paper',
                'subjectType' => 'Main',
                'CQ' => $scenario['paper1']['cqFull'],
                'MCQ' => $scenario['paper1']['mcqFull'],
                'Practical' => $scenario['paper1']['prFull'],
            ]);

            $paperTwo = Subject::create([
                'subjectName' => 'English 2nd Paper',
                'alias' => 'english_2nd_paper',
                'subjectType' => 'Main',
                'CQ' => $scenario['paper2']['cqFull'],
                'MCQ' => $scenario['paper2']['mcqFull'],
                'Practical' => $scenario['paper2']['prFull'],
            ]);

            $cellKey = 'pair:english';
        } else {
            $data['subject']->update([
                'subjectName' => 'Bangla 1st Paper',
                'alias' => 'bangla_1st_paper',
                'subjectType' => 'Main',
                'CQ' => $scenario['paper1']['cqFull'],
                'MCQ' => $scenario['paper1']['mcqFull'],
                'Practical' => $scenario['paper1']['prFull'],
            ]);

            $paperTwo = Subject::create([
                'subjectName' => 'Bangla 2nd Paper',
                'alias' => 'bangla_2nd_paper',
                'subjectType' => 'Main',
                'CQ' => $scenario['paper2']['cqFull'],
                'MCQ' => $scenario['paper2']['mcqFull'],
                'Practical' => $scenario['paper2']['prFull'],
            ]);

            $cellKey = 'pair:bangla';
        }

        DB::table('curriculum_subject_mappings')->insert([
            'session_id' => (string) $data['session']->id,
            'class_id' => (string) $data['class']->id,
            'section_id' => (string) $data['section']->id,
            'department_id' => null,
            'subject_id' => (int) $paperTwo->id,
            'mapping_type' => 'main',
            'sort_order' => 2,
            'is_active' => 1,
            'source' => 'test-fixture',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$data['subject'], $paperTwo, $cellKey];
    }

    private function saveAndConfirmPaper(array $data, $actor, Subject $paper, array $values): void
    {
        $student = $data['students']->first();
        $scopeInput = [
            'sessionId' => $data['session']->id,
            'classId' => $data['class']->id,
            'groupId' => $data['section']->id,
            'examId' => $data['exam']->id,
            'subjectId' => $paper->id,
        ];

        app(ResultMarksDraftService::class)->save($scopeInput + [
            'studentId' => [$student->id],
            'cqMarks' => [$values['cq']],
            'mcqMarks' => [$values['mcq']],
            'practical' => [$values['pr']],
            'gender' => 'all',
            'scope_revision' => 1,
        ], $actor, null, true);

        if (!($values['confirm'] ?? true)) {
            return;
        }

        app(ResultMarksConfirmationService::class)->confirm($scopeInput + [
            'scope_revision' => 2,
            'confirm_blank_marks' => $values['blankOverride'] ? 1 : 0,
        ], $actor);
    }

    private function publishOptions(array $scenario): array
    {
        return $scenario['confirm_anyway'] ? ['confirm_anyway' => true] : [];
    }

    private function surfaceSnapshot(array $data, array $scenario): array
    {
        $student = $data['students']->first()->fresh();
        $student->load(['marksheet' => fn ($query) => $query
            ->where('examId', $data['exam']->id)
            ->orderBy('subjectId')]);

        $subjects = app(ResultCalculationInputBuilder::class)->subjectsForStudent($student);
        $result = app(BoardResultCalculator::class)->calculate($student, $data['exam'], $student->marksheet, $subjects);

        $batchForSingle = app(ResultCalculationBatchBuilder::class)->build(
            $data['exam']->id,
            $data['class']->id,
            $data['session']->id,
            $data['section']->id,
            null,
        );
        $singleEntry = $batchForSingle['entries'][$student->id] ?? null;
        if (is_array($singleEntry)) {
            $subjects = $singleEntry['subjects'];
            $result = $singleEntry['result'];
        }

        $single = app(TranscriptResultPresenter::class)->presentWithGradeRows(
            $result,
            $subjects,
            $student->marksheet,
            GradeList::all(),
        );
        $singleRow = $this->resultRowByCellKey($single, $scenario['cellKey']);

        $bulkResult = app(BulkTranscriptResultBuilder::class)->build([$student], $data['exam'])[0]['result'];
        $bulkRow = $this->resultRowByCellKey($bulkResult, $scenario['cellKey']);

        $batch = app(ResultCalculationBatchBuilder::class)->buildPublicationScope(
            $data['exam']->id,
            $data['class']->id,
            $data['session']->id,
            $data['section']->id,
        );
        $tabulation = app(TabulationResultPresenter::class)->present(array_values($batch['entries']));
        $tabRow = collect($tabulation['rows'])->first(fn ($row) => (int) ($row['student']->id ?? 0) === (int) $student->id);
        $tabCell = collect($tabRow['subjects'])->firstWhere('cellKey', $scenario['cellKey']);

        return [
            'single' => $this->normalizeSurface([
                'cq' => $singleRow['cq'],
                'mcq' => $singleRow['mcq'],
                'practical' => $singleRow['practical'],
                'total' => $singleRow['total'],
                'gpa' => $single['gpa'],
                'grade' => $singleRow['grade'],
                'point' => $singleRow['gradePoint'],
                'status' => $single['status'],
                'classification' => $single['classification'],
                'subjectStatus' => $singleRow['status'],
            ]),
            'bulk' => $this->normalizeSurface([
                'cq' => $bulkRow['cq'],
                'mcq' => $bulkRow['mcq'],
                'practical' => $bulkRow['practical'],
                'total' => $bulkRow['total'],
                'gpa' => $bulkResult['gpa'],
                'grade' => $bulkRow['grade'],
                'point' => $bulkRow['gradePoint'],
                'status' => $bulkResult['status'],
                'classification' => $bulkResult['classification'],
                'subjectStatus' => $bulkRow['status'],
            ]),
            'tabulation' => $this->normalizeSurface([
                'cq' => $tabCell['cq'],
                'mcq' => $tabCell['mcq'],
                'practical' => $tabCell['practical'],
                'total' => $tabCell['total'],
                'gpa' => $tabRow['finalGpa'],
                'grade' => $tabCell['grade'],
                'point' => $tabCell['gradePoint'],
                'status' => $tabRow['status'],
                'classification' => $tabRow['classification'],
                'subjectStatus' => $tabCell['status'],
            ]),
        ];
    }

    private function resultRowByCellKey(array $presented, string $cellKey): array
    {
        $rows = collect(array_merge($presented['mainRows'], $presented['optionalRows']));
        $row = $rows->firstWhere('cellKey', $cellKey);
        if (is_array($row)) {
            return $row;
        }

        if (!empty($presented['mainRows']) && count($presented['mainRows']) === 1) {
            return $presented['mainRows'][0];
        }

        if (!empty($presented['optionalRows']) && count($presented['optionalRows']) === 1) {
            return $presented['optionalRows'][0];
        }

        return [];
    }

    private function normalizeSurface(array $surface): array
    {
        foreach (['cq', 'mcq', 'practical', 'total', 'grade', 'status', 'classification', 'subjectStatus'] as $field) {
            $surface[$field] = $this->normalizeValue($surface[$field] ?? '-');
        }

        $surface['gpa'] = $this->normalizeDecimal($surface['gpa'] ?? '-');
        $surface['point'] = $this->normalizeDecimal($surface['point'] ?? '-');

        return $surface;
    }

    private function normalizeDecimal(mixed $value): string
    {
        if ($value === null) {
            return '-';
        }

        if (is_string($value) && trim($value) === '-') {
            return '-';
        }

        if (!is_numeric($value)) {
            return $this->normalizeValue($value);
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function normalizeValue(mixed $value): string
    {
        if ($value === null) {
            return '-';
        }

        if (is_string($value)) {
            return trim($value) === '' ? '-' : trim($value);
        }

        if (is_numeric($value)) {
            $float = (float) $value;
            if (abs($float - round($float)) < 0.00001) {
                return (string) (int) round($float);
            }

            return rtrim(rtrim(number_format($float, 2, '.', ''), '0'), '.');
        }

        return (string) $value;
    }

    private function assertSurfaceParity(array $snapshot, string $label): void
    {
        $fields = ['cq', 'mcq', 'practical', 'total', 'gpa', 'grade', 'point', 'status', 'classification', 'subjectStatus'];
        foreach ($fields as $field) {
            $this->assertSame($snapshot['single'][$field], $snapshot['bulk'][$field], $label.' single/bulk mismatch on '.$field);
            $this->assertSame($snapshot['single'][$field], $snapshot['tabulation'][$field], $label.' single/tab mismatch on '.$field);
        }
    }

    private function assertEquivalentStage(array $expected, array $actual, string $label): void
    {
        foreach (['single', 'bulk', 'tabulation'] as $surface) {
            $this->assertSame($expected[$surface], $actual[$surface], $label.' mismatch on '.$surface);
        }
    }

    private function assertTotalsConsistent(array $surface): void
    {
        $componentValues = [
            $this->componentValue($surface['cq']),
            $this->componentValue($surface['mcq']),
            $this->componentValue($surface['practical']),
        ];

        if ($surface['total'] === '-') {
            $this->assertContains($surface['status'], ['Incomplete', 'Absent']);
            return;
        }

        $this->assertSame(
            round((float) $surface['total'], 2),
            round(array_sum($componentValues), 2),
            'Total must equal CQ + MCQ + Practical'
        );
    }

    private function componentValue(string $display): float
    {
        if ($display === '-') {
            return 0.0;
        }

        if (preg_match('/=\s*(-?\d+(?:\.\d+)?)$/', $display, $matches)) {
            return (float) $matches[1];
        }

        return (float) $display;
    }

    private function marksSnapshot(array $scope, int $studentId): array
    {
        $rows = Marksheet::query()
            ->where('sessionId', $scope['sessionId'])
            ->where('classId', $scope['classId'])
            ->where('groupId', $scope['groupId'])
            ->where('examId', $scope['examId'])
            ->where('studentId', $studentId)
            ->orderBy('subjectId')
            ->get();

        return [
            'count' => $rows->count(),
            'ids' => $rows->pluck('id')->values()->all(),
            'rows' => $rows->map(fn ($row) => [
                'subjectId' => (int) $row->subjectId,
                'cq' => $row->subjectMarks,
                'mcq' => $row->objectMarks,
                'practical' => $row->practicalMarks,
                'total' => $row->totalMarks,
            ])->values()->all(),
            'states' => MarksScopeState::query()
                ->where('sessionId', (string) $scope['sessionId'])
                ->where('classId', (string) $scope['classId'])
                ->where('groupId', (string) $scope['groupId'])
                ->where('examId', (string) $scope['examId'])
                ->orderBy('subjectId')
                ->get(['subjectId', 'status', 'revision'])
                ->map(fn ($state) => [
                    'subjectId' => (int) $state->subjectId,
                    'status' => (string) $state->status,
                    'revision' => (int) $state->revision,
                ])->values()->all(),
        ];
    }

    private function renderArchive(array $subjects, mixed $total, mixed $gpa, string $result): string
    {
        return view('result.transcriptArchiveTable', [
            'transcriptData' => [
                'subjects' => $subjects,
                'total_marks' => $total,
                'gpa' => $gpa,
                'result' => $result,
            ],
        ])->render();
    }

    private function firstMainRowCells(string $html): array
    {
        $matched = preg_match(
            '/<h3[^>]*>Main Subject<\/h3>.*?<tbody>\s*<tr>\s*<td>(.*?)<\/td>\s*<td>(.*?)<\/td>\s*<td>(.*?)<\/td>\s*<td>(.*?)<\/td>\s*<td>(.*?)<\/td>\s*<td>(.*?)<\/td>\s*<td>(.*?)<\/td>/si',
            $html,
            $matches
        );

        $this->assertSame(1, $matched, 'Failed to parse archive main subject row.');

        return [
            'name' => trim(strip_tags($matches[1])),
            'theory' => trim(strip_tags($matches[2])),
            'mcq' => trim(strip_tags($matches[3])),
            'practical' => trim(strip_tags($matches[4])),
            'total' => trim(strip_tags($matches[5])),
            'grade' => trim(strip_tags($matches[6])),
            'point' => trim(strip_tags($matches[7])),
        ];
    }
}
