<?php

namespace App\Services;

use App\Exceptions\ResultLifecycleException;
use App\Models\Subject;

class ResultComponentMarksValidationService
{
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
            'cqMarks.*' => ['nullable', 'regex:/^[0-9]+$/', 'integer', 'min:0', 'max:'.$maximums['cqMarks']],
            'mcqMarks.*' => ['nullable', 'regex:/^[0-9]+$/', 'integer', 'min:0', 'max:'.$maximums['mcqMarks']],
            'practical.*' => ['nullable', 'regex:/^[0-9]+$/', 'integer', 'min:0', 'max:'.$maximums['practical']],
        ];
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
}
