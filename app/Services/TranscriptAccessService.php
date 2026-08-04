<?php

namespace App\Services;

use App\Models\CultivationAdmin;
use App\Models\Exam;
use App\Models\newAdmission;

final class TranscriptAccessService
{
    public function __construct(private MarksEntryAuthorizationService $marksAuthorization) {}

    public function authorize(?CultivationAdmin $actor, newAdmission $student, Exam $exam): void
    {
        if (! $actor) {
            abort(403, 'Unauthorized transcript access.');
        }

        if ($actor->isCash() || (! $actor->isTeacher() && (int) $actor->userType < CultivationAdmin::ROLE_GENERAL)) {
            abort(403, 'Unauthorized transcript access.');
        }

        $classId = $this->positiveInteger($student->className);
        $sessionId = $this->positiveInteger($student->sessName);
        $sectionId = $this->nullablePositiveInteger($student->sectionName);
        $departmentId = $this->nullablePositiveInteger($student->departmentName);

        if ($classId === null || $sessionId === null) {
            abort(404, 'The student academic scope was not found.');
        }

        $examClassValue = trim((string) ($exam->className ?? ''));
        $examClassId = $this->nullablePositiveInteger($examClassValue);
        if ($examClassValue !== '' && $examClassValue !== '0' && $examClassId !== $classId) {
            abort(404, 'The selected exam does not belong to the student class.');
        }

        if (! $actor->isTeacher()) {
            return;
        }

        $query = newAdmission::query()
            ->whereKey((int) $student->id)
            ->where('sessName', $sessionId)
            ->where('className', $classId)
            ->when($sectionId !== null, fn ($builder) => $builder->where('sectionName', $sectionId))
            ->when($departmentId !== null, fn ($builder) => $builder->where('departmentName', $departmentId));

        $authorized = $this->marksAuthorization->applyTeacherStudentAuthorizationFilters(
            $query,
            $actor,
            $classId,
            $sectionId,
            $departmentId,
            null,
            (string) ($student->gender ?? 'all'),
            $sessionId,
        );

        if (! $authorized || ! $query->exists()) {
            abort(404, 'The student is outside the assigned teacher scope.');
        }
    }

    private function positiveInteger(mixed $value): ?int
    {
        $resolved = $this->nullablePositiveInteger($value);

        return $resolved !== null && $resolved > 0 ? $resolved : null;
    }

    private function nullablePositiveInteger(mixed $value): ?int
    {
        if ($value === null || $value === '' || ! ctype_digit((string) $value)) {
            return null;
        }

        $resolved = (int) $value;

        return $resolved > 0 ? $resolved : null;
    }
}
