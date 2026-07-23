<?php

namespace App\Services\ResultCalculation;

use App\Models\classManage;
use App\Models\Department;
use App\Models\Exam;
use App\Models\newAdmission;
use App\Models\Placement;
use App\Models\PromotionAuditLog;
use App\Models\ResultArchive;
use App\Models\sectionManage;
use App\Models\sessionManage;
use Illuminate\Support\Facades\Log;
use Throwable;

class PromotionPreviewBuilder
{
    public function __construct(private ResultCalculationBatchBuilder $batchBuilder) {}

    public function build(
        int $examId,
        int $sourceClassId,
        int $sourceSessionId,
        int $destinationClassId,
        int $destinationSessionId,
        ?int $sourceSectionId = null,
        ?int $sourceDepartmentId = null,
        ?int $destinationSectionId = null,
        ?int $destinationDepartmentId = null,
        ?int $studentId = null,
        int $limit = 100,
    ): array {
        $scope = compact(
            'examId', 'sourceClassId', 'sourceSessionId', 'sourceSectionId', 'sourceDepartmentId',
            'destinationClassId', 'destinationSessionId', 'destinationSectionId', 'destinationDepartmentId'
        );
        $blockers = []; $warnings = [];
        $this->validateScope($scope, $blockers, $warnings);

        try {
            $batch = $this->batchBuilder->buildTolerant(
                $examId, $sourceClassId, $sourceSessionId, $sourceSectionId, $sourceDepartmentId
            );
        } catch (Throwable $exception) {
            Log::error('Centralized promotion preview calculation failed.', $scope + [
                'student_id' => $studentId, 'exception' => get_class($exception), 'stage' => 'centralized_batch',
            ]);
            return [
                'rows' => [],
                'summary' => $this->summary([], array_merge($blockers, [[
                    'code' => 'CALCULATION_ERROR',
                    'message' => 'Centralized calculation failed; Phase 9 write is blocked.',
                ]]), $warnings, 1),
                'scope' => $scope,
            ];
        }

        $entries = collect($batch['entries']);
        $calculationFailures = collect($batch['errors'] ?? []);
        if ($studentId !== null) $entries = $entries->only([$studentId]);
        if ($studentId !== null) $calculationFailures = $calculationFailures->only([$studentId]);
        $entries = $entries->take(max(1, min(10000, $limit)));
        $remaining = max(0, max(1, min(10000, $limit)) - $entries->count());
        $calculationFailures = $calculationFailures->take($remaining);
        foreach ($calculationFailures as $failedStudentId => $failure) {
            Log::error('Centralized promotion preview student calculation failed.', $scope + [
                'student_id' => $failedStudentId, 'exception' => $failure['exception'], 'stage' => 'student_calculation',
            ]);
        }
        $students = $entries->pluck('student');
        $students = $students->concat($calculationFailures->pluck('student'));
        $studentIds = $students->pluck('id')->map(fn ($id) => (string) $id)->values();
        $stdIds = $students->pluck('stdId')->filter()->map(fn ($id) => (string) $id)->values();

        $placements = Placement::query()
            ->where('examId', (string) $examId)->where('classId', (string) $sourceClassId)
            ->where('sessionId', (string) $sourceSessionId)
            ->when($sourceSectionId === null, fn ($query) => $query->whereNull('groupId'), fn ($query) => $query->where('groupId', (string) $sourceSectionId))
            ->whereIn('studentId', $studentIds)->orderBy('id')->get()->groupBy(fn ($row) => (string) $row->studentId);

        $archives = ResultArchive::query()->whereIn('student_id', $studentIds)->orderBy('id')->get()
            ->groupBy(fn ($row) => (string) $row->student_id);
        $auditLogs = PromotionAuditLog::query()->whereIn('student_id', $studentIds)->orderBy('id')->get()
            ->groupBy(fn ($row) => (string) $row->student_id);

        $destinationStudents = newAdmission::query()
            ->where('sessName', $destinationSessionId)->where('className', $destinationClassId)
            ->when($destinationSectionId !== null, fn ($query) => $query->where('sectionName', $destinationSectionId))
            ->when($destinationDepartmentId !== null, fn ($query) => $query->where('departmentName', $destinationDepartmentId))
            ->get();
        $destinationRolls = $destinationStudents->groupBy(fn ($student) => strtolower(trim((string) $student->getRawOriginal('rollNumber'))));
        $destinationStdIds = $destinationStudents->groupBy(fn ($student) => (string) $student->stdId);
        $proposedRolls = $students->groupBy(fn ($student) => strtolower(trim((string) $student->getRawOriginal('rollNumber'))));
        $duplicateStdIds = newAdmission::query()->whereIn('stdId', $stdIds)->get()
            ->groupBy(fn ($student) => (string) $student->stdId)->filter(fn ($items) => $items->count() > 1);

        $rows = [];
        foreach ($entries as $id => $entry) {
            $student = $entry['student']; $result = $entry['result']; $key = (string) $id;
            $studentPlacements = $placements->get($key, collect());
            $placement = $studentPlacements->first();
            $studentArchives = $archives->get($key, collect());
            $sameExamArchives = $studentArchives->filter(fn ($archive) =>
                (string) $archive->old_class === (string) $sourceClassId
                && (string) $archive->old_session === (string) $sourceSessionId
                && ($sourceSectionId === null || (string) $archive->old_section === (string) $sourceSectionId)
                && (string) $archive->exam_id === (string) $examId
            );
            $logs = $auditLogs->get($key, collect());
            $matchingPromotion = $logs->first(fn ($log) =>
                ($log->engine !== 'centralized' || $log->reverted_at === null)
                &&
                (string) $log->old_class === (string) $sourceClassId
                && (string) $log->old_session === (string) $sourceSessionId
                && (string) $log->new_class === (string) $destinationClassId
                && (string) $log->new_session === (string) $destinationSessionId
                && ($destinationSectionId === null || (string) $log->new_section === (string) $destinationSectionId)
            );

            $rawRoll = $student->getRawOriginal('rollNumber');
            $rollKey = strtolower(trim((string) $rawRoll));
            $rollConflict = $rollKey !== '' && $destinationRolls->get($rollKey, collect())
                ->contains(fn ($candidate) => (int) $candidate->id !== (int) $student->id);
            $batchRollConflict = $rollKey !== '' && $proposedRolls->get($rollKey, collect())->count() > 1;
            $duplicateSource = $student->stdId !== null && $duplicateStdIds->has((string) $student->stdId);
            $destinationRecordConflict = $student->stdId !== null && $student->stdId !== ''
                && $destinationStdIds->get((string) $student->stdId, collect())
                    ->contains(fn ($candidate) => (int) $candidate->id !== (int) $student->id);
            $alreadyDestination = (int) $student->sessName === $destinationSessionId
                && (int) $student->className === $destinationClassId
                && ($destinationSectionId === null || (int) $student->sectionName === $destinationSectionId);

            $legacyMarks = $student->marksheet;
            $legacyCount = $legacyMarks->count();
            $legacyGpa = $legacyCount > 0 ? round($legacyMarks->sum(fn ($mark) => (float) ($mark->gradePoint ?? 0)) / $legacyCount, 2) : null;
            $legacyStatus = $legacyCount === 0 ? 'NoMarks'
                : ($legacyMarks->contains(fn ($mark) => (float) ($mark->gradePoint ?? 0) <= 0) ? 'Fail' : 'Pass');
            $rowBlockers = []; $rowWarnings = []; $reasons = [];
            if ($result->status === 'Fail') $rowBlockers[] = 'CENTRALIZED_FAIL';
            if ($result->status === 'Incomplete') $rowBlockers[] = 'CENTRALIZED_INCOMPLETE';
            if ($alreadyDestination) $rowBlockers[] = 'ALREADY_IN_DESTINATION';
            if ($matchingPromotion) $rowBlockers[] = 'ALREADY_PROMOTED';
            if ($rollConflict || $batchRollConflict) $rowBlockers[] = 'DESTINATION_ROLL_CONFLICT';
            if ($destinationRecordConflict) $rowBlockers[] = 'DESTINATION_STUDENT_CONFLICT';
            if ($duplicateSource) $rowBlockers[] = 'DUPLICATE_SOURCE_STUDENT';
            if ($studentPlacements->count() > 1) $rowWarnings[] = 'DUPLICATE_PLACEMENT';
            if (!$placement) $rowWarnings[] = 'MISSING_PLACEMENT';
            if ($studentArchives->whereNull('exam_id')->isNotEmpty()) $rowWarnings[] = 'LEGACY_ARCHIVE_EXAM_UNKNOWN';
            if ($sameExamArchives->count() > 1) $rowWarnings[] = 'DUPLICATE_SCOPE_ARCHIVE';
            if ($logs->filter(fn ($log) => (string) $log->old_class === (string) $sourceClassId
                && (string) $log->old_session === (string) $sourceSessionId)->count() > 1) $rowWarnings[] = 'DUPLICATE_PROMOTION_HISTORY';
            if ($sameExamArchives->isNotEmpty() && !$matchingPromotion) $rowWarnings[] = 'ARCHIVE_WITHOUT_MATCHING_PROMOTION';
            if ($matchingPromotion && $sameExamArchives->isEmpty()) $rowWarnings[] = 'PROMOTION_WITHOUT_SCOPE_ARCHIVE';

            if ($legacyStatus === 'Fail' && $result->status === 'Pass') $reasons[] = 'OPTIONAL_F_OR_DENOMINATOR_DIFFERENCE';
            if ($result->status === 'Incomplete') $reasons[] = 'MISSING_COMPULSORY_INCOMPLETE';
            if ($result->gpa !== null && $legacyGpa !== null && $legacyGpa > 5 && $result->gpa <= 5) $reasons[] = 'GPA_CAPPED_AT_5';
            if ($legacyCount !== $result->compulsorySubjectCount) $reasons[] = 'OPTIONAL_REMOVED_FROM_DENOMINATOR';
            if (collect($result->subjectResults)->contains(fn ($subject) => $subject->componentFailures !== [])) $reasons[] = 'COMPONENT_FAILURE_DETECTED';
            if (collect($result->subjectResults)->contains(fn ($subject) => str_starts_with($subject->subjectId, 'pair:'))) $reasons[] = 'SUBJECT_PAIR_RESULT';
            if (collect($result->subjectResults)->contains(fn ($subject) => strcasecmp($subject->subjectType, 'Theory') === 0)) $reasons[] = 'THEORY_INCLUDED';
            if (!$placement) $reasons[] = 'STUDENT_HAS_NO_PLACEMENT';
            if ($rollConflict || $batchRollConflict) $reasons[] = 'DESTINATION_ROLL_CONFLICT';
            if ($destinationRecordConflict) $reasons[] = 'DESTINATION_STUDENT_RECORD_EXISTS';

            $rows[] = [
                'studentId' => (int) $student->id, 'roll' => $student->rollNumber,
                'sourceSessionId' => (int) $student->sessName, 'sourceClassId' => (int) $student->className,
                'sourceSectionId' => $student->sectionName === null ? null : (int) $student->sectionName,
                'sourceDepartmentId' => $student->departmentName === null ? null : (int) $student->departmentName,
                'sourceGroupId' => $student->sectionName === null ? null : (int) $student->sectionName,
                'destinationSessionId' => $destinationSessionId, 'destinationClassId' => $destinationClassId,
                'destinationSectionId' => $destinationSectionId,
                'destinationDepartmentId' => $destinationDepartmentId ?? ($student->departmentName === null ? null : (int) $student->departmentName),
                'destinationGroupId' => $destinationSectionId,
                'legacyEligible' => true, 'legacyStatus' => $legacyStatus, 'legacyGpa' => $legacyGpa,
                'centralizedStatus' => $result->status, 'centralizedGpa' => $result->gpa,
                'failedCompulsoryCount' => count($result->failedCompulsorySubjects),
                'missingCompulsoryCount' => count($result->missingCompulsorySubjects),
                'componentFailureCount' => collect($result->subjectResults)->sum(fn ($subject) => count($subject->componentFailures)),
                'placementStatus' => $placement?->status, 'placementPosition' => $placement?->position,
                'alreadyPromoted' => (bool) $matchingPromotion,
                'destinationConflict' => $alreadyDestination || $rollConflict || $batchRollConflict || $destinationRecordConflict,
                'proposedRoll' => $rawRoll, 'eligibilityCategory' => match ($result->status) {
                    'Pass' => 'Academically eligible', 'Fail' => 'Academically failed', default => 'Academically incomplete',
                },
                'blockingReasons' => array_values(array_unique($rowBlockers)),
                'warningReasons' => array_values(array_unique($rowWarnings)),
                'differenceReasons' => array_values(array_unique($reasons)),
                'eligibilityDiffers' => $result->status !== 'Pass',
                'archiveCount' => $sameExamArchives->count(),
                '_result' => $result,
            ];
        }

        foreach ($calculationFailures as $id => $failure) {
            $student = $failure['student'];
            $rows[] = [
                'studentId' => (int) $student->id, 'roll' => $student->rollNumber,
                'sourceSessionId' => (int) $student->sessName, 'sourceClassId' => (int) $student->className,
                'sourceSectionId' => $student->sectionName === null ? null : (int) $student->sectionName,
                'sourceDepartmentId' => $student->departmentName === null ? null : (int) $student->departmentName,
                'sourceGroupId' => $student->sectionName === null ? null : (int) $student->sectionName,
                'destinationSessionId' => $destinationSessionId, 'destinationClassId' => $destinationClassId,
                'destinationSectionId' => $destinationSectionId,
                'destinationDepartmentId' => $destinationDepartmentId ?? ($student->departmentName === null ? null : (int) $student->departmentName),
                'destinationGroupId' => $destinationSectionId,
                'legacyEligible' => true, 'legacyStatus' => 'Unknown', 'legacyGpa' => null,
                'centralizedStatus' => 'CalculationError', 'centralizedGpa' => null,
                'failedCompulsoryCount' => 0, 'missingCompulsoryCount' => 0, 'componentFailureCount' => 0,
                'placementStatus' => null, 'placementPosition' => null, 'alreadyPromoted' => false,
                'destinationConflict' => false, 'proposedRoll' => $student->getRawOriginal('rollNumber'),
                'eligibilityCategory' => 'Calculation error', 'blockingReasons' => ['CALCULATION_ERROR'],
                'warningReasons' => [], 'differenceReasons' => ['CALCULATION_ERROR'],
                'eligibilityDiffers' => true, 'archiveCount' => 0, '_result' => null,
            ];
        }

        return ['rows' => $rows, 'summary' => $this->summary($rows, $blockers, $warnings, $calculationFailures->count()), 'scope' => $scope];
    }

    private function validateScope(array $scope, array &$blockers, array &$warnings): void
    {
        foreach (['examId', 'sourceClassId', 'sourceSessionId', 'destinationClassId', 'destinationSessionId'] as $field) {
            if (($scope[$field] ?? 0) <= 0) $blockers[] = ['code' => 'INVALID_SCOPE', 'message' => "{$field} must be a positive ID."];
        }
        if (!Exam::find($scope['examId'])) $blockers[] = ['code' => 'EXAM_MISSING', 'message' => 'Selected exam does not exist.'];
        if (!classManage::find($scope['sourceClassId'])) $blockers[] = ['code' => 'SOURCE_CLASS_MISSING', 'message' => 'Source class does not exist.'];
        if (!sessionManage::find($scope['sourceSessionId'])) $blockers[] = ['code' => 'SOURCE_SESSION_MISSING', 'message' => 'Source session does not exist.'];
        if (!classManage::find($scope['destinationClassId'])) $blockers[] = ['code' => 'DESTINATION_CLASS_MISSING', 'message' => 'Destination class does not exist.'];
        if (!sessionManage::find($scope['destinationSessionId'])) $blockers[] = ['code' => 'DESTINATION_SESSION_MISSING', 'message' => 'Destination session does not exist.'];
        if ($scope['destinationSectionId'] !== null && !sectionManage::find($scope['destinationSectionId'])) {
            $blockers[] = ['code' => 'DESTINATION_SECTION_MISSING', 'message' => 'Destination section does not exist.'];
        }
        if ($scope['sourceSectionId'] !== null && !sectionManage::find($scope['sourceSectionId'])) {
            $blockers[] = ['code' => 'SOURCE_SECTION_MISSING', 'message' => 'Source section does not exist.'];
        }
        if ($scope['sourceDepartmentId'] !== null && !Department::find($scope['sourceDepartmentId'])) {
            $blockers[] = ['code' => 'SOURCE_DEPARTMENT_MISSING', 'message' => 'Source department does not exist.'];
        }
        if ($scope['destinationDepartmentId'] !== null && !Department::find($scope['destinationDepartmentId'])) {
            $blockers[] = ['code' => 'DESTINATION_DEPARTMENT_MISSING', 'message' => 'Destination department does not exist.'];
        }
        if ($scope['sourceClassId'] === $scope['destinationClassId'] && $scope['sourceSessionId'] === $scope['destinationSessionId']
            && $scope['sourceSectionId'] === $scope['destinationSectionId']) {
            $blockers[] = ['code' => 'SAME_ACADEMIC_SCOPE', 'message' => 'Source and destination academic scopes are identical.'];
        }
        if ($scope['destinationClassId'] < $scope['sourceClassId']) {
            $warnings[] = ['code' => 'CLASS_ORDER_UNVERIFIED', 'message' => 'Destination class ID is lower; the project has no reliable class-order contract.'];
        }
        if ($scope['destinationSectionId'] !== null) {
            $warnings[] = ['code' => 'SECTION_CLASS_MAPPING_UNVERIFIED', 'message' => 'Sections are global; class-specific section validity cannot be proven.'];
        }
        if ($scope['destinationDepartmentId'] !== null) {
            $warnings[] = ['code' => 'DEPARTMENT_CLASS_MAPPING_UNVERIFIED', 'message' => 'No reliable class-to-department mapping is enforced by promotion.'];
        }
    }

    private function summary(array $rows, array $blockers, array $warnings, int $calculationErrors): array
    {
        $rows = collect($rows);
        return [
            'studentsChecked' => $rows->count(),
            'centralizedPass' => $rows->where('centralizedStatus', 'Pass')->count(),
            'centralizedFail' => $rows->where('centralizedStatus', 'Fail')->count(),
            'centralizedIncomplete' => $rows->where('centralizedStatus', 'Incomplete')->count(),
            'legacyEligible' => $rows->where('legacyEligible', true)->count(),
            'centralizedNormallyEligible' => $rows->where('centralizedStatus', 'Pass')->count(),
            'eligibilityDifferences' => $rows->where('eligibilityDiffers', true)->count(),
            'alreadyPromoted' => $rows->where('alreadyPromoted', true)->count(),
            'destinationConflicts' => $rows->where('destinationConflict', true)->count(),
            'rollConflicts' => $rows->filter(fn ($row) => in_array('DESTINATION_ROLL_CONFLICT', $row['blockingReasons'], true))->count(),
            'archiveConflicts' => $rows->filter(fn ($row) => collect($row['warningReasons'])->contains(fn ($reason) => str_contains($reason, 'ARCHIVE')))->count(),
            'missingPlacement' => $rows->whereNull('placementStatus')->count(),
            'duplicateSourceRecords' => $rows->filter(fn ($row) => in_array('DUPLICATE_SOURCE_STUDENT', $row['blockingReasons'], true))->count(),
            'blockingErrors' => count($blockers) + $rows->sum(fn ($row) => count($row['blockingReasons'])),
            'nonBlockingWarnings' => count($warnings) + $rows->sum(fn ($row) => count($row['warningReasons'])),
            'calculationErrors' => $calculationErrors,
            'phase9WouldBeEligible' => $rows->filter(fn ($row) => $row['centralizedStatus'] === 'Pass' && $row['blockingReasons'] === [])->count(),
            'scopeBlockers' => $blockers, 'scopeWarnings' => $warnings,
            'phase9WriteSafe' => $calculationErrors === 0 && $blockers === [] && $rows->every(fn ($row) => $row['blockingReasons'] === []),
            'noRecordsModified' => true,
        ];
    }
}
