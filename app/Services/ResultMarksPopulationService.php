<?php

namespace App\Services;

use App\Exceptions\ResultLifecycleException;
use App\Models\CultivationAdmin;
use App\Models\Subject;
use App\Models\newAdmission;
use App\Models\sessionManage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ResultMarksPopulationService
{
    public function __construct(
        private MarksEntryAuthorizationService $authorization,
        private ReligiousSubjectAssignmentResolver $religiousSubjects,
        private FourthSubjectAssignmentResolver $fourthSubjects,
    ) {}

    public function resolve(
        array $scope,
        Subject $subject,
        ?CultivationAdmin $actor,
        ?int $departmentId = null,
        string $gender = 'all',
        bool $requireCompleteTeacherCoverage = false,
    ): Collection {
        $sessionLabel = sessionManage::whereKey($scope['sessionId'])->value('session');
        if ($sessionLabel === null) throw ResultLifecycleException::missing('The selected session was not found.');

        $base = newAdmission::query()
            ->where('className', (string) $scope['classId'])
            ->when($scope['groupId'] !== null, fn ($q) => $q->where('sectionName', (string) $scope['groupId']))
            ->when($departmentId !== null, fn ($q) => $q->where('departmentName', (string) $departmentId))
            ->where(function ($q) use ($scope, $sessionLabel) {
                $q->where('sessName', (string) $scope['sessionId'])
                    ->orWhere('sessName', (string) $sessionLabel);
            });

        $academic = [
            'class_id' => (int) $scope['classId'],
            'section_id' => $scope['groupId'],
            'department_id' => $departmentId,
            'session_id' => (int) $scope['sessionId'],
        ];
        $this->religiousSubjects->applyStudentReligiousSubjectFilter($base, $subject);
        $this->fourthSubjects->applyStudentFourthSubjectFilter($base, $subject, $academic);

        $complete = $this->applyStudentOrder(clone $base)->get();
        if (!$actor || !$actor->isTeacher()) return $this->genderFilter($complete, $gender);

        $authorized = clone $base;
        if (!$this->authorization->applyTeacherStudentAuthorizationFilters(
            $authorized,
            $actor,
            (int) $scope['classId'],
            $scope['groupId'],
            $departmentId,
            (int) $scope['subjectId'],
            $gender,
            (int) $scope['sessionId'],
        )) {
            throw ResultLifecycleException::forbidden();
        }

        $authorized = $this->applyStudentOrder($authorized)->get();
        if ($requireCompleteTeacherCoverage) {
            $completeIds = $complete->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
            $authorizedIds = $authorized->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
            if ($completeIds->all() !== $authorizedIds->all()) {
                throw ResultLifecycleException::forbidden(
                    'Teacher assignment does not prove complete coverage of this subject scope.'
                );
            }
        }
        return $authorized;
    }

    private function genderFilter(Collection $students, string $gender): Collection
    {
        if ($gender === 'all') return $students;
        return $students->filter(fn ($student) => (string) $student->gender === $gender)->values();
    }

    private function applyStudentOrder(Builder $query): Builder
    {
        return $query
            ->orderByRaw('CAST(NULLIF(gender, "") AS UNSIGNED) ASC')
            ->orderByRaw('CAST(NULLIF(rollNumber, "") AS UNSIGNED) ASC')
            ->orderBy('id');
    }
}
