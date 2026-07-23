<?php

namespace App\Services\ResultCalculation;

use App\Models\Placement;

class PlacementPreviewBuilder
{
    public function __construct(private ResultCalculationBatchBuilder $batchBuilder) {}

    public function build(int $examId, int $classId, int $sessionId, ?int $sectionId = null, ?int $departmentId = null, ?int $studentId = null, int $limit = 100): array
    {
        $batch = $this->batchBuilder->build($examId, $classId, $sessionId, $sectionId, $departmentId);
        $entries = collect($batch['entries']);
        if ($studentId) $entries = $entries->only([$studentId]);
        $entries = $entries->take(max(1, min(10000, $limit)));

        $placementQuery = Placement::query()->where('examId', (string) $examId)->where('classId', (string) $classId)->where('sessionId', (string) $sessionId);
        if ($sectionId !== null) $placementQuery->where('groupId', (string) $sectionId);
        if ($departmentId !== null) $placementQuery->whereIn('studentId', $batch['students']->where('departmentName', $departmentId)->pluck('id')->map(fn ($id) => (string) $id));
        $existingPlacements = $placementQuery->orderBy('id')->get();
        $placementsByStudent = $existingPlacements->groupBy(fn ($row) => (int) $row->studentId);

        $rows = [];
        foreach ($entries as $studentIdKey => $entry) {
            $student = $entry['student']; $result = $entry['result']; $marks = $student->marksheet;
            $legacyCount = $marks->count();
            $legacyGpSum = $marks->sum(fn ($mark) => (float) ($mark->gradePoint ?? 0));
            $legacyGpa = $legacyCount ? round($legacyGpSum / $legacyCount, 2) : null;
            $legacyStatus = $legacyCount === 0 ? 'NotPlaced' : ($marks->contains(fn ($mark) => (float) ($mark->gradePoint ?? 0) <= 0) ? 'Fail' : 'Pass');
            $legacyTotal = (float) $marks->sum(fn ($mark) => (float) ($mark->totalMarks ?? 0));
            $compulsoryTotal = (float) collect($result->subjectResults)->where('isCompulsory', true)->sum(fn ($subject) => (float) ($subject->obtainedMarks ?? 0));
            $existing = $placementsByStudent->get((int) $studentIdKey, collect())->first();
            $reasons = $this->reasons($legacyGpa, $legacyStatus, $legacyCount, $result, $marks);
            $rows[] = [
                'studentId' => (int) $student->id, 'roll' => $student->rollNumber,
                'existingPlacementId' => $existing?->id, 'existingRank' => $existing?->position,
                'legacyGpa' => $legacyGpa, 'centralizedGpa' => $result->gpa,
                'legacyStatus' => $legacyStatus, 'centralizedStatus' => $result->status,
                'legacySubjectCount' => $legacyCount, 'centralizedCompulsorySubjectCount' => $result->compulsorySubjectCount,
                'legacyTotalMarks' => $legacyTotal, 'compulsoryOnlyTotal' => $compulsoryTotal,
                'proposedRankingTotal' => $legacyTotal, 'optionalBonus' => $result->optionalBonus,
                'failedCompulsoryCount' => count($result->failedCompulsorySubjects),
                'missingCompulsoryCount' => count($result->missingCompulsorySubjects),
                'previewRank' => null, 'rankChanged' => false,
                'gpaChanged' => $legacyGpa !== $result->gpa,
                'statusChanged' => $legacyStatus !== $result->status,
                'eligible' => $result->status === 'Pass', 'reasons' => $reasons,
                'warnings' => $result->warnings,
                '_result' => $result,
            ];
        }

        $eligibleIndexes = array_keys(array_filter($rows, fn ($row) => $row['eligible']));
        usort($eligibleIndexes, function ($a, $b) use ($rows) {
            $left = $rows[$a]; $right = $rows[$b];
            if ($left['centralizedGpa'] !== $right['centralizedGpa']) return $right['centralizedGpa'] <=> $left['centralizedGpa'];
            if ($left['proposedRankingTotal'] !== $right['proposedRankingTotal']) return $right['proposedRankingTotal'] <=> $left['proposedRankingTotal'];
            return $this->rollValue($left['roll']) <=> $this->rollValue($right['roll']);
        });
        foreach ($eligibleIndexes as $offset => $index) $rows[$index]['previewRank'] = $offset + 1;
        foreach ($rows as &$row) $row['rankChanged'] = $row['existingRank'] !== $row['previewRank'];
        unset($row);

        $duplicatePlacements = $placementsByStudent->filter(fn ($items) => $items->count() > 1)->sum(fn ($items) => $items->count() - 1);
        $scopedStudentIds = $batch['students']->pluck('id')->map(fn ($id) => (int) $id);
        $orphanPlacements = $existingPlacements->reject(fn ($placement) => $scopedStudentIds->contains((int) $placement->studentId))->count();
        return [
            'rows' => $rows,
            'summary' => [
                'studentsChecked' => count($rows), 'existingPlacementsFound' => $existingPlacements->count(),
                'gpaDifferences' => count(array_filter($rows, fn ($row) => $row['gpaChanged'])),
                'statusDifferences' => count(array_filter($rows, fn ($row) => $row['statusChanged'])),
                'rankDifferences' => count(array_filter($rows, fn ($row) => $row['rankChanged'])),
                'newlyIneligible' => count(array_filter($rows, fn ($row) => $row['legacyStatus'] === 'Pass' && !$row['eligible'])),
                'newlyEligible' => count(array_filter($rows, fn ($row) => $row['legacyStatus'] !== 'Pass' && $row['eligible'])),
                'incompleteStudents' => count(array_filter($rows, fn ($row) => $row['centralizedStatus'] === 'Incomplete')),
                'duplicatePlacementRows' => $duplicatePlacements, 'orphanPlacementRows' => $orphanPlacements,
                'calculationErrors' => 0,
            ],
        ];
    }

    private function reasons(?float $legacyGpa, string $legacyStatus, int $legacyCount, StudentResult $result, $marks): array
    {
        $reasons = [];
        if ($legacyGpa !== null && $legacyGpa > 5) $reasons[] = 'GPA_CAPPED_AT_5';
        if ($legacyCount !== $result->compulsorySubjectCount) $reasons[] = 'OPTIONAL_OR_DUPLICATE_REMOVED_FROM_DENOMINATOR';
        if ($result->optionalBonus > 0) $reasons[] = 'OPTIONAL_BONUS_APPLIED';
        if ($legacyStatus === 'Fail' && $result->status === 'Pass') $reasons[] = 'OPTIONAL_F_NO_LONGER_FAILS';
        if ($result->missingCompulsorySubjects !== []) $reasons[] = 'MISSING_COMPULSORY_INCOMPLETE';
        if (collect($result->subjectResults)->contains(fn ($subject) => strcasecmp($subject->subjectType, 'Theory') === 0)) $reasons[] = 'COMPULSORY_THEORY_INCLUDED';
        if (collect($result->subjectResults)->contains(fn ($subject) => str_starts_with($subject->subjectId, 'pair:'))) $reasons[] = 'CONFIGURED_SUBJECT_PAIR';
        if (collect($result->subjectResults)->contains(fn ($subject) => $subject->componentFailures !== [])) $reasons[] = 'COMPONENT_FAILURE_REEVALUATED';
        if ($result->warnings !== []) $reasons[] = 'DATA_QUALITY_WARNING';
        if ($marks->groupBy('subjectId')->contains(fn ($rows) => $rows->count() > 1)) $reasons[] = 'DUPLICATE_MARKS_ROWS';
        return array_values(array_unique($reasons));
    }

    private function rollValue(mixed $roll): int { return is_numeric($roll) ? (int) $roll : PHP_INT_MAX; }
}
