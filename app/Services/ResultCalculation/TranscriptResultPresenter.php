<?php

namespace App\Services\ResultCalculation;

use App\Models\GradeList;
use Illuminate\Support\Collection;

class TranscriptResultPresenter
{
    public function __construct(
        private ?TranscriptSubjectOrderingService $ordering = null,
        private ?StudentResultClassificationService $classifier = null,
    ) {}

    public function present(StudentResult $result, iterable $subjects, iterable $marks): array
    {
        return $this->presentPrepared($result, $subjects, $marks, null);
    }

    public function presentWithGradeRows(StudentResult $result, iterable $subjects, iterable $marks, iterable $gradeRows): array
    {
        return $this->presentPrepared($result, $subjects, $marks, $gradeRows);
    }

    private function presentPrepared(StudentResult $result, iterable $subjects, iterable $marks, ?iterable $gradeRows): array
    {
        $subjectsById = collect($subjects)->keyBy(fn ($subject) => (string) $subject->id);
        $marksBySubject = collect($marks)->groupBy(fn ($mark) => (string) $mark->subjectId);
        $mainRows = [];
        $optionalRows = [];
        $failedNames = [];
        $missingNames = [];
        $totalMarks = 0.0;

        foreach ($result->subjectResults as $subjectResult) {
            $row = $this->subjectRow($subjectResult, $subjectsById, $marksBySubject);
            $totalMarks += $subjectResult->obtainedMarks ?? 0.0;
            if ($subjectResult->status === 'Fail') $failedNames[] = $row['name'];
            if ($subjectResult->missing && $subjectResult->isCompulsory) $missingNames[] = $row['name'];
            if ($subjectResult->isOptional) $optionalRows[] = $row;
            else $mainRows[] = $row;
        }

        $mainRows = $this->ordering()->sortMainRows($mainRows);
        $optionalRows = $this->ordering()->sortOptionalRows($optionalRows);

        $classification = $this->classifier()->classify($result, $marks);
        $failedSubjects = array_values(array_unique($failedNames));
        $letter = match (true) {
            $classification['classification'] === 'Absent' => 'Absent',
            $result->status === 'Fail' => 'F',
            $result->status === 'Incomplete' => 'Incomplete',
            default => $this->letterForGpa((float) $result->gpa, $gradeRows),
        };

        return [
            'mainRows' => $mainRows,
            'optionalRows' => $optionalRows,
            'totalMarks' => round($totalMarks, 2),
            'gpa' => $result->status === 'Incomplete' ? null : $result->gpa,
            'gpaDisplay' => $classification['classification'] !== 'Complete'
                ? $classification['classification']
                : number_format((float) $result->gpa, 2),
            'letterGrade' => $letter,
            'status' => $result->status,
            'classification' => $classification['classification'],
            'hasMainMarkEvidence' => $classification['hasMainMarkEvidence'],
            'ignoredOptionalMissingSubjects' => $classification['ignoredOptionalMissingSubjectIds'],
            'optionalBonus' => $result->optionalBonus,
            'optionalBonusDisplay' => number_format($result->optionalBonus, 2),
            'failedSubjects' => $failedSubjects,
            'failedSubjectRows' => collect($failedSubjects)->chunk(3)
                ->map(fn (Collection $row) => $row->values()->all())
                ->values()
                ->all(),
            'failedSubjectCount' => count($failedSubjects),
            'missingSubjects' => array_values(array_unique($missingNames)),
            'missingSubjectCount' => count(array_unique($missingNames)),
            'isIncomplete' => $result->status === 'Incomplete',
            'componentFailures' => collect($result->subjectResults)
                ->filter(fn ($item) => $item->componentFailures !== [])
                ->mapWithKeys(fn ($item) => [$item->subjectId => $item->componentFailures])->all(),
            'warnings' => $result->warnings,
        ];
    }

    private function letterForGpa(float $gpa, ?iterable $gradeRows): string
    {
        if ($gradeRows === null) return GradeList::letterForGpa($gpa) ?? '-';
        $candidate = null;
        foreach ($gradeRows as $row) {
            $min = is_numeric($row->minGp) ? (float) $row->minGp : null;
            $max = is_numeric($row->maxGp) ? (float) $row->maxGp : null;
            if ($min !== null && $max !== null && $gpa >= $min && $gpa <= $max) return (string) $row->gradeName;
            if (is_numeric($row->gradePoint) && (float) $row->gradePoint <= $gpa
                && ($candidate === null || (float) $candidate->gradePoint < (float) $row->gradePoint)) {
                $candidate = $row;
            }
        }
        if ($candidate !== null) return (string) $candidate->gradeName;
        if ($gpa >= 5.0) return 'A+';
        if ($gpa >= 4.0) return 'A';
        if ($gpa >= 3.5) return 'A-';
        if ($gpa >= 3.0) return 'B';
        if ($gpa >= 2.0) return 'C';
        if ($gpa >= 1.0) return 'D';
        return 'F';
    }

    private function subjectRow(SubjectResult $result, Collection $subjectsById, Collection $marksBySubject): array
    {
        $sourceSubjects = collect($result->sourceSubjectIds)->map(fn ($id) => $subjectsById->get((string) $id))->filter()->values();
        $sourceMarks = collect($result->sourceSubjectIds)->map(function ($id) use ($marksBySubject) {
            return $marksBySubject->get((string) $id, collect())->sortBy('id')->last();
        });
        $paired = $sourceSubjects->count() > 1;

        return [
            'id' => $result->subjectId,
            'cellKey' => $result->subjectId,
            'sourceIds' => $result->sourceSubjectIds,
            'type' => $result->subjectType,
            'isOptional' => $result->isOptional,
            'isReligious' => $sourceSubjects->contains(fn ($subject) => (bool) ($subject->isReligious ?? false)),
            'paired' => $paired,
            'name' => $this->displayName($result, $sourceSubjects),
            'cq' => $this->componentDisplay($sourceSubjects, $sourceMarks, 'CQ', 'subjectMarks', $paired),
            'mcq' => $this->componentDisplay($sourceSubjects, $sourceMarks, 'MCQ', 'objectMarks', $paired),
            'practical' => $this->componentDisplay($sourceSubjects, $sourceMarks, 'Practical', 'practicalMarks', $paired),
            'total' => $result->obtainedMarks ?? '-',
            'grade' => $result->letterGrade,
            'gradePoint' => $result->missing ? '-' : number_format($result->gradePoint, 2),
            'status' => $result->status,
            'componentFailures' => $result->componentFailures,
            'mappingSortOrder' => (int) ($sourceSubjects->min('mapping_sort_order') ?? $sourceSubjects->min('applicability_order') ?? PHP_INT_MAX),
            'mappingDepartmentIds' => $sourceSubjects
                ->map(fn ($subject) => is_numeric($subject->mapping_department_id ?? null) ? (int) $subject->mapping_department_id : null)
                ->filter(fn ($id) => $id !== null)
                ->unique()
                ->values()
                ->all(),
            'applicabilitySources' => $sourceSubjects
                ->map(fn ($subject) => (string) ($subject->applicability_source ?? ''))
                ->filter()
                ->unique()
                ->values()
                ->all(),
            'mappingCategories' => $sourceSubjects
                ->map(fn ($subject) => (string) ($subject->mapping_category ?? ''))
                ->filter()
                ->unique()
                ->values()
                ->all(),
            'sortOrder' => (int) ($sourceSubjects->min('applicability_order') ?? PHP_INT_MAX),
        ];
    }

    private function componentDisplay(Collection $subjects, Collection $marks, string $fullField, string $markField, bool $paired): string|float
    {
        $values = [];
        $effectiveValues = [];
        foreach ($subjects as $index => $subject) {
            if ((float) ($subject->{$fullField} ?? 0) <= 0) continue;
            $mark = $marks->get($index);
            $resolved = EffectiveComponentMarkResolver::resolve(
                $mark?->{$markField},
                true,
                (bool) ($mark?->confirmed_blank_override ?? false),
            );
            $values[] = $resolved;
            if ($resolved !== null) {
                $effectiveValues[] = $resolved;
            }
        }

        if ($effectiveValues === []) {
            return '-';
        }

        if (!$paired || count($effectiveValues) === 1) {
            return $effectiveValues[0];
        }

        return '('.implode(' + ', $effectiveValues).') = '.array_sum($effectiveValues);
    }

    private function displayName(SubjectResult $result, Collection $subjects): string
    {
        if (str_starts_with($result->subjectId, 'pair:')) {
            $key = strtolower(substr($result->subjectId, 5));
            return config("subject_pairs.displayNames.{$key}")
                ?? preg_replace('/\s*(1st|2nd|First|Second)\s*Paper$/i', '', (string) ($subjects->first()?->subjectName ?? $result->subjectId));
        }
        return (string) ($subjects->first()?->subjectName ?? $result->subjectId);
    }

    private function ordering(): TranscriptSubjectOrderingService
    {
        if ($this->ordering === null) {
            $this->ordering = app(TranscriptSubjectOrderingService::class);
        }

        return $this->ordering;
    }

    private function classifier(): StudentResultClassificationService
    {
        if ($this->classifier === null) {
            $this->classifier = app(StudentResultClassificationService::class);
        }

        return $this->classifier;
    }

}
