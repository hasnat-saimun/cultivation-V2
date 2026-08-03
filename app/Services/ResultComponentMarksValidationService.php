<?php

namespace App\Services;

use App\Exceptions\ResultLifecycleException;
use App\Models\Subject;

class ResultComponentMarksValidationService
{
    public const DECIMAL_PATTERN = '/^[0-9]+(?:\.[0-9]{1,2})?$/';

    public function maximums(?Subject $subject): array
    {
        $legacy = $subject && $subject->CQ === null && $subject->MCQ === null && $subject->Practical === null;

        return [
            'cqMarks' => $legacy ? 100.0 : (float) ($subject?->CQ ?? 0),
            'mcqMarks' => $legacy ? 100.0 : (float) ($subject?->MCQ ?? 0),
            'practical' => $legacy ? 100.0 : (float) ($subject?->Practical ?? 0),
        ];
    }

    public function componentRules(?Subject $subject): array
    {
        $maximums = $this->maximums($subject);

        return [
            'cqMarks.*' => $this->rulesForMaximum($maximums['cqMarks']),
            'mcqMarks.*' => $this->rulesForMaximum($maximums['mcqMarks']),
            'practical.*' => $this->rulesForMaximum($maximums['practical']),
        ];
    }

    public function normalize(mixed $value): ?float
    {
        if ($value === null || $value === '') return null;
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            throw ResultLifecycleException::invalid('InvalidMarksIdentity', $this->formatMessage());
        }

        $text = (string) $value;
        if (preg_match(self::DECIMAL_PATTERN, $text) !== 1) {
            throw ResultLifecycleException::invalid('InvalidMarksIdentity', $this->formatMessage());
        }

        return (float) $text;
    }

    public function assertWithinMaximums(array $raw, Subject $subject): void
    {
        $maximums = $this->maximums($subject);
        foreach ([
            'subjectMarks' => ['cqMarks', 'CQ'],
            'objectMarks' => ['mcqMarks', 'MCQ'],
            'practicalMarks' => ['practical', 'Practical'],
        ] as $field => [$maximumKey, $label]) {
            $value = $raw[$field];
            $maximum = $maximums[$maximumKey];
            if ($value !== null && ($maximum <= 0 || $value < 0 || $value > $maximum)) {
                throw ResultLifecycleException::invalid(
                    'InvalidMarksIdentity',
                    "{$label} marks must be between 0 and {$maximum} for this subject."
                );
            }
        }
    }

    private function rulesForMaximum(float $maximum): array
    {
        return ['bail', 'nullable', 'regex:'.self::DECIMAL_PATTERN, 'numeric', 'min:0', 'max:'.$maximum];
    }

    private function formatMessage(): string
    {
        return 'Marks must use English digits with no more than two decimal places.';
    }
}
