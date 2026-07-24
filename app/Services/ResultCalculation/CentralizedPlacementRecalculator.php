<?php

namespace App\Services\ResultCalculation;

use App\Models\Marksheet;
use App\Models\Placement;
use App\Models\ResultPublish;
use App\Models\ServerConfig;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CentralizedPlacementRecalculator
{
    public function __construct(
        private ResultCalculationBatchBuilder $batchBuilder,
        private RankingMethodResolver $rankingMethodResolver,
    ) {}

    public function recalculate(
        int $examId,
        int $classId,
        int $sessionId,
        ?int $sectionId = null,
        ?int $departmentId = null,
        bool $dryRun = false,
        bool $force = false,
        string|int|null $actor = null,
    ): array {
        $scope = compact('examId', 'classId', 'sessionId', 'sectionId', 'departmentId');
        foreach (['examId', 'classId', 'sessionId'] as $required) {
            if ($scope[$required] <= 0) {
                throw new PlacementRecalculationException("Invalid required scope: {$required}.", $this->emptyReport($scope));
            }
        }

        $resolved = $this->rankingMethodResolver->resolve();
        $method = $resolved['method'];
        $published = $this->isPublished($examId, $classId, $sessionId, $sectionId);
        $report = array_merge($this->emptyReport($scope), [
            'rankingMethod' => $method,
            'warnings' => $resolved['warnings'],
            'publicationLocked' => $published,
            'forceRequired' => $published && !$force,
            'dryRun' => $dryRun,
        ]);

        if (!$dryRun && !config('result_engine.placement_enabled', false)) {
            $report['blockingErrors'][] = $this->issue('PLACEMENT_ENGINE_DISABLED', 'Centralized placement persistence is disabled.');
            throw new PlacementRecalculationException('Centralized placement persistence is disabled.', $report);
        }
        if ($published && !$force) {
            if ($dryRun) {
                $report['warnings'][] = $this->issue('PUBLISHED_SCOPE', 'The selected result scope is published; explicit force is required for a real write.');
            } else {
                $report['blockingErrors'][] = $this->issue('PUBLISHED_SCOPE', 'The selected result scope is published; explicit force is required.');
                throw new PlacementRecalculationException('Published placement scope is locked.', $report);
            }
        }

        try {
            $batch = $this->batchBuilder->build($examId, $classId, $sessionId, $sectionId, $departmentId);
            [$rows, $errors, $warnings] = $this->prepare($batch, $scope, $method);
            $report['warnings'] = array_merge($report['warnings'], $warnings);
            $report['blockingErrors'] = array_merge($report['blockingErrors'], $errors);
            $report['studentsChecked'] = count($rows);
            $report['passedRanked'] = count(array_filter($rows, fn ($row) => $row['status'] === 'Pass'));
            $report['failedRanked'] = count(array_filter($rows, fn ($row) => $row['status'] === 'Fail'));
            $report['incompleteUnranked'] = count(array_filter($rows, fn ($row) => $row['status'] === 'Incomplete'));
            $report['wouldWriteCount'] = count($rows);

            $existing = $this->placementScopeQuery($scope, collect($batch['students'])->pluck('id')->all())->orderBy('id')->get();
            $report['rowsToReplace'] = $existing->count();
            $duplicateExisting = $existing->groupBy(fn ($row) => (string) $row->studentId)->filter(fn ($items) => $items->count() > 1);
            if ($duplicateExisting->isNotEmpty()) {
                $report['warnings'][] = $this->issue(
                    'DUPLICATE_EXISTING_PLACEMENT_NORMALIZED',
                    'Duplicate existing placement rows are fully contained in the exact replacement scope and will be normalized.'
                );
            }
            $report = array_merge($report, $this->comparison($existing, $rows));

            if ($report['blockingErrors'] !== []) {
                throw new PlacementRecalculationException('Centralized placement preflight failed.', $report);
            }
            if ($dryRun) {
                $report['writePermitted'] = !$report['forceRequired'];
                $report['noRecordsModified'] = true;
                return $report;
            }

            $insertRows = array_map(function ($row) {
                unset($row['_roll'], $row['_academicTuple'], $row['_failedCount'], $row['_rankingTotal']);
                $row['created_at'] = now();
                $row['updated_at'] = now();
                return $row;
            }, $rows);

            DB::transaction(function () use ($scope, $batch, $insertRows, &$report) {
                $query = $this->placementScopeQuery($scope, collect($batch['students'])->pluck('id')->all());
                $locked = $query->lockForUpdate()->get();
                $report['rowsReplaced'] = $locked->count();
                $query->delete();
                if ($insertRows !== []) $this->insertPlacementRows($insertRows);
                $inserted = $this->placementScopeQuery($scope, collect($batch['students'])->pluck('id')->all())->count();
                if ($inserted !== count($insertRows)) {
                    throw new \RuntimeException("Expected ".count($insertRows)." placement rows, found {$inserted}.");
                }
                $report['rowsInserted'] = $inserted;
            });

            $report['writePermitted'] = true;
            $report['noRecordsModified'] = false;
            Log::info('Centralized placement recalculation completed.', $this->logContext($report, $actor, $force));
            return $report;
        } catch (PlacementRecalculationException $exception) {
            Log::warning('Centralized placement recalculation blocked.', $this->logContext($exception->report, $actor, $force) + ['stage' => 'preflight']);
            throw $exception;
        } catch (Throwable $exception) {
            $report['blockingErrors'][] = $this->issue('RECALCULATION_EXCEPTION', 'Placement recalculation failed safely.', null, null);
            Log::error('Centralized placement recalculation failed and was rolled back.', $this->logContext($report, $actor, $force) + [
                'stage' => 'calculation_or_transaction', 'exception' => get_class($exception), 'rolled_back' => true,
            ]);
            throw new PlacementRecalculationException('Centralized placement recalculation failed safely.', $report);
        }
    }

    private function prepare(array $batch, array $scope, string $method): array
    {
        $errors = []; $warnings = []; $rows = []; $keys = [];
        $studentIds = collect($batch['students'])->pluck('id')->map(fn ($id) => (string) $id);
        $marks = Marksheet::query()->where('examId', (string) $scope['examId'])
            ->where('classId', (string) $scope['classId'])->where('sessionId', (string) $scope['sessionId'])
            ->when($scope['sectionId'] !== null, fn ($q) => $q->where('groupId', (string) $scope['sectionId']))
            ->whereIn('studentId', $studentIds)->get();

        foreach ($marks->groupBy(fn ($mark) => $mark->studentId.'|'.$mark->subjectId) as $duplicateKey => $items) {
            if ($items->count() > 1) {
                [$studentId, $subjectId] = explode('|', $duplicateKey, 2);
                $errors[] = $this->issue('DUPLICATE_MARKS', 'Duplicate logical marksheet rows found.', $studentId, $subjectId);
            }
        }

        $allSubjects = Subject::query()->get();
        $subjectsById = $allSubjects->keyBy(fn ($subject) => (string) $subject->id);
        $optionalIds = $allSubjects->filter(fn ($subject) => strcasecmp(trim((string) $subject->subjectType), 'Optional') === 0)
            ->pluck('id')->map(fn ($id) => (string) $id);
        foreach ($marks as $mark) {
            if (!$subjectsById->has((string) $mark->subjectId)) {
                $errors[] = $this->issue('INVALID_SUBJECT_ROW', 'Marksheet row references an unknown subject.', $mark->studentId, $mark->subjectId);
            }
        }
        $applicableSubjects = collect($batch['entries'])->flatMap(fn ($entry) => $entry['subjects'])->unique('id');
        foreach ($applicableSubjects as $subject) {
            $components = [$subject->CQ, $subject->MCQ, $subject->Practical];
            if (collect($components)->contains(fn ($value) => $value !== null && $value !== '' && (!is_numeric($value) || (float) $value < 0))) {
                $errors[] = $this->issue('INVALID_COMPONENT_CONFIGURATION', 'Subject has an invalid required component configuration.', null, $subject->id);
            }
            if (array_sum(array_map(fn ($value) => is_numeric($value) ? (float) $value : 0.0, $components)) <= 0) {
                $errors[] = $this->issue('INVALID_FULL_MARKS', 'Subject has no positive configured full marks.', null, $subject->id);
            }
        }
        foreach ($batch['students'] as $student) {
            $enteredOptional = $marks->where('studentId', (string) $student->id)->whereIn('subjectId', $optionalIds)->pluck('subjectId')->unique();
            if ($enteredOptional->count() > 1) {
                $errors[] = $this->issue('MULTIPLE_OPTIONAL_ROWS', 'Multiple optional/fourth-subject marks are present.', $student->id);
            }
            if ((int) ($student->fourthSubjectId ?? 0) > 0) {
                $fourth = $subjectsById->get((string) (int) $student->fourthSubjectId);
                if (!$fourth || strcasecmp(trim((string) $fourth->subjectType), 'Optional') !== 0
                    || !in_array((string) ($fourth->assign_class ?? '0'), ['0', (string) $scope['classId']], true)) {
                    $errors[] = $this->issue('INVALID_FOURTH_SUBJECT', 'Assigned fourth subject is missing, not optional, or outside the class curriculum.', $student->id, $student->fourthSubjectId);
                }
            }
        }

        foreach ($batch['entries'] as $studentId => $entry) {
            $student = $entry['student']; $result = $entry['result'];
            foreach ($result->warnings as $warning) {
                if ($this->isMissingMarkFullMarksWarning($warning, $result, $entry['subjects'])) continue;
                $errors[] = $this->issue($this->warningCode($warning), $warning, $studentId);
            }
            if ($result->compulsorySubjectCount === 0) {
                $errors[] = $this->issue('MISSING_COMPULSORY_CONFIGURATION', 'No compulsory subject configuration is available.', $studentId);
            }
            $total = round((float) collect($result->subjectResults)
                ->filter(fn (SubjectResult $subject) => $subject->isCompulsory)
                ->sum(fn (SubjectResult $subject) => (float) ($subject->obtainedMarks ?? 0)), 2);
            $key = implode('|', [(string) $studentId, $scope['sessionId'], $scope['classId'], $scope['sectionId'] ?? 'NULL', $scope['examId'], $scope['departmentId'] ?? 'NULL']);
            if (isset($keys[$key])) $errors[] = $this->issue('DUPLICATE_PLACEMENT_PAYLOAD', 'Duplicate logical placement payload key.', $studentId);
            $keys[$key] = true;
            $rows[] = [
                'studentId' => (string) $studentId, 'sessionId' => (string) $scope['sessionId'],
                'classId' => (string) $scope['classId'], 'groupId' => $scope['sectionId'] === null ? null : (string) $scope['sectionId'],
                'examId' => (string) $scope['examId'], 'subjectsCount' => $result->compulsorySubjectCount,
                'totalGradePoints' => round($result->compulsoryGpSum + $result->optionalBonus, 2),
                'gpa' => $result->gpa ?? 0.0, 'totalMarks' => (int) round($total),
                'position' => null, 'status' => $result->status, '_roll' => $this->rollValue($student->rollNumber),
                '_academicTuple' => [], '_failedCount' => count($result->failedCompulsorySubjects), '_rankingTotal' => $total,
            ];
        }

        $this->rankSeries($rows, 'Pass', $method);
        $this->rankSeries($rows, 'Fail', $method);
        return [$rows, $this->uniqueIssues($errors), $warnings];
    }

    private function rankSeries(array &$rows, string $status, string $method): void
    {
        $indexes = array_keys(array_filter($rows, fn ($row) => $row['status'] === $status));
        foreach ($indexes as $index) {
            $rows[$index]['_academicTuple'] = $status === 'Pass'
                ? ($method === ServerConfig::RANKING_METHOD_TOTAL_MARKS
                    ? [$rows[$index]['_rankingTotal'], (float) $rows[$index]['gpa']]
                    : [(float) $rows[$index]['gpa'], $rows[$index]['_rankingTotal']])
                : [$rows[$index]['_rankingTotal'], -$rows[$index]['_failedCount']];
        }
        usort($indexes, function ($leftIndex, $rightIndex) use ($rows) {
            $left = $rows[$leftIndex]; $right = $rows[$rightIndex];
            foreach ([0, 1] as $key) {
                if ($left['_academicTuple'][$key] !== $right['_academicTuple'][$key]) return $right['_academicTuple'][$key] <=> $left['_academicTuple'][$key];
            }
            if ($left['_roll'] !== $right['_roll']) return $left['_roll'] <=> $right['_roll'];
            return (int) $left['studentId'] <=> (int) $right['studentId'];
        });
        $previous = null; $rank = 0;
        foreach ($indexes as $offset => $index) {
            $tuple = $rows[$index]['_academicTuple'];
            if ($previous === null || $tuple !== $previous) $rank = $offset + 1;
            $rows[$index]['position'] = $rank;
            $previous = $tuple;
        }
    }

    private function placementScopeQuery(array $scope, array $studentIds)
    {
        return Placement::query()->where('examId', (string) $scope['examId'])
            ->where('classId', (string) $scope['classId'])->where('sessionId', (string) $scope['sessionId'])
            ->when($scope['sectionId'] === null, fn ($q) => $q->whereNull('groupId'), fn ($q) => $q->where('groupId', (string) $scope['sectionId']))
            ->when($scope['departmentId'] !== null, fn ($q) => $q->whereIn('studentId', array_map('strval', $studentIds)));
    }

    private function isPublished(int $examId, int $classId, int $sessionId, ?int $sectionId): bool
    {
        return ResultPublish::query()->where('examId', (string) $examId)->where('classId', (string) $classId)
            ->where('sessionId', (string) $sessionId)
            ->where('status', ResultPublish::STATUS_PUBLISHED)
            ->where(function ($query) use ($sectionId) {
                if ($sectionId === null) {
                    $query->whereNull('groupId');
                    return;
                }
                $query->where('groupId', (string) $sectionId)
                    ->orWhere(fn ($legacy) => $legacy->whereNull('groupId')->where('legacyImported', true));
            })->exists();
    }

    private function comparison($existing, array $rows): array
    {
        $old = $existing->keyBy(fn ($row) => (string) $row->studentId);
        $gpa = $status = $rank = $new = 0;
        foreach ($rows as $row) {
            $stored = $old->get($row['studentId']);
            if (!$stored) { $new++; continue; }
            if ((float) $stored->gpa !== (float) $row['gpa']) $gpa++;
            if ((string) $stored->status !== $row['status']) $status++;
            if (($stored->position === null ? null : (int) $stored->position) !== $row['position']) $rank++;
        }
        return ['gpaChanges' => $gpa, 'statusChanges' => $status, 'rankChanges' => $rank, 'newRows' => $new];
    }

    protected function insertPlacementRows(array $rows): void
    {
        Placement::query()->insert($rows);
    }

    private function emptyReport(array $scope): array
    {
        return $scope + ['rankingMethod' => 'grading', 'studentsChecked' => 0, 'passedRanked' => 0, 'failedRanked' => 0,
            'incompleteUnranked' => 0, 'gpaChanges' => 0, 'statusChanges' => 0, 'rankChanges' => 0, 'newRows' => 0,
            'rowsToReplace' => 0, 'wouldWriteCount' => 0, 'rowsReplaced' => 0, 'rowsInserted' => 0,
            'blockingErrors' => [], 'warnings' => [], 'publicationLocked' => false, 'forceRequired' => false,
            'writePermitted' => false, 'noRecordsModified' => true];
    }

    private function issue(string $code, string $message, string|int|null $studentId = null, string|int|null $subjectId = null): array
    {
        return array_filter(compact('code', 'message', 'studentId', 'subjectId'), fn ($value) => $value !== null);
    }

    private function warningCode(string $warning): string
    {
        return match (true) {
            str_contains($warning, 'Duplicate marks') => 'DUPLICATE_MARKS',
            str_contains($warning, 'fourth subject') => 'INVALID_FOURTH_SUBJECT',
            str_contains($warning, 'Multiple optional') => 'MULTIPLE_OPTIONAL_ROWS',
            str_contains($warning, 'Configured pair') => 'MALFORMED_SUBJECT_PAIR',
            str_contains($warning, 'full marks') => 'INVALID_FULL_MARKS',
            str_contains($warning, 'outside') => 'INVALID_COMPONENT_MARKS',
            str_contains($warning, 'No compulsory') => 'MISSING_COMPULSORY_CONFIGURATION',
            default => 'CALCULATION_WARNING',
        };
    }

    private function isMissingMarkFullMarksWarning(string $warning, StudentResult $result, $subjects): bool
    {
        if (!preg_match('/^Subject (.+) has no positive configured full marks\.$/', $warning, $matches)) return false;
        $calculated = collect($result->subjectResults)->first(fn (SubjectResult $subject) => $subject->subjectId === $matches[1]);
        if (!$calculated?->missing) return false;
        $sourceIds = collect($calculated->sourceSubjectIds)->map(fn ($id) => (string) $id);
        $configuredFull = collect($subjects)->filter(fn ($subject) => $sourceIds->contains((string) $subject->id))
            ->sum(fn ($subject) => (float) ($subject->CQ ?? 0) + (float) ($subject->MCQ ?? 0) + (float) ($subject->Practical ?? 0));
        return $configuredFull > 0;
    }

    private function uniqueIssues(array $issues): array
    {
        return collect($issues)->unique(fn ($item) => implode('|', [$item['code'], $item['studentId'] ?? '', $item['subjectId'] ?? '', $item['message']]))->values()->all();
    }

    private function rollValue(mixed $roll): int { return is_numeric($roll) ? (int) $roll : PHP_INT_MAX; }

    private function logContext(array $report, string|int|null $actor, bool $force): array
    {
        return ['engine' => 'centralized', 'ranking_method' => $report['rankingMethod'] ?? null,
            'exam_id' => $report['examId'] ?? null, 'class_id' => $report['classId'] ?? null,
            'session_id' => $report['sessionId'] ?? null, 'section_id' => $report['sectionId'] ?? null,
            'department_id' => $report['departmentId'] ?? null, 'students' => $report['studentsChecked'] ?? 0,
            'pass_ranked' => $report['passedRanked'] ?? 0, 'fail_ranked' => $report['failedRanked'] ?? 0,
            'incomplete' => $report['incompleteUnranked'] ?? 0, 'rows_replaced' => $report['rowsReplaced'] ?? 0,
            'rows_inserted' => $report['rowsInserted'] ?? 0, 'force' => $force, 'actor' => $actor,
            'blocking_codes' => collect($report['blockingErrors'] ?? [])->pluck('code')->unique()->values()->all(),
            'blocking_errors' => $report['blockingErrors'] ?? []];
    }
}
