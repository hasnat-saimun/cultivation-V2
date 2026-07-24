<?php

namespace App\Services\ResultCalculation;

use App\Models\classManage;
use App\Models\Department;
use App\Models\Exam;
use App\Models\newAdmission;
use App\Models\PromotionAuditLog;
use App\Models\ReligiousSubjectDefault;
use App\Models\ResultArchive;
use App\Models\ResultPublish;
use App\Models\sectionManage;
use App\Models\sessionManage;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class CentralizedPromotionProcessor
{
    public function __construct(
        private PromotionPreviewBuilder $previewBuilder,
        private ResultCalculationBatchBuilder $batchBuilder,
    ) {}

    public function process(
        int $examId,
        int $sourceClassId,
        int $sourceSessionId,
        int $destinationClassId,
        int $destinationSessionId,
        int $destinationSectionId,
        ?int $sourceSectionId = null,
        ?int $sourceDepartmentId = null,
        ?int $destinationDepartmentId = null,
        array $selectedStudentIds = [],
        array $rollNumbers = [],
        bool $dryRun = false,
        string|int|null $actor = null,
    ): array {
        $scope = compact(
            'examId', 'sourceClassId', 'sourceSessionId', 'sourceSectionId', 'sourceDepartmentId',
            'destinationClassId', 'destinationSessionId', 'destinationSectionId', 'destinationDepartmentId'
        );
        $report = $this->emptyReport($scope, $dryRun);
        $promotionCycleId = (string) Str::uuid();
        $report['promotionCycleId'] = $promotionCycleId;
        $selectedStudentIds = array_values(array_unique(array_map('intval', $selectedStudentIds)));
        sort($selectedStudentIds);

        if (!$dryRun && !config('result_engine.promotion_enabled', false)) {
            $this->block($report, 'PROMOTION_ENGINE_DISABLED', 'Centralized promotion persistence is disabled.');
            throw new PromotionProcessingException('Centralized promotion persistence is disabled.', $report);
        }
        if ($selectedStudentIds === []) {
            $this->block($report, 'STUDENT_SELECTION_REQUIRED', 'At least one student must be explicitly selected.');
        }
        $this->validateEntities($scope, $report);
        $report['published'] = $this->isPublished($scope);
        if (!$report['published']) $this->block($report, 'PUBLICATION_REQUIRED', 'The selected exam result scope must be published.');

        try {
            $preview = $this->previewBuilder->build(
                $examId, $sourceClassId, $sourceSessionId, $destinationClassId, $destinationSessionId,
                $sourceSectionId, $sourceDepartmentId, $destinationSectionId, $destinationDepartmentId,
                null, 10000
            );
            $previewRows = collect($preview['rows'])->whereIn('studentId', $selectedStudentIds)->keyBy('studentId');
            $batch = $this->batchBuilder->buildTolerant(
                $examId, $sourceClassId, $sourceSessionId, $sourceSectionId, $sourceDepartmentId
            );
            $entries = collect($batch['entries'])->only($selectedStudentIds);
            $calculationErrors = collect($batch['errors'] ?? [])->only($selectedStudentIds);
            $sourceStudents = collect($batch['students'])->whereIn('id', $selectedStudentIds)->keyBy('id');
            $report['studentsChecked'] = count($selectedStudentIds);

            foreach ($selectedStudentIds as $studentId) {
                if (!$sourceStudents->has($studentId)) {
                    $this->block($report, 'SOURCE_SCOPE_MISMATCH', 'Selected student does not belong to the requested source scope.', $studentId);
                }
                if ($calculationErrors->has($studentId)) {
                    $this->block($report, 'CALCULATION_ERROR', 'Centralized result calculation failed.', $studentId);
                }
                $previewRow = $previewRows->get($studentId);
                if ($previewRow) {
                    foreach ($previewRow['blockingReasons'] as $reason) {
                        if (in_array($reason, ['CENTRALIZED_FAIL', 'CENTRALIZED_INCOMPLETE'], true)) continue;
                        $this->block($report, $reason, 'Promotion preview detected a blocking conflict.', $studentId);
                    }
                }
            }

            $destinationStudents = newAdmission::query()
                ->where('sessName', $destinationSessionId)->where('className', $destinationClassId)
                ->where('sectionName', $destinationSectionId)->get();
            $destinationRolls = $destinationStudents->groupBy(fn ($student) => $this->normalizeRoll($student->getRawOriginal('rollNumber')));
            $destinationStdIds = $destinationStudents->groupBy(fn ($student) => (string) $student->stdId);
            $subjects = Subject::query()->get()->keyBy(fn ($subject) => (string) $subject->id);
            $religiousDefault = ReligiousSubjectDefault::query()->where('classId', $destinationClassId)->value('subjectId');
            $archives = ResultArchive::query()->whereIn('student_id', $selectedStudentIds)->orderBy('id')->get()->groupBy('student_id');
            $audits = PromotionAuditLog::query()->whereIn('student_id', $selectedStudentIds)->orderBy('id')->get()->groupBy('student_id');
            $proposedRollMap = []; $payloads = [];

            foreach ($selectedStudentIds as $studentId) {
                if ($sourceStudents->has($studentId)) continue;
                $matchingAudit = $audits->get($studentId, collect())->filter(fn ($audit) =>
                    $audit->engine === 'centralized'
                    && $audit->promotion_cycle_id !== null
                    && $audit->reverted_at === null
                    && (string)$audit->exam_id === (string)$examId
                    &&
                    (string)$audit->old_session === (string)$sourceSessionId
                    && (string)$audit->old_class === (string)$sourceClassId
                    && (string)$audit->new_session === (string)$destinationSessionId
                    && (string)$audit->new_class === (string)$destinationClassId
                    && (string)$audit->new_section === (string)$destinationSectionId
                );
                $matchingArchive = $archives->get($studentId, collect())->filter(fn ($archive) =>
                    $matchingAudit->contains(fn ($audit) =>
                        $archive->promotion_cycle_id === $audit->promotion_cycle_id
                        && (string)$archive->exam_id === (string)$audit->exam_id
                    )
                );
                if ($matchingArchive->count() === 1 && $matchingAudit->count() === 1) {
                    $this->block($report,'ALREADY_PROMOTED','The same logical promotion already exists.',$studentId);
                } elseif ($matchingArchive->isNotEmpty() || $matchingAudit->isNotEmpty()) {
                    $this->block($report,'AMBIGUOUS_PROMOTION_STATE','Student is outside source scope with partial archive/audit evidence.',$studentId);
                }
            }

            foreach ($selectedStudentIds as $studentId) {
                $entry = $entries->get($studentId);
                $student = $sourceStudents->get($studentId);
                if (!$entry || !$student) continue;
                $result = $entry['result'];
                if ($result->status === 'Fail') $this->block($report, 'ACADEMIC_STATUS_FAIL', 'Centralized status is Fail.', $studentId);
                elseif ($result->status === 'Incomplete') $this->block($report, 'ACADEMIC_STATUS_INCOMPLETE', 'Centralized status is Incomplete.', $studentId);
                elseif ($result->status !== 'Pass') $this->block($report, 'CALCULATION_ERROR', 'Centralized status is unsupported.', $studentId);
                else $report['passStudents']++;

                if ((int) $student->sessName !== $sourceSessionId || (int) $student->className !== $sourceClassId
                    || ($sourceSectionId !== null && (int) $student->sectionName !== $sourceSectionId)
                    || ($sourceDepartmentId !== null && (int) $student->departmentName !== $sourceDepartmentId)) {
                    $this->block($report, 'SOURCE_SCOPE_MISMATCH', 'Student source fields no longer match the requested scope.', $studentId);
                }

                $newRoll = array_key_exists($studentId, $rollNumbers) && trim((string) $rollNumbers[$studentId]) !== ''
                    ? trim((string) $rollNumbers[$studentId])
                    : trim((string) $student->getRawOriginal('rollNumber'));
                $rollKey = $this->normalizeRoll($newRoll);
                if ($rollKey === '' || strlen($newRoll) > 64) {
                    $this->block($report, 'INVALID_DESTINATION_ROLL', 'Destination roll is blank or invalid.', $studentId);
                } else {
                    if (isset($proposedRollMap[$rollKey]) && $proposedRollMap[$rollKey] !== $studentId) {
                        $this->block($report, 'DESTINATION_ROLL_CONFLICT', 'Two selected students have the same destination roll.', $studentId);
                    }
                    $proposedRollMap[$rollKey] = $studentId;
                    if ($destinationRolls->get($rollKey, collect())->contains(fn ($candidate) => (int) $candidate->id !== $studentId)) {
                        $this->block($report, 'DESTINATION_ROLL_CONFLICT', 'Destination roll already exists.', $studentId);
                    }
                }
                if ($student->stdId && $destinationStdIds->get((string) $student->stdId, collect())
                    ->contains(fn ($candidate) => (int) $candidate->id !== $studentId)) {
                    $this->block($report, 'DESTINATION_STUDENT_CONFLICT', 'Student identity already exists in destination scope.', $studentId);
                }

                $destinationDepartment = $destinationDepartmentId ?? ($student->departmentName === null ? null : (int) $student->departmentName);
                if ($student->departmentName !== null && $destinationDepartmentId === null) {
                    $this->block($report, 'DEPARTMENT_COMPATIBILITY_UNPROVEN', 'Destination compatibility for the existing department cannot be proven.', $studentId);
                }
                $fourthId = (int) ($student->fourthSubjectId ?? 0);
                if ($fourthId > 0) {
                    $fourth = $subjects->get((string) $fourthId);
                    if (!$fourth || strcasecmp(trim((string) $fourth->subjectType), 'Optional') !== 0
                        || !in_array((string) ($fourth->assign_class ?? '0'), ['0', (string) $destinationClassId], true)) {
                        $this->block($report, 'INVALID_DESTINATION_FOURTH_SUBJECT', 'Fourth subject is not valid for the destination curriculum.', $studentId, $fourthId);
                    }
                }

                $religiousId = (int) ($student->religiousSubjectId ?? 0) ?: (int) ($religiousDefault ?? 0);
                if ($religiousId > 0) {
                    $religious = $subjects->get((string) $religiousId);
                    if (!$religious || !($religious->isReligious ?? false)
                        || !in_array((string) ($religious->assign_class ?? '0'), ['0', (string) $destinationClassId], true)) {
                        $this->block($report, 'INVALID_RELIGIOUS_MAPPING', 'Religious subject mapping is invalid for the destination curriculum.', $studentId, $religiousId);
                    }
                } elseif (trim((string) ($student->religion ?? '')) !== '') {
                    $this->block($report, 'INVALID_RELIGIOUS_MAPPING', 'No destination religious subject mapping can be resolved.', $studentId);
                }

                $studentArchives = $archives->get($studentId, collect());
                $sameSource = $studentArchives->filter(fn ($archive) =>
                    (string) $archive->old_session === (string) $sourceSessionId
                    && (string) $archive->old_class === (string) $sourceClassId
                    && ($sourceSectionId === null || (string) $archive->old_section === (string) $sourceSectionId)
                );
                $legacyArchives = $sameSource->filter(fn ($archive) =>
                    $archive->promotion_cycle_id === null
                    && ($archive->exam_id === null || (string)$archive->exam_id === (string)$examId)
                );
                $matchingAudits = $audits->get($studentId, collect())->filter(fn ($audit) =>
                    $audit->engine === 'centralized'
                    && $audit->promotion_cycle_id !== null
                    && $audit->reverted_at === null
                    && (string)$audit->exam_id === (string)$examId
                    &&
                    (string) $audit->old_session === (string) $sourceSessionId
                    && (string) $audit->old_class === (string) $sourceClassId
                    && ($sourceSectionId === null || (string) $audit->old_section === (string) $sourceSectionId)
                    && (string) $audit->new_session === (string) $destinationSessionId
                    && (string) $audit->new_class === (string) $destinationClassId
                    && (string) $audit->new_section === (string) $destinationSectionId
                );
                $matchingArchives = $sameSource->filter(fn ($archive) =>
                    $matchingAudits->contains(fn ($audit) =>
                        $archive->promotion_cycle_id === $audit->promotion_cycle_id
                        && (string)$archive->exam_id === (string)$audit->exam_id
                    )
                );
                if ($legacyArchives->isNotEmpty()) $this->block($report, 'AMBIGUOUS_PROMOTION_STATE', 'A legacy archive without centralized cycle identity matches the source scope.', $studentId);
                if ($matchingArchives->count() > 1) $this->block($report, 'DUPLICATE_ARCHIVE', 'Duplicate active centralized archives exist.', $studentId);
                elseif ($matchingArchives->count() === 1 && $matchingAudits->count() === 1) $this->block($report, 'ALREADY_PROMOTED', 'The same active logical promotion already exists.', $studentId);
                elseif ($matchingArchives->isNotEmpty() || $matchingAudits->isNotEmpty()) $this->block($report, 'AMBIGUOUS_PROMOTION_STATE', 'Archive and active promotion audit state is partial.', $studentId);
                if ($matchingAudits->count() > 1) $this->block($report, 'DUPLICATE_PROMOTION_HISTORY', 'Duplicate promotion audit history exists.', $studentId);

                $subjectsById = collect($entry['subjects'])->keyBy(fn ($subject) => (string)$subject->id);
                $marksBySubject = $student->marksheet->keyBy(fn ($mark) => (string)$mark->subjectId);
                $archiveSubjects = array_map(function (SubjectResult $subjectResult) use ($subjectsById, $marksBySubject) {
                    $sourceIds = collect($subjectResult->sourceSubjectIds);
                    $sourceSubjects = $sourceIds->map(fn ($id) => $subjectsById->get((string)$id))->filter();
                    $sourceMarks = $sourceIds->map(fn ($id) => $marksBySubject->get((string)$id))->filter();
                    return [
                        'id'=>$subjectResult->subjectId,
                        'name'=>$sourceSubjects->pluck('subjectName')->filter()->implode(' + ') ?: $subjectResult->subjectId,
                        'cq'=>$sourceMarks->sum(fn ($mark)=>(float)($mark->subjectMarks ?? 0)),
                        'mcq'=>$sourceMarks->sum(fn ($mark)=>(float)($mark->objectMarks ?? 0)),
                        'practical'=>$sourceMarks->sum(fn ($mark)=>(float)($mark->practicalMarks ?? 0)),
                        'total'=>$subjectResult->obtainedMarks,
                        'marks'=>$subjectResult->obtainedMarks,
                        'grade'=>$subjectResult->letterGrade,
                        'gradePoint'=>$subjectResult->gradePoint,
                        'gpa'=>$subjectResult->gradePoint,
                        'type'=>$subjectResult->isOptional ? 'Optional' : $subjectResult->subjectType,
                    ];
                }, $result->subjectResults);
                $archiveData = [
                    'calculation_version' => $result->calculationVersion,
                    'exam_id' => $examId,
                    'source' => ['session_id'=>$sourceSessionId,'class_id'=>$sourceClassId,'section_id'=>$student->sectionName,'department_id'=>$student->departmentName,'roll'=>$student->getRawOriginal('rollNumber')],
                    'destination' => ['session_id'=>$destinationSessionId,'class_id'=>$destinationClassId,'section_id'=>$destinationSectionId,'department_id'=>$destinationDepartment,'roll'=>$newRoll],
                    'gpa' => $result->gpa, 'status' => $result->status, 'result' => $result->status,
                    'total_marks' => collect($result->subjectResults)->sum(fn (SubjectResult $subject) => (float)($subject->obtainedMarks ?? 0)),
                    'optional_bonus' => $result->optionalBonus,
                    'compulsory_gp_sum' => $result->compulsoryGpSum, 'compulsory_subject_count' => $result->compulsorySubjectCount,
                    'failed_compulsory_subjects' => $result->failedCompulsorySubjects,
                    'missing_compulsory_subjects' => $result->missingCompulsorySubjects,
                    'subjects' => $archiveSubjects,
                    'centralized_subjects' => array_map(fn (SubjectResult $subject) => $subject->toArray(), $result->subjectResults),
                    'marks' => $student->marksheet->map(fn ($mark) => [
                        'subject_id'=>$mark->subjectId,'cq'=>$mark->subjectMarks,'mcq'=>$mark->objectMarks,
                        'practical'=>$mark->practicalMarks,'total'=>$mark->totalMarks,
                    ])->values()->all(),
                    'promoted_at' => now()->toISOString(),
                ];
                $payloads[$studentId] = [
                    'student' => $student, 'result' => $result, 'newRoll' => $newRoll,
                    'destinationDepartment' => $destinationDepartment, 'archiveData' => $archiveData,
                ];
            }

            $report['archiveSafeStudents'] = count($payloads);
            $report['wouldPromoteCount'] = count($payloads);
            $report['wouldCreateArchiveCount'] = count($payloads);
            $report['wouldUpdateStudentCount'] = count($payloads);
            $report['wouldCreateAuditCount'] = count($payloads);
            if (count($payloads) !== count($selectedStudentIds)) {
                $this->block($report, 'EXPECTED_ROW_COUNT_MISMATCH', 'Prepared payload count does not match selected student count.');
            }
            if ($report['blockingErrors'] !== []) throw new PromotionProcessingException('Centralized promotion preflight failed.', $report);
            $report['writeSafe'] = true;
            if ($dryRun) {
                $report['noRecordsModified'] = true;
                return $report;
            }

            DB::transaction(function () use ($scope, $selectedStudentIds, $payloads, $promotionCycleId, $actor, &$report) {
                $locked = newAdmission::query()->whereIn('id', $selectedStudentIds)->lockForUpdate()->get()->keyBy('id');
                if ($locked->count() !== count($selectedStudentIds)) throw new \RuntimeException('Lock-time student count mismatch.');
                foreach ($selectedStudentIds as $studentId) {
                    $student = $locked->get($studentId);
                    if ((int) $student->sessName !== $scope['sourceSessionId'] || (int) $student->className !== $scope['sourceClassId']
                        || ($scope['sourceSectionId'] !== null && (int) $student->sectionName !== $scope['sourceSectionId'])) {
                        throw new \RuntimeException('Lock-time source scope mismatch.');
                    }
                }
                $this->recheckConflicts($scope, $selectedStudentIds, $payloads);

                $archiveRows = []; $auditRows = [];
                foreach ($payloads as $studentId => $payload) {
                    $student = $locked->get($studentId);
                    $archiveRows[] = [
                        'student_id'=>$studentId, 'old_class'=>(string)$student->className,
                        'old_roll'=>(string)$student->getRawOriginal('rollNumber'), 'old_session'=>(string)$student->sessName,
                        'old_section'=>$student->sectionName === null ? null : (string)$student->sectionName,
                        'exam_id'=>$scope['examId'], 'promotion_cycle_id'=>$promotionCycleId,
                        'result_data'=>json_encode($payload['archiveData'], JSON_THROW_ON_ERROR),
                        'created_at'=>now(), 'updated_at'=>now(),
                    ];
                    $auditRows[] = [
                        'promotion_id'=>$promotionCycleId,'student_id'=>$studentId,
                        'exam_id'=>$scope['examId'],'promotion_cycle_id'=>$promotionCycleId,'engine'=>'centralized',
                        'old_session'=>(string)$student->sessName,'old_class'=>(string)$student->className,
                        'old_section'=>$student->sectionName,'old_department'=>$student->departmentName,
                        'old_roll'=>$student->getRawOriginal('rollNumber'),
                        'new_session'=>(string)$scope['destinationSessionId'],'new_class'=>(string)$scope['destinationClassId'],
                        'new_section'=>(string)$scope['destinationSectionId'],'new_department'=>$payload['destinationDepartment'],
                        'new_roll'=>$payload['newRoll'],
                        'performed_by'=>is_numeric($actor)?(int)$actor:null,'ip_address'=>request()?->ip(),
                        'actor_context'=>is_numeric($actor)?'web':(string)($actor ?: 'system'),
                        'created_at'=>now(),'updated_at'=>now(),
                    ];
                }
                $this->insertArchives($archiveRows);
                foreach ($payloads as $studentId => $payload) {
                    $student = $locked->get($studentId);
                    $student->sessName = $scope['destinationSessionId'];
                    $student->className = $scope['destinationClassId'];
                    $student->sectionName = $scope['destinationSectionId'];
                    if ($payload['destinationDepartment'] !== null) $student->departmentName = $payload['destinationDepartment'];
                    $student->rollNumber = $payload['newRoll'];
                    $this->saveStudent($student);
                }
                $this->insertAudits($auditRows);
                $archiveCount = ResultArchive::query()->where('promotion_cycle_id',$promotionCycleId)
                    ->whereIn('student_id',$selectedStudentIds)->count();
                $updatedCount = newAdmission::query()->whereIn('id',$selectedStudentIds)
                    ->where('sessName',$scope['destinationSessionId'])->where('className',$scope['destinationClassId'])
                    ->where('sectionName',$scope['destinationSectionId'])->count();
                $auditCount = PromotionAuditLog::query()->where('promotion_cycle_id',$promotionCycleId)
                    ->whereIn('student_id',$selectedStudentIds)->count();
                if ($archiveCount !== count($selectedStudentIds) || $updatedCount !== count($selectedStudentIds)
                    || $auditCount !== count($selectedStudentIds)) {
                    throw new \RuntimeException('Write payload count mismatch.');
                }
                $report['archivesCreated'] = $archiveCount;
                $report['studentsUpdated'] = $updatedCount;
                $report['auditsCreated'] = $auditCount;
            }, 3);

            $report['studentsPromoted'] = count($payloads);
            $report['transactionCommitted'] = true;
            $report['noRecordsModified'] = false;
            Log::info('Centralized promotion completed.', $this->logContext($report, $actor) + ['transaction_committed'=>true]);
            return $report;
        } catch (PromotionProcessingException $exception) {
            Log::warning('Centralized promotion blocked.', $this->logContext($exception->report, $actor) + [
                'stage'=>'preflight','blocking_codes'=>collect($exception->report['blockingErrors'])->pluck('code')->unique()->values()->all(),
            ]);
            throw $exception;
        } catch (Throwable $exception) {
            $this->block($report, 'PROMOTION_TRANSACTION_FAILED', 'Centralized promotion failed safely and was rolled back.');
            Log::error('Centralized promotion failed and rolled back.', $this->logContext($report, $actor) + [
                'stage'=>'calculation_or_transaction','exception'=>get_class($exception),'rolled_back'=>true,
            ]);
            throw new PromotionProcessingException('Centralized promotion failed safely.', $report);
        }
    }

    protected function insertArchives(array $rows): void { ResultArchive::query()->insert($rows); }
    protected function saveStudent(newAdmission $student): void { $student->save(); }
    protected function insertAudits(array $rows): void { PromotionAuditLog::query()->insert($rows); }

    private function recheckConflicts(array $scope, array $studentIds, array $payloads): void
    {
        $rolls = array_map(fn ($payload) => $this->normalizeRoll($payload['newRoll']), $payloads);
        if (newAdmission::query()->where('sessName',$scope['destinationSessionId'])->where('className',$scope['destinationClassId'])
            ->where('sectionName',$scope['destinationSectionId'])->whereNotIn('id',$studentIds)->whereIn(DB::raw('LOWER(TRIM(rollNumber))'), $rolls)->lockForUpdate()->exists()) {
            throw new \RuntimeException('Lock-time destination roll conflict.');
        }
        $activeCycles = PromotionAuditLog::query()->whereIn('student_id',$studentIds)
            ->where('engine','centralized')->whereNull('reverted_at')->whereNotNull('promotion_cycle_id')
            ->lockForUpdate()->pluck('promotion_cycle_id');
        $legacyConflict = ResultArchive::query()->whereIn('student_id',$studentIds)
            ->whereNull('promotion_cycle_id')->where('old_session',(string)$scope['sourceSessionId'])
            ->where('old_class',(string)$scope['sourceClassId'])
            ->where(fn ($query) => $query->whereNull('exam_id')->orWhere('exam_id',$scope['examId']))
            ->lockForUpdate()->exists();
        $activeConflict = $activeCycles->isNotEmpty() && ResultArchive::query()
            ->whereIn('student_id',$studentIds)->whereIn('promotion_cycle_id',$activeCycles)
            ->lockForUpdate()->exists();
        if ($legacyConflict || $activeConflict) {
            throw new \RuntimeException('Lock-time archive/idempotency conflict.');
        }
    }

    private function validateEntities(array $scope, array &$report): void
    {
        foreach (['examId','sourceClassId','sourceSessionId','destinationClassId','destinationSessionId','destinationSectionId'] as $field) {
            if (($scope[$field] ?? 0) <= 0) $this->block($report, 'INVALID_SCOPE', "{$field} must be a positive ID.");
        }
        if (!classManage::find($scope['sourceClassId'])) $this->block($report,'SOURCE_CLASS_MISSING','Source class does not exist.');
        if (!Exam::find($scope['examId'])) $this->block($report,'EXAM_MISSING','Selected exam does not exist.');
        if (!sessionManage::find($scope['sourceSessionId'])) $this->block($report,'SOURCE_SESSION_MISSING','Source session does not exist.');
        if (!classManage::find($scope['destinationClassId'])) $this->block($report,'DESTINATION_CLASS_MISSING','Destination class does not exist.');
        if (!sessionManage::find($scope['destinationSessionId'])) $this->block($report,'DESTINATION_SESSION_MISSING','Destination session does not exist.');
        if (!sectionManage::find($scope['destinationSectionId'])) $this->block($report,'DESTINATION_SECTION_MISSING','Destination section does not exist.');
        if ($scope['sourceDepartmentId'] !== null && !Department::find($scope['sourceDepartmentId'])) $this->block($report,'SOURCE_DEPARTMENT_MISSING','Source department does not exist.');
        if ($scope['destinationDepartmentId'] !== null && !Department::find($scope['destinationDepartmentId'])) $this->block($report,'INVALID_DESTINATION_DEPARTMENT','Destination department does not exist.');
        if ($scope['sourceClassId'] === $scope['destinationClassId'] && $scope['sourceSessionId'] === $scope['destinationSessionId']
            && $scope['sourceSectionId'] === $scope['destinationSectionId']) $this->block($report,'SAME_ACADEMIC_SCOPE','Source and destination scopes are identical.');
    }

    private function isPublished(array $scope): bool
    {
        return ResultPublish::query()->where('examId',(string)$scope['examId'])->where('classId',(string)$scope['sourceClassId'])
            ->where('sessionId',(string)$scope['sourceSessionId'])
            ->where('status', ResultPublish::STATUS_PUBLISHED)
            ->where(function ($query) use ($scope) {
                if ($scope['sourceSectionId'] === null) {
                    $query->whereNull('groupId');
                    return;
                }
                $query->where('groupId',(string)$scope['sourceSectionId'])
                    ->orWhere(fn ($legacy) => $legacy->whereNull('groupId')->where('legacyImported', true));
            })->exists();
    }

    private function block(array &$report, string $code, string $message, int|string|null $studentId = null, int|string|null $subjectId = null): void
    {
        $issue = array_filter(compact('code','message','studentId','subjectId'), fn ($value) => $value !== null);
        foreach ($report['blockingErrors'] as $existing) {
            if ($existing['code'] === $code && ($existing['studentId'] ?? null) === $studentId
                && ($existing['subjectId'] ?? null) === $subjectId) return;
        }
        $report['blockingErrors'][] = $issue;
    }

    private function normalizeRoll(mixed $roll): string { return strtolower(trim((string) $roll)); }

    private function emptyReport(array $scope, bool $dryRun): array
    {
        return $scope + ['dryRun'=>$dryRun,'published'=>false,'studentsChecked'=>0,'passStudents'=>0,
            'promotionCycleId'=>null,
            'archiveSafeStudents'=>0,'wouldPromoteCount'=>0,'wouldCreateArchiveCount'=>0,'wouldUpdateStudentCount'=>0,
            'wouldCreateAuditCount'=>0,'studentsPromoted'=>0,'archivesCreated'=>0,'studentsUpdated'=>0,'auditsCreated'=>0,
            'blockingErrors'=>[],'warnings'=>[],'writeSafe'=>false,'transactionCommitted'=>false,'noRecordsModified'=>true];
    }

    private function logContext(array $report, string|int|null $actor): array
    {
        return ['engine'=>'centralized','exam_id'=>$report['examId'],'source_class_id'=>$report['sourceClassId'],
            'promotion_cycle_id'=>$report['promotionCycleId'],
            'source_session_id'=>$report['sourceSessionId'],'source_section_id'=>$report['sourceSectionId'],
            'destination_class_id'=>$report['destinationClassId'],'destination_session_id'=>$report['destinationSessionId'],
            'destination_section_id'=>$report['destinationSectionId'],'students'=>$report['studentsChecked'],'actor'=>$actor];
    }
}
