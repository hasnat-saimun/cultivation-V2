<?php

namespace App\Services\ResultCalculation;

final class StudentResult
{
    /** @param SubjectResult[] $subjectResults */
    public function __construct(
        public readonly array $subjectResults,
        public readonly float $compulsoryGpSum,
        public readonly int $compulsorySubjectCount,
        public readonly ?float $optionalSubjectGp,
        public readonly float $optionalBonus,
        public readonly array $failedCompulsorySubjects,
        public readonly array $missingCompulsorySubjects,
        public readonly ?float $gpa,
        public readonly string $status,
        public readonly array $warnings = [],
        public readonly string $calculationVersion = 'board-v1',
    ) {}

    public function toArray(): array
    {
        return [
            'subjectResults' => array_map(fn (SubjectResult $r) => $r->toArray(), $this->subjectResults),
            'compulsoryGpSum' => $this->compulsoryGpSum,
            'compulsorySubjectCount' => $this->compulsorySubjectCount,
            'optionalSubjectGp' => $this->optionalSubjectGp,
            'optionalBonus' => $this->optionalBonus,
            'failedCompulsorySubjects' => $this->failedCompulsorySubjects,
            'missingCompulsorySubjects' => $this->missingCompulsorySubjects,
            'gpa' => $this->gpa,
            'status' => $this->status,
            'warnings' => $this->warnings,
            'calculationVersion' => $this->calculationVersion,
        ];
    }
}
