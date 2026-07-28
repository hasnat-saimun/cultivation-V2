<?php

namespace App\Services\ResultCalculation;

final class StudentResultClassificationService
{
    /**
     * Classify attendance/completeness independently from the academic Pass/Fail outcome.
     *
     * @return array{
     *   classification:string,
     *   hasMainMarkEvidence:bool,
     *   missingRequiredSubjectIds:array,
     *   ignoredOptionalMissingSubjectIds:array
     * }
     */
    public function classify(StudentResult $result, iterable $marks): array
    {
        $marksBySubject = collect($marks)->groupBy(fn ($mark) => (string) $this->value($mark, 'subjectId'));
        $required = collect($result->subjectResults)->filter(fn (SubjectResult $subject) => $subject->isCompulsory);
        $optional = collect($result->subjectResults)->filter(fn (SubjectResult $subject) => $subject->isOptional);

        $hasMainMarkEvidence = $required->contains(function (SubjectResult $subject) use ($marksBySubject) {
            return collect($subject->sourceSubjectIds)->contains(function ($subjectId) use ($marksBySubject) {
                return $marksBySubject->get((string) $subjectId, collect())->contains(
                    fn ($mark) => $this->numeric($this->value($mark, 'subjectMarks')) !== null
                        || $this->numeric($this->value($mark, 'objectMarks')) !== null
                        || $this->numeric($this->value($mark, 'practicalMarks')) !== null
                );
            });
        });

        $missingRequired = $required->filter(fn (SubjectResult $subject) => $subject->missing)
            ->pluck('subjectId')->values()->all();
        $ignoredOptional = $optional->filter(fn (SubjectResult $subject) => $subject->missing)
            ->pluck('subjectId')->values()->all();

        return [
            'classification' => !$hasMainMarkEvidence
                ? 'Absent'
                : ($missingRequired === [] ? 'Complete' : 'Incomplete'),
            'hasMainMarkEvidence' => $hasMainMarkEvidence,
            'missingRequiredSubjectIds' => $missingRequired,
            'ignoredOptionalMissingSubjectIds' => $ignoredOptional,
        ];
    }

    private function value(object|array|null $record, string $key): mixed
    {
        return $record === null ? null : (is_array($record) ? ($record[$key] ?? null) : ($record->{$key} ?? null));
    }

    private function numeric(mixed $value): ?float
    {
        return $value === null || $value === '' || !is_numeric($value) ? null : (float) $value;
    }
}
