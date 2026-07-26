<?php

namespace App\Services;

use App\Models\newAdmission;
use App\Models\ReligiousSubjectDefault;
use App\Models\Subject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AcademicSubjectApplicabilityService
{
    public function __construct(
        private DepartmentBasedClassDetector $departmentBasedClasses,
        private CurriculumSubjectMappingService $curriculumMappings,
    ) {}

    private const SOURCE_CURRICULUM_MAPPING_MAIN = 'curriculum subject mapping';
    private const SOURCE_RELIGION_MAPPING = 'religion mapping';
    private const SOURCE_OPTIONAL_SUBJECT = 'optional subject';

    /** @return array<int,Collection> */
    public function subjectsForStudents(iterable $students): array
    {
        $students = collect($students)->values();
        if ($students->isEmpty()) {
            return [];
        }

        $classIds = $students
            ->pluck('className')
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $classNames = \App\Models\classManage::query()
            ->whereIn('id', $classIds)
            ->pluck('className', 'id');

        $religiousDefaults = ReligiousSubjectDefault::query()
            ->whereIn('classId', $classIds)
            ->pluck('subjectId', 'classId');

        $mainIdsByStudent = [];
        $groupEnabledByStudent = [];
        $allSubjectIds = [];
        $scopeMappingCache = [];

        foreach ($students as $student) {
            $studentId = (int) ($student->id ?? 0);
            $classId = (int) ($student->className ?? 0);
            $sessionId = $this->nullableId($student->sessName ?? null);
            $sectionId = $this->nullableId($student->sectionName ?? null);
            $departmentId = $this->nullableId($student->departmentName ?? null);
            $groupEnabled = $this->departmentBasedClasses->isDepartmentBasedClass(
                (string) ($classNames[$classId] ?? '')
            );
            $groupEnabledByStudent[$studentId] = $groupEnabled;

            $scopeKey = implode('|', [
                (string) ($sessionId ?? 0),
                (string) $classId,
                (string) ($sectionId ?? 0),
                (string) ($departmentId ?? 0),
                $groupEnabled ? '1' : '0',
            ]);

            if (!array_key_exists($scopeKey, $scopeMappingCache)) {
                $scopeMappingCache[$scopeKey] = $this->curriculumMappings
                    ->mappedMainSubjectsForStudent($student, $groupEnabled);
            }

            $mainSubjects = $scopeMappingCache[$scopeKey];
            $mainIds = $mainSubjects
                ->pluck('id')
                ->filter(fn ($id) => is_numeric($id))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $mainIdsByStudent[$studentId] = $mainIds;
            foreach ($mainIds as $subjectId) {
                $allSubjectIds[] = (int) $subjectId;
            }

            $religionCandidate = (int) ($student->religiousSubjectId ?? 0)
                ?: (int) ($religiousDefaults[$classId] ?? 0);
            if ($religionCandidate > 0) {
                $allSubjectIds[] = $religionCandidate;
            }

            $fourthCandidate = (int) ($student->fourthSubjectId ?? 0);
            if ($fourthCandidate > 0) {
                $allSubjectIds[] = $fourthCandidate;
            }
        }

        $subjects = Subject::query()
            ->whereIn('id', array_values(array_unique($allSubjectIds)))
            ->get()
            ->keyBy(fn ($subject) => (int) $subject->id);

        $result = [];
        foreach ($students as $student) {
            $studentId = (int) ($student->id ?? 0);
            $classId = (int) ($student->className ?? 0);
            $mainIds = collect($mainIdsByStudent[$studentId] ?? [])->values();
            $groupEnabled = (bool) ($groupEnabledByStudent[$studentId] ?? false);
            $sessionId = $this->nullableId($student->sessName ?? null);
            $sectionId = $this->nullableId($student->sectionName ?? null);
            $departmentId = $this->nullableId($student->departmentName ?? null);
            $scopeKey = implode('|', [
                (string) ($sessionId ?? 0),
                (string) $classId,
                (string) ($sectionId ?? 0),
                (string) ($departmentId ?? 0),
                $groupEnabled ? '1' : '0',
            ]);
            $mainSubjects = $scopeMappingCache[$scopeKey] ?? collect();

            if ($groupEnabled && $departmentId === null) {
                $this->gap('GROUP_ENABLED_STUDENT_WITHOUT_DEPARTMENT', $student);
            }

            $order = [];
            $sources = [];
            $mappingSortOrders = [];
            $mappingDepartmentIds = [];
            $mappingCategories = [];
            foreach ($mainSubjects->values() as $index => $subject) {
                $subjectId = (int) ($subject->id ?? 0);
                if ($subjectId <= 0) {
                    continue;
                }

                $resolvedSortOrder = is_numeric($subject->applicability_order ?? null)
                    ? (int) $subject->applicability_order
                    : ($index + 1);

                $order[$subjectId] = $resolvedSortOrder;
                $sources[$subjectId] = self::SOURCE_CURRICULUM_MAPPING_MAIN;
                $mappingSortOrders[$subjectId] = is_numeric($subject->mapping_sort_order ?? null)
                    ? (int) $subject->mapping_sort_order
                    : $resolvedSortOrder;
                $mappingDepartmentIds[$subjectId] = is_numeric($subject->mapping_department_id ?? null)
                    ? (int) $subject->mapping_department_id
                    : null;

                if ($mappingDepartmentIds[$subjectId] === null) {
                    $mappingCategories[$subjectId] = 'common';
                } elseif ($departmentId !== null && $mappingDepartmentIds[$subjectId] === $departmentId) {
                    $mappingCategories[$subjectId] = 'department_group';
                } else {
                    $mappingCategories[$subjectId] = 'other_department';
                }
            }

            if ($mainIds->isEmpty()) {
                $this->gap('CURRICULUM_MAIN_SUBJECTS_NOT_CONFIGURED', $student);
            }

            $religiousId = (int) ($student->religiousSubjectId ?? 0)
                ?: (int) ($religiousDefaults[$classId] ?? 0);
            if ($religiousId > 0 && isset($subjects[$religiousId])
                && (bool) ($subjects[$religiousId]->isReligious ?? false)) {
                $mainIds->push($religiousId);
                $order[$religiousId] ??= 800000;
                $sources[$religiousId] = self::SOURCE_RELIGION_MAPPING;
            }

            $fourthId = (int) ($student->fourthSubjectId ?? 0);
            if ($fourthId > 0) {
                if (isset($subjects[$fourthId])
                    && strcasecmp((string) $subjects[$fourthId]->subjectType, 'Optional') === 0) {
                    $mainIds->push($fourthId);
                    $order[$fourthId] ??= 900000;
                    $sources[$fourthId] = self::SOURCE_OPTIONAL_SUBJECT;
                } else {
                    $this->gap('INVALID_FOURTH_SUBJECT', $student, $fourthId);
                }
            }

            if (method_exists($student, 'setAttribute')) {
                $student->setAttribute('curriculum_main_subjects_configured', !$mainIdsByStudent[$studentId]->isEmpty());
            }

            $result[$studentId] = $mainIds
                ->unique()
                ->map(fn ($id) => $subjects->get((int) $id))
                ->filter()
                ->reject(function ($subject) use ($religiousId, $fourthId) {
                    if ((bool) ($subject->isReligious ?? false)) {
                        return (int) $subject->id !== $religiousId;
                    }

                    if (strcasecmp((string) $subject->subjectType, 'Optional') === 0) {
                        return (int) $subject->id !== $fourthId;
                    }

                    return false;
                })
                ->each(function ($subject) use ($order, $sources, $mappingSortOrders, $mappingDepartmentIds, $mappingCategories) {
                    $subjectId = (int) $subject->id;
                    $subject->setAttribute('applicability_order', $order[$subjectId] ?? (100000 + $subjectId));
                    $subject->setAttribute('applicability_source', $sources[$subjectId] ?? self::SOURCE_CURRICULUM_MAPPING_MAIN);
                    $subject->setAttribute('mapping_sort_order', $mappingSortOrders[$subjectId] ?? ($order[$subjectId] ?? (100000 + $subjectId)));
                    $subject->setAttribute('mapping_department_id', $mappingDepartmentIds[$subjectId] ?? null);
                    $subject->setAttribute('mapping_category', $mappingCategories[$subjectId] ?? null);
                })
                ->sortBy('applicability_order')
                ->values();
        }

        return $result;
    }

    public function subjectsForStudent(newAdmission $student): Collection
    {
        return $this->subjectsForStudents([$student])[(int) $student->id] ?? collect();
    }

    private function nullableId($value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function gap(string $reason, object $student, ?int $subjectId = null): void
    {
        Log::warning('Academic subject applicability configuration gap.', [
            'reason' => $reason, 'student_id' => (int) ($student->id ?? 0),
            'session_id' => $student->sessName ?? null, 'class_id' => $student->className ?? null,
            'section_id' => $student->sectionName ?? null, 'department_id' => $student->departmentName ?? null,
            'subject_id' => $subjectId,
        ]);
    }
}
