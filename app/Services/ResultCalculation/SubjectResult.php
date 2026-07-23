<?php

namespace App\Services\ResultCalculation;

final class SubjectResult
{
    public function __construct(
        public readonly string $subjectId,
        public readonly string $subjectType,
        public readonly ?float $obtainedMarks,
        public readonly float $fullMarks,
        public readonly ?float $percentage,
        public readonly string $letterGrade,
        public readonly float $gradePoint,
        public readonly string $status,
        public readonly bool $isOptional,
        public readonly bool $isCompulsory,
        public readonly array $componentFailures = [],
        public readonly bool $missing = false,
        public readonly array $sourceSubjectIds = [],
    ) {}

    public function toArray(): array { return get_object_vars($this); }
}
