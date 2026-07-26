<?php

namespace App\Services\ResultCalculation;

use App\Models\GradeList;
use Illuminate\Support\Collection;

class TranscriptResultPresenter
{
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
            if ($subjectResult->missing) $missingNames[] = $row['name'];
            if ($subjectResult->isOptional) $optionalRows[] = $row;
            else $mainRows[] = $row;
        }

        usort($mainRows, fn ($a, $b) => $this->order($a['name']) <=> $this->order($b['name'])
            ?: strcasecmp($a['name'], $b['name']));

        $letter = match ($result->status) {
            'Fail' => 'F',
            'Incomplete' => 'Incomplete',
            default => $this->letterForGpa((float) $result->gpa, $gradeRows),
        };

        return [
            'mainRows' => $mainRows,
            'optionalRows' => $optionalRows,
            'totalMarks' => round($totalMarks, 2),
            'gpa' => $result->status === 'Incomplete' ? null : $result->gpa,
            'gpaDisplay' => $result->status === 'Incomplete'
                ? 'Incomplete'
                : number_format((float) $result->gpa, 2),
            'letterGrade' => $letter,
            'status' => $result->status,
            'optionalBonus' => $result->optionalBonus,
            'optionalBonusDisplay' => number_format($result->optionalBonus, 2),
            'failedSubjects' => array_values(array_unique($failedNames)),
            'failedSubjectCount' => count(array_unique($failedNames)),
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
            'sourceIds' => $result->sourceSubjectIds,
            'type' => $result->subjectType,
            'isOptional' => $result->isOptional,
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
        ];
    }

    private function componentDisplay(Collection $subjects, Collection $marks, string $fullField, string $markField, bool $paired): string|float
    {
        $values = [];
        foreach ($subjects as $index => $subject) {
            if ((float) ($subject->{$fullField} ?? 0) <= 0) continue;
            $value = $marks->get($index)?->{$markField};
            $values[] = is_numeric($value) ? (float) $value : null;
        }
        if ($values === [] || in_array(null, $values, true)) return '-';
        if (!$paired) return $values[0];
        return '('.implode(' + ', $values).') = '.array_sum($values);
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

    private function order(string $name): int
    {
        $name = strtolower($name);
        if ((str_contains($name, 'information') && str_contains($name, 'communication')) || str_contains($name, 'ict')) return 900;
        if (str_contains($name, 'bangla') && !str_contains($name, 'bangladesh')) return 100;
        if (str_contains($name, 'english')) return 200;
        if (str_contains($name, 'mathematics') || preg_match('/\bmath\b/', $name)) return 300;
        if (str_contains($name, 'general science')) return 400;
        if (str_contains($name, 'social science') || str_contains($name, 'bangladesh') || str_contains($name, 'bgs')) return 500;
        if (preg_match('/religion|islam|hindu|buddh|christ/', $name)) return 600;
        return 700;
    }
}
