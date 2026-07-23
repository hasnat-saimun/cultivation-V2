<?php

namespace App\Services\ResultCalculation;

use App\Models\classManage;
use App\Models\Department;
use App\Models\newAdmission;
use App\Models\PromotionAuditLog;
use App\Models\ResultArchive;
use App\Models\sectionManage;
use App\Models\sessionManage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class CentralizedPromotionReverter
{
    public function process(
        string $promotionCycleId,
        array $selectedStudentIds = [],
        bool $all = false,
        bool $dryRun = false,
        string|int|null $actor = null,
        ?string $reason = null,
    ): array {
        $promotionCycleId = trim($promotionCycleId);
        $selectedStudentIds = array_values(array_unique(array_map('intval', $selectedStudentIds)));
        sort($selectedStudentIds);
        $report = $this->emptyReport($promotionCycleId, $dryRun);

        if ($promotionCycleId === '') {
            $this->block($report, 'PROMOTION_CYCLE_REQUIRED', 'A promotion cycle ID is required.');
        }
        if (!$all && $selectedStudentIds === []) {
            $this->block($report, 'STUDENT_SELECTION_REQUIRED', 'Select students or explicitly use all students in the cycle.');
        }
        if (!$dryRun && !config('result_engine.promotion_revert_enabled', false)) {
            $this->block($report, 'PROMOTION_REVERT_ENGINE_DISABLED', 'Centralized promotion revert persistence is disabled.');
        }

        try {
            $audits = $this->auditQuery($promotionCycleId, $selectedStudentIds, $all)->get();
            $report['studentsChecked'] = $audits->count();
            if ($audits->isEmpty()) {
                $this->block($report, 'CENTRALIZED_PROMOTION_NOT_FOUND', 'No centralized promotion audit matches the requested identity.');
            }
            if (!$all && $audits->pluck('student_id')->map(fn ($id) => (int)$id)->sort()->values()->all() !== $selectedStudentIds) {
                $this->block($report, 'MISSING_AUDIT', 'One or more selected students have no matching centralized audit.');
            }

            $studentIds = $audits->pluck('student_id')->map(fn ($id) => (int)$id)->all();
            $archives = ResultArchive::query()
                ->where('promotion_cycle_id', $promotionCycleId)
                ->whereIn('student_id', $studentIds)
                ->orderBy('id')->get()->groupBy('student_id');
            $students = newAdmission::query()->whereIn('id', $studentIds)->get()->keyBy('id');
            $payloads = $this->validateAndBuild($audits, $archives, $students, $report);

            $report['wouldRestoreCount'] = count($payloads);
            $report['wouldMarkRevertedCount'] = count($payloads);
            $report['restorePayloads'] = array_values(array_map(
                fn ($payload) => ['studentId'=>$payload['studentId']] + $payload['restore'],
                $payloads
            ));
            if (count($payloads) !== count($studentIds)) {
                $this->block($report, 'EXPECTED_ROW_COUNT_MISMATCH', 'Prepared restore count does not match the selected audit count.');
            }
            if ($report['blockingErrors'] !== []) {
                throw new PromotionRevertException('Centralized promotion revert preflight failed.', $report);
            }

            $report['writeSafe'] = true;
            if ($dryRun) return $report;

            $revertCycleId = (string) Str::uuid();
            $report['revertCycleId'] = $revertCycleId;
            DB::transaction(function () use (
                $promotionCycleId, $studentIds, $payloads, $actor, $reason, $revertCycleId, &$report
            ) {
                $lockedStudents = newAdmission::query()->whereIn('id', $studentIds)
                    ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                $lockedAudits = PromotionAuditLog::query()
                    ->where('promotion_cycle_id', $promotionCycleId)->where('engine', 'centralized')
                    ->whereIn('student_id', $studentIds)->orderBy('id')->lockForUpdate()->get();
                $lockedArchives = ResultArchive::query()
                    ->where('promotion_cycle_id', $promotionCycleId)->whereIn('student_id', $studentIds)
                    ->orderBy('id')->lockForUpdate()->get()->groupBy('student_id');

                $lockedReport = $this->emptyReport($promotionCycleId, false);
                $lockedPayloads = $this->validateAndBuild($lockedAudits, $lockedArchives, $lockedStudents, $lockedReport);
                if ($lockedReport['blockingErrors'] !== [] || count($lockedPayloads) !== count($payloads)) {
                    throw new PromotionRevertException('Lock-time centralized revert validation failed.', $lockedReport);
                }

                foreach ($lockedPayloads as $payload) {
                    $student = $lockedStudents->get($payload['studentId']);
                    foreach ($payload['restore'] as $field => $value) $student->{$field} = $value;
                    $this->saveStudent($student);
                }

                $updatedAudits = $this->markAuditsReverted(
                    $promotionCycleId, $studentIds, $revertCycleId, $actor, $reason
                );
                $restoredCount = $this->countRestoredStudents($lockedPayloads);
                if ($restoredCount !== count($studentIds) || $updatedAudits !== count($studentIds)) {
                    throw new \RuntimeException('Centralized revert write count mismatch.');
                }
                $report['studentsRestored'] = $restoredCount;
                $report['auditsMarkedReverted'] = $updatedAudits;
            }, 3);

            $report['transactionCommitted'] = true;
            $report['noRecordsModified'] = false;
            Log::info('Centralized promotion revert completed.', $this->logContext($report, $actor) + [
                'transaction_committed'=>true,
            ]);
            return $report;
        } catch (PromotionRevertException $exception) {
            $finalReport = $exception->report;
            if ($finalReport['blockingErrors'] === []) {
                $this->block($finalReport, 'AMBIGUOUS_PROMOTION_STATE', 'Promotion state changed during locked validation.');
            }
            Log::warning('Centralized promotion revert blocked.', $this->logContext($finalReport, $actor) + [
                'stage'=>'preflight_or_lock_recheck',
                'blocking_codes'=>collect($finalReport['blockingErrors'])->pluck('code')->unique()->values()->all(),
            ]);
            throw new PromotionRevertException($exception->getMessage(), $finalReport);
        } catch (Throwable $exception) {
            $this->block($report, 'PROMOTION_REVERT_TRANSACTION_FAILED', 'Centralized revert failed safely and was rolled back.');
            Log::error('Centralized promotion revert failed and rolled back.', $this->logContext($report, $actor) + [
                'stage'=>'transaction','exception'=>get_class($exception),'rolled_back'=>true,
            ]);
            throw new PromotionRevertException('Centralized promotion revert failed safely.', $report);
        }
    }

    protected function saveStudent(newAdmission $student): void
    {
        $student->save();
    }

    protected function markAuditsReverted(
        string $promotionCycleId,
        array $studentIds,
        string $revertCycleId,
        string|int|null $actor,
        ?string $reason,
    ): int {
        return PromotionAuditLog::query()
            ->where('promotion_cycle_id', $promotionCycleId)->where('engine', 'centralized')
            ->whereIn('student_id', $studentIds)->whereNull('reverted_at')
            ->update([
                'reverted_at'=>now(),
                'reverted_by'=>is_numeric($actor) ? (int)$actor : null,
                'revert_cycle_id'=>$revertCycleId,
                'revert_reason'=>$reason === null ? null : Str::limit(trim($reason), 255, ''),
                'updated_at'=>now(),
            ]);
    }

    protected function countRestoredStudents(array $payloads): int
    {
        $count = 0;
        foreach ($payloads as $payload) {
            $restore = $payload['restore'];
            $query = newAdmission::query()->whereKey($payload['studentId']);
            foreach ($restore as $field => $value) {
                $value === null ? $query->whereNull($field) : $query->where($field, $value);
            }
            if ($query->exists()) $count++;
        }
        return $count;
    }

    private function validateAndBuild(
        Collection $audits,
        Collection $archives,
        Collection $students,
        array &$report,
    ): array {
        $payloads = [];
        $sourceRolls = [];

        foreach ($audits as $audit) {
            $studentId = (int)$audit->student_id;
            if ($audit->engine !== 'centralized' || !$audit->promotion_cycle_id) {
                $this->block($report, 'LEGACY_PROMOTION', 'Legacy or unidentified promotion cannot use centralized revert.', $studentId);
                continue;
            }
            if (!$audit->exam_id) $this->block($report, 'MISSING_EXAM_IDENTITY', 'Centralized audit has no selected exam identity.', $studentId);
            if ($audit->reverted_at !== null || $audit->revert_cycle_id !== null) {
                $this->block($report, 'ALREADY_REVERTED', 'The promotion was already reverted.', $studentId);
            }
            $studentArchives = $archives->get($studentId, collect());
            if ($studentArchives->isEmpty()) {
                $this->block($report, 'MISSING_ARCHIVE', 'Matching centralized archive is missing.', $studentId);
                continue;
            }
            if ($studentArchives->count() !== 1) {
                $this->block($report, 'DUPLICATE_ARCHIVE', 'Centralized archive identity is ambiguous.', $studentId);
                continue;
            }
            $archive = $studentArchives->first();
            if ((string)$archive->exam_id !== (string)$audit->exam_id) {
                $this->block($report, 'ARCHIVE_AUDIT_MISMATCH', 'Archive and audit selected exam do not match.', $studentId);
            }
            $data = is_array($archive->result_data) ? $archive->result_data : [];
            $source = $data['source'] ?? [];
            $destination = $data['destination'] ?? [];
            if (!$this->snapshotMatchesAudit($source, $destination, $archive, $audit)) {
                $this->block($report, 'ARCHIVE_AUDIT_MISMATCH', 'Archive and audit academic scopes disagree.', $studentId);
            }

            $student = $students->get($studentId);
            if (!$student) {
                $this->block($report, 'STUDENT_MISSING', 'Student record is missing.', $studentId);
                continue;
            }
            if (!$this->studentMatchesDestination($student, $audit)) {
                $this->block($report, 'STUDENT_DESTINATION_MISMATCH', 'Student no longer matches the recorded destination scope and roll.', $studentId);
            }
            if (!sessionManage::find($audit->old_session) || !classManage::find($audit->old_class)
                || ($audit->old_section !== null && !sectionManage::find($audit->old_section))
                || ($audit->old_department !== null && !Department::find($audit->old_department))) {
                $this->block($report, 'SOURCE_ENTITY_MISSING', 'One or more recorded source entities no longer exist.', $studentId);
            }

            $later = PromotionAuditLog::query()->where('student_id', $studentId)
                ->where('id', '>', $audit->id)->orderBy('id')->get();
            if ($later->contains(fn ($row) => $row->engine === 'centralized')) {
                $this->block($report, 'LATER_PROMOTION_EXISTS', 'A later centralized promotion exists.', $studentId);
            }
            if ($later->contains(fn ($row) => $row->engine !== 'centralized')) {
                $this->block($report, 'LATER_LEGACY_PROMOTION_EXISTS', 'A later legacy promotion record exists.', $studentId);
            }

            $rollKey = implode('|', [
                (string)$audit->old_session, (string)$audit->old_class,
                (string)($audit->old_section ?? ''), $this->normalizeRoll($audit->old_roll),
            ]);
            if (isset($sourceRolls[$rollKey]) && $sourceRolls[$rollKey] !== $studentId) {
                $this->block($report, 'SOURCE_ROLL_CONFLICT', 'Selected students would restore the same source roll.', $studentId);
            }
            $sourceRolls[$rollKey] = $studentId;
            if ($this->sourceRollOccupied($audit, $audits->pluck('student_id')->all())) {
                $this->block($report, 'SOURCE_ROLL_CONFLICT', 'The recorded source roll is occupied.', $studentId);
            }

            $payloads[$studentId] = [
                'studentId'=>$studentId,
                'auditId'=>(int)$audit->id,
                'archiveId'=>(int)$archive->id,
                'restore'=>[
                    'sessName'=>$audit->old_session,
                    'className'=>$audit->old_class,
                    'sectionName'=>$audit->old_section,
                    'departmentName'=>$audit->old_department,
                    'rollNumber'=>$audit->old_roll,
                ],
            ];
        }
        return $payloads;
    }

    private function auditQuery(string $cycle, array $studentIds, bool $all)
    {
        return PromotionAuditLog::query()
            ->where('promotion_cycle_id', $cycle)->where('engine', 'centralized')
            ->when(!$all, fn ($query) => $query->whereIn('student_id', $studentIds))
            ->orderBy('student_id')->orderBy('id');
    }

    private function snapshotMatchesAudit(array $source, array $destination, ResultArchive $archive, PromotionAuditLog $audit): bool
    {
        return (string)$archive->old_session === (string)$audit->old_session
            && (string)$archive->old_class === (string)$audit->old_class
            && (string)($archive->old_section ?? '') === (string)($audit->old_section ?? '')
            && $this->normalizeRoll($archive->old_roll) === $this->normalizeRoll($audit->old_roll)
            && (string)($source['department_id'] ?? '') === (string)($audit->old_department ?? '')
            && (string)($destination['session_id'] ?? '') === (string)$audit->new_session
            && (string)($destination['class_id'] ?? '') === (string)$audit->new_class
            && (string)($destination['section_id'] ?? '') === (string)($audit->new_section ?? '')
            && (string)($destination['department_id'] ?? '') === (string)($audit->new_department ?? '')
            && $this->normalizeRoll($destination['roll'] ?? '') === $this->normalizeRoll($audit->new_roll);
    }

    private function studentMatchesDestination(newAdmission $student, PromotionAuditLog $audit): bool
    {
        return (string)$student->sessName === (string)$audit->new_session
            && (string)$student->className === (string)$audit->new_class
            && (string)($student->sectionName ?? '') === (string)($audit->new_section ?? '')
            && (string)($student->departmentName ?? '') === (string)($audit->new_department ?? '')
            && $this->normalizeRoll($student->getRawOriginal('rollNumber')) === $this->normalizeRoll($audit->new_roll);
    }

    private function sourceRollOccupied(PromotionAuditLog $audit, array $selectedIds): bool
    {
        return newAdmission::query()
            ->where('sessName', $audit->old_session)->where('className', $audit->old_class)
            ->when($audit->old_section === null, fn ($q) => $q->whereNull('sectionName'),
                fn ($q) => $q->where('sectionName', $audit->old_section))
            ->whereNotIn('id', $selectedIds)
            ->whereRaw('LOWER(TRIM(rollNumber)) = ?', [$this->normalizeRoll($audit->old_roll)])
            ->exists();
    }

    private function block(array &$report, string $code, string $message, int|string|null $studentId = null): void
    {
        $issue = array_filter(compact('code', 'message', 'studentId'), fn ($value) => $value !== null);
        if (!collect($report['blockingErrors'])->contains(fn ($existing) =>
            $existing['code'] === $code && ($existing['studentId'] ?? null) === $studentId
        )) $report['blockingErrors'][] = $issue;
    }

    private function normalizeRoll(mixed $roll): string
    {
        return strtolower(trim((string)$roll));
    }

    private function emptyReport(string $promotionCycleId, bool $dryRun): array
    {
        return [
            'promotionCycleId'=>$promotionCycleId,'revertCycleId'=>null,'dryRun'=>$dryRun,
            'studentsChecked'=>0,'wouldRestoreCount'=>0,'wouldMarkRevertedCount'=>0,
            'studentsRestored'=>0,'auditsMarkedReverted'=>0,'restorePayloads'=>[],
            'blockingErrors'=>[],'writeSafe'=>false,'transactionCommitted'=>false,
            'noRecordsModified'=>true,
        ];
    }

    private function logContext(array $report, string|int|null $actor): array
    {
        return [
            'engine'=>'centralized','promotion_cycle_id'=>$report['promotionCycleId'],
            'revert_cycle_id'=>$report['revertCycleId'],'students'=>$report['studentsChecked'],
            'actor'=>$actor,
        ];
    }
}
