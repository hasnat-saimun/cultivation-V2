<?php

namespace App\Services;

use App\Models\classManage;
use App\Models\Department;
use App\Models\sectionManage;
use App\Models\Subject;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TeacherAssignmentAcademicScopeService
{
    public const DEPARTMENT_ALL = 'all';
    public const DEPARTMENT_SPECIFIC = 'specific';
    public const DEPARTMENT_NOT_APPLICABLE = 'not_applicable';

    public function __construct(
        private DepartmentBasedClassDetector $departmentBasedClassDetector,
        private TeacherAssignmentSubjectCatalogService $subjectCatalog
    ) {}

    public function requiresGroup(classManage $class): bool
    {
        return $this->departmentBasedClassDetector->isDepartmentBasedClass((string) $class->className);
    }

    public function requiresGroupName(?string $className): bool
    {
        return $this->departmentBasedClassDetector->isDepartmentBasedClass($className);
    }

    /** @return array<int,bool> */
    public function groupRequirementMap(Collection $classes): array
    {
        return $classes->mapWithKeys(fn (classManage $class) => [
            (int) $class->id => $this->requiresGroup($class),
        ])->all();
    }

    public function assertValid(
        int $classId,
        ?int $sectionId,
        ?int $groupId,
        ?int $subjectId = null,
        string $groupField = 'optionalGroup',
        ?string $departmentScope = null,
        string $scopeField = 'departmentScope',
        ?int $sessionId = null
    ): void {
        $class = classManage::find($classId);
        if (!$class) {
            throw ValidationException::withMessages(['className' => ['The selected class is invalid.']]);
        }

        if ($sectionId !== null && !sectionManage::whereKey($sectionId)->exists()) {
            throw ValidationException::withMessages(['section' => ['The selected section is invalid.']]);
        }

        $requiresGroup = $this->requiresGroup($class);
        $departmentScope = strtolower(trim((string) $departmentScope));
        if ($requiresGroup) {
            if (!in_array($departmentScope, [self::DEPARTMENT_ALL, self::DEPARTMENT_SPECIFIC], true)) {
                throw ValidationException::withMessages([$scopeField => ['Select All Departments or a specific department/group.']]);
            }
            if ($departmentScope === self::DEPARTMENT_ALL && $groupId !== null) {
                throw ValidationException::withMessages([$groupField => ['All Departments cannot include a concrete department/group ID.']]);
            }
            if ($departmentScope === self::DEPARTMENT_SPECIFIC && $groupId === null) {
                throw ValidationException::withMessages([$groupField => ['Select a concrete department/group for the specific scope.']]);
            }
        } else {
            if ($departmentScope === '') {
                $departmentScope = self::DEPARTMENT_NOT_APPLICABLE;
            }
            if ($departmentScope !== self::DEPARTMENT_NOT_APPLICABLE || $groupId !== null) {
                throw ValidationException::withMessages([$scopeField => ['Department/group is not applicable to the selected class.']]);
            }
        }
        if ($groupId !== null && !Department::whereKey($groupId)->exists()) {
            throw ValidationException::withMessages([$groupField => ['The selected department/group is invalid.']]);
        }

        if ($subjectId !== null) {
            if (!$this->subjectCatalog->isAllowedSubjectInScope(
                $sessionId,
                $classId,
                $sectionId,
                $groupId,
                $subjectId
            )) {
                throw ValidationException::withMessages(['subject' => ['The selected subject is not valid for the selected class.']]);
            }
        }
    }

    public function isValidAuthorizationDepartment(int $classId, ?int $groupId): bool
    {
        $class = classManage::find($classId);
        if (!$class) {
            return false;
        }
        if (!$this->requiresGroup($class)) {
            return $groupId === null;
        }

        return $groupId === null || Department::whereKey($groupId)->exists();
    }
}
