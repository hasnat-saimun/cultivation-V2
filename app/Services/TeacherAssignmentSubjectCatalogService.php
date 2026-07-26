<?php

namespace App\Services;

use App\Models\classManage as ClassModel;
use App\Models\Subject;
use Illuminate\Support\Collection;

class TeacherAssignmentSubjectCatalogService
{
    public function __construct(
        private CurriculumSubjectMappingService $curriculumMappings,
        private DepartmentBasedClassDetector $departmentBasedClassDetector,
    ) {}

    /**
     * Resolve assignable subject IDs for a teacher-assignment scope.
     *
     * Authoritative source is curriculum subject mappings. Legacy assign_class
     * fallback is used only when no mapping rows are available for the scope.
     *
     * @return array<int,int>
     */
    public function resolveAllowedSubjectIds(
        ?int $sessionId,
        int $classId,
        ?int $sectionId,
        ?int $groupId
    ): array {
        if ($classId <= 0) {
            return [];
        }

        $className = (string) (ClassModel::query()->whereKey($classId)->value('className') ?? '');
        $departmentBasedClass = $this->departmentBasedClassDetector->isDepartmentBasedClass($className);

        $scope = (object) [
            'sessName' => $sessionId,
            'className' => $classId,
            'sectionName' => $sectionId,
            'departmentName' => $groupId,
        ];

        $mappedMainIds = $this->curriculumMappings
            ->mappedMainSubjectsForStudent($scope, $departmentBasedClass)
            ->pluck('id')
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        // Optional and religious subjects are student-specific at runtime, but
        // assignable at admin level when class-compatible.
        $supplementalIds = $this->classCompatibleSupplementalSubjectIds($classId);

        if (!empty($mappedMainIds)) {
            return array_values(array_unique(array_merge($mappedMainIds, $supplementalIds)));
        }

        return $this->classCompatibleSubjectIds($classId);
    }

    public function isAllowedSubjectInScope(
        ?int $sessionId,
        int $classId,
        ?int $sectionId,
        ?int $groupId,
        int $subjectId
    ): bool {
        if ($subjectId <= 0) {
            return false;
        }

        $allowed = $this->resolveAllowedSubjectIds($sessionId, $classId, $sectionId, $groupId);

        return in_array($subjectId, $allowed, true);
    }

    /** @return array<int,int> */
    private function classCompatibleSubjectIds(int $classId): array
    {
        return Subject::query()
            ->select(['id', 'assign_class'])
            ->orderBy('id')
            ->get()
            ->filter(fn (Subject $subject) => $this->subjectMatchesClass($subject->assign_class ?? null, $classId))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /** @return array<int,int> */
    private function classCompatibleSupplementalSubjectIds(int $classId): array
    {
        return Subject::query()
            ->select(['id', 'assign_class', 'subjectType', 'isReligious'])
            ->orderBy('id')
            ->get()
            ->filter(function (Subject $subject) use ($classId): bool {
                if (!$this->subjectMatchesClass($subject->assign_class ?? null, $classId)) {
                    return false;
                }

                if ((bool) ($subject->isReligious ?? false)) {
                    return true;
                }

                return strcasecmp((string) ($subject->subjectType ?? ''), 'Optional') === 0;
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function subjectMatchesClass(?string $assignClass, int $classId): bool
    {
        $assignClass = trim((string) $assignClass);
        if ($assignClass === '' || $assignClass === '0') {
            return true;
        }
        if (ctype_digit($assignClass)) {
            return (int) $assignClass === $classId;
        }

        preg_match_all('/\d+/', $assignClass, $matches);
        $classIds = array_map('intval', $matches[0] ?? []);

        return empty($classIds) || in_array($classId, $classIds, true);
    }
}
