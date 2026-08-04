<?php

namespace App\Services\ResultCalculation;

use App\Services\ResultPublicationVisibilityService;

final class PublishedResultFinalizer
{
    public function __construct(private ResultPublicationVisibilityService $visibility) {}

    public function finalizeForScope(
        StudentResult $result,
        int $examId,
        int $sessionId,
        int $classId,
        ?int $sectionId,
    ): StudentResult {
        if (!$this->visibility->isPublished($examId, $sessionId, $classId, $sectionId)) {
            return $result;
        }

        return $this->finalize($result);
    }

    /**
     * Final publication closes the Incomplete state without fabricating marks.
     * A missing compulsory subject is finalized as F/0 and joins the failed list.
     */
    public function finalize(StudentResult $result): StudentResult
    {
        if ($result->status !== 'Incomplete') {
            return $result;
        }

        $failed = $result->failedCompulsorySubjects;
        $subjects = array_map(function (SubjectResult $subject) use (&$failed) {
            if (!$subject->isCompulsory || !$subject->missing) {
                return $subject;
            }

            $failed[] = $subject->subjectId;

            return new SubjectResult(
                $subject->subjectId,
                $subject->subjectType,
                $subject->obtainedMarks,
                $subject->fullMarks,
                $subject->percentage,
                'F',
                0.0,
                'Fail',
                $subject->isOptional,
                $subject->isCompulsory,
                $subject->componentFailures,
                false,
                $subject->sourceSubjectIds,
                $subject->includedInResult,
            );
        }, $result->subjectResults);

        return new StudentResult(
            $subjects,
            $result->compulsoryGpSum,
            $result->compulsorySubjectCount,
            $result->optionalSubjectGp,
            $result->optionalBonus,
            array_values(array_unique($failed)),
            [],
            0.0,
            'Fail',
            $result->warnings,
            $result->calculationVersion,
        );
    }
}
