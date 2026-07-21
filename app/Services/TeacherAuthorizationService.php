<?php

namespace App\Services;

use App\Models\CultivationAdmin;

class TeacherAuthorizationService
{
    public function resolveCurrentCultivationAdmin(): ?CultivationAdmin
    {
        return app(CultivationAdminResolver::class)->current();
    }

    public function classTeacherAssignment(?CultivationAdmin $user): ?array
    {
        if (!$user || !$user->isTeacher()) {
            return null;
        }

        $classId = (int) ($user->primary_class_id ?? 0);
        if ($classId <= 0) {
            return null;
        }

        $sectionId = $user->primary_section_id;
        $sectionId = ($sectionId === null || $sectionId === '') ? null : (int) $sectionId;

        return [
            'class_id' => $classId,
            'section_id' => $sectionId,
        ];
    }

    public function isAssignedClassTeacher(?CultivationAdmin $user): bool
    {
        return $this->classTeacherAssignment($user) !== null;
    }

    public function assignedClassTeacherClassIds(?CultivationAdmin $user): array
    {
        $assignment = $this->classTeacherAssignment($user);
        if (!$assignment) {
            return [];
        }

        return [(int) $assignment['class_id']];
    }

    public function assignedClassTeacherSectionIds(?CultivationAdmin $user): array
    {
        $assignment = $this->classTeacherAssignment($user);
        if (!$assignment || $assignment['section_id'] === null) {
            return [];
        }

        return [(int) $assignment['section_id']];
    }

    public function canAccessClassTeacherContext(
        ?CultivationAdmin $user,
        int $classId,
        ?int $sectionId = null,
        bool $strictSection = false
    ): bool {
        if (!$user || !$user->isTeacher()) {
            return true;
        }

        $assignment = $this->classTeacherAssignment($user);
        if (!$assignment) {
            return false;
        }

        if ((int) $assignment['class_id'] !== $classId) {
            return false;
        }

        if ($assignment['section_id'] === null) {
            return true;
        }

        if ($sectionId === null) {
            return !$strictSection;
        }

        return (int) $assignment['section_id'] === (int) $sectionId;
    }
}
