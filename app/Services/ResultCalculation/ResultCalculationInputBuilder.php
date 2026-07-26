<?php

namespace App\Services\ResultCalculation;

use App\Models\newAdmission;
use App\Services\AcademicSubjectApplicabilityService;
use Illuminate\Support\Collection;

class ResultCalculationInputBuilder
{
    public function __construct(private AcademicSubjectApplicabilityService $applicability) {}
    /**
     * Load applicable subjects once for a set of students whose exam-scoped
     * marks have already been eager-loaded.
     *
     * @return array<int, Collection>
     */
    public function subjectsForStudents(iterable $students): array
    {
        return $this->applicability->subjectsForStudents($students);
    }

    public function subjectsForStudent(newAdmission $student): Collection
    {
        return $this->applicability->subjectsForStudent($student);
    }
}
