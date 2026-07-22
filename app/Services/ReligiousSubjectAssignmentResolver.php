<?php

namespace App\Services;

use App\Models\Subject;
use App\Models\newAdmission;
use Illuminate\Database\Eloquent\Builder;

class ReligiousSubjectAssignmentResolver
{
    public function isReligiousSubject(Subject $subject): bool
    {
        return !empty($subject->isReligious);
    }

    public function assignedReligiousSubjectIdForStudent(newAdmission $student): ?int
    {
        $value = $student->religiousSubjectId ?? null;
        if ($value === null) {
            return null;
        }

        if (is_string($value) && trim($value) === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $subjectId = (int) $value;

        return $subjectId > 0 ? $subjectId : null;
    }

    public function applyStudentReligiousSubjectFilter(Builder $query, Subject $subject): Builder
    {
        if (!$this->isReligiousSubject($subject)) {
            return $query;
        }

        return $query->where('religiousSubjectId', (int) $subject->id);
    }
}
