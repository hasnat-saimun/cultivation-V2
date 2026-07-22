<?php

namespace App\Services;

use App\Models\Subject;
use App\Models\newAdmission;
use Illuminate\Database\Eloquent\Builder;

class FourthSubjectAssignmentResolver
{
    public function isFourthSubjectInContext(Subject $subject, array $academicContext = []): bool
    {
        if (strcasecmp((string) ($subject->subjectType ?? ''), 'Optional') !== 0) {
            return false;
        }

        $classId = (int) ($academicContext['class_id'] ?? 0);

        return $this->subjectMatchesClass($subject->assign_class ?? null, $classId);
    }

    public function assignedFourthSubjectIdForStudent(newAdmission $student): ?int
    {
        $value = $student->fourthSubjectId ?? null;
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

    public function applyStudentFourthSubjectFilter(Builder $query, Subject $subject, array $academicContext = []): Builder
    {
        if (!$this->isFourthSubjectInContext($subject, $academicContext)) {
            return $query;
        }

        return $query->where('fourthSubjectId', (int) $subject->id);
    }

    private function subjectMatchesClass(?string $assignClass, int $classId): bool
    {
        if ($classId <= 0) {
            return true;
        }

        $assignClass = trim((string) $assignClass);
        if ($assignClass === '' || $assignClass === '0') {
            return true;
        }

        if (ctype_digit($assignClass)) {
            return (int) $assignClass === $classId;
        }

        preg_match_all('/\d+/', $assignClass, $matches);
        $classIds = array_map('intval', $matches[0] ?? []);

        if (empty($classIds)) {
            return true;
        }

        return in_array($classId, $classIds, true);
    }
}
