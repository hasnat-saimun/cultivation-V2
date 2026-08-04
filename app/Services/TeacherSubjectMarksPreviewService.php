<?php

namespace App\Services;

use App\Models\Marksheet;
use App\Models\Subject;
use App\Services\ResultCalculation\BoardResultCalculator;
use Illuminate\Support\Collection;

final class TeacherSubjectMarksPreviewService
{
    public function __construct(private BoardResultCalculator $calculator) {}

    /**
     * Build a data-entry preview for one paper only.
     *
     * This deliberately supplies only the selected subject to calculateSubject().
     * Paired-paper aggregation remains exclusive to the centralized report pipeline.
     */
    public function build(
        Collection $students,
        Collection $marks,
        object $exam,
        Subject $subject,
    ): Collection {
        return $students->mapWithKeys(function ($student) use ($marks, $exam, $subject) {
            /** @var Marksheet|null $mark */
            $mark = $marks->get((int) $student->id);
            $payload = $mark ?: [
                'id' => null,
                'subjectId' => $subject->id,
                'subjectMarks' => null,
                'objectMarks' => null,
                'practicalMarks' => null,
                'confirmed_blank_override' => false,
            ];

            return [(int) $student->id => $this->calculator->calculateSubject(
                $student,
                $exam,
                $payload,
                $subject,
            )];
        });
    }
}
