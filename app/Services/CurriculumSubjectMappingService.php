<?php

namespace App\Services;

use App\Models\CurriculumSubjectMapping;
use App\Models\Subject;
use Illuminate\Support\Collection;

class CurriculumSubjectMappingService
{
    /**
     * @return Collection<int, Subject>
     */
    public function mappedMainSubjectsForStudent(object $student, bool $departmentBasedClass): Collection
    {
        $sessionId = $this->toInt($student->sessName ?? null);
        $classId = $this->toInt($student->className ?? null);
        $sectionId = $this->toInt($student->sectionName ?? null);
        $departmentId = $this->toInt($student->departmentName ?? null);

        if ($sessionId === null || $classId === null) {
            return collect();
        }

        $exactSectionScope = CurriculumSubjectMapping::normalizeSectionScope($sectionId);
        $classWideSectionScope = CurriculumSubjectMapping::normalizeSectionScope(null);
        $exactDepartmentScope = CurriculumSubjectMapping::normalizeDepartmentScope($departmentId);
        $commonDepartmentScope = CurriculumSubjectMapping::normalizeDepartmentScope(null);

        $sectionScopes = array_values(array_unique([
            $exactSectionScope,
            $classWideSectionScope,
            ...CurriculumSubjectMapping::sectionScopeCandidates(null),
        ]));
        $departmentScopes = $departmentBasedClass
            ? array_values(array_unique([
                $exactDepartmentScope,
                $commonDepartmentScope,
                ...CurriculumSubjectMapping::departmentScopeCandidates(null),
            ]))
            : array_values(array_unique([
                $commonDepartmentScope,
                ...CurriculumSubjectMapping::departmentScopeCandidates(null),
            ]));

        $rows = CurriculumSubjectMapping::query()
            ->where('session_id', (string) $sessionId)
            ->where('class_id', (string) $classId)
            ->where('mapping_type', CurriculumSubjectMapping::TYPE_MAIN)
            ->where('is_active', 1)
            ->where(function ($query) use ($sectionScopes, $sectionId): void {
                $query->whereIn('normalized_section_scope', $sectionScopes)
                    ->orWhere(function ($fallback) use ($sectionId): void {
                        $fallback->whereNull('normalized_section_scope');
                        if ($sectionId === null) {
                            $fallback->where(function ($allScope): void {
                                $allScope->whereNull('section_id')->orWhere('section_id', '');
                            });
                            return;
                        }

                        $fallback->where(function ($exactScope) use ($sectionId): void {
                            $exactScope->where('section_id', (string) $sectionId)
                                ->orWhereNull('section_id')
                                ->orWhere('section_id', '');
                        });
                    });
            })
            ->where(function ($query) use ($departmentScopes, $departmentId, $departmentBasedClass): void {
                $query->whereIn('normalized_department_scope', $departmentScopes)
                    ->orWhere(function ($fallback) use ($departmentId, $departmentBasedClass): void {
                        $fallback->whereNull('normalized_department_scope');
                        if (!$departmentBasedClass || $departmentId === null) {
                            $fallback->where(function ($allScope): void {
                                $allScope->whereNull('department_id')->orWhere('department_id', '');
                            });
                            return;
                        }

                        $fallback->where(function ($exactScope) use ($departmentId): void {
                            $exactScope->where('department_id', (string) $departmentId)
                                ->orWhereNull('department_id')
                                ->orWhere('department_id', '');
                        });
                    });
            })
            ->orderBy('subject_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'subject_id', 'section_id', 'department_id', 'normalized_section_scope', 'normalized_department_scope', 'sort_order']);

        $resolvedRows = $rows->map(function ($row) {
            $rowSectionScope = $row->normalized_section_scope;
            if ($rowSectionScope === null) {
                $rowSectionScope = CurriculumSubjectMapping::normalizeSectionScope($this->toInt($row->section_id ?? null));
            }
            $rowDepartmentScope = $row->normalized_department_scope;
            if ($rowDepartmentScope === null) {
                $rowDepartmentScope = CurriculumSubjectMapping::normalizeDepartmentScope($this->toInt($row->department_id ?? null));
            }

            return [
                'row' => $row,
                'section_scope' => $rowSectionScope,
                'department_scope' => $rowDepartmentScope,
            ];
        });

        if ($resolvedRows->isEmpty()) {
            return collect();
        }

        $bySubject = [];
        foreach ($resolvedRows as $resolved) {
            $row = $resolved['row'];
            $subjectId = (int) $row->subject_id;
            $rowSectionScope = (string) $resolved['section_scope'];
            $rowDepartmentScope = (string) $resolved['department_scope'];

            $rank = $this->scopeRank(
                $rowSectionScope,
                $rowDepartmentScope,
                $exactSectionScope,
                $classWideSectionScope,
                $exactDepartmentScope,
                $commonDepartmentScope,
                $departmentBasedClass,
                true
            );

            if ($rank === 0) {
                continue;
            }

            if (!isset($bySubject[$subjectId]) || $this->isBetterRow($rank, (int) $row->sort_order, (int) $row->id, $bySubject[$subjectId])) {
                $bySubject[$subjectId] = [
                    'rank' => $rank,
                    'sort_order' => (int) $row->sort_order,
                    'row_id' => (int) $row->id,
                    'department_id' => $this->toInt($row->department_id ?? null),
                ];
            }
        }

        if ($bySubject === []) {
            return collect();
        }

        $subjectIds = array_keys($bySubject);
        $subjects = Subject::query()
            ->whereIn('id', $subjectIds)
            ->get()
            ->keyBy(fn (Subject $subject) => (int) $subject->id);

        $ordered = collect($bySubject)
            ->sort(function (array $left, array $right): int {
                $orderCompare = ($left['sort_order'] <=> $right['sort_order']);
                if ($orderCompare !== 0) {
                    return $orderCompare;
                }
                return ($right['rank'] <=> $left['rank']);
            })
            ->map(function (array $meta, int $subjectId) use ($subjects) {
                /** @var Subject|null $subject */
                $subject = $subjects->get($subjectId);
                if ($subject === null) {
                    return null;
                }

                $subject->setAttribute('applicability_order', (int) $meta['sort_order']);
                $subject->setAttribute('mapping_sort_order', (int) $meta['sort_order']);
                $subject->setAttribute('mapping_department_id', $meta['department_id']);
                return $subject;
            })
            ->filter()
            ->values();

        return $ordered;
    }

    private function scopeRank(
        string $rowSectionScope,
        string $rowDepartmentScope,
        string $exactSectionScope,
        string $classWideSectionScope,
        string $exactDepartmentScope,
        string $commonDepartmentScope,
        bool $departmentBasedClass,
        bool $allowClassWideCommon
    ): int
    {
        $isSectionExact = $rowSectionScope === $exactSectionScope;
        $isSectionClassWide = in_array($rowSectionScope, [$classWideSectionScope, 'section:all'], true);
        if (!$isSectionExact && !$isSectionClassWide) {
            return 0;
        }

        $isDepartmentExact = $departmentBasedClass && $rowDepartmentScope === $exactDepartmentScope;
        $isDepartmentCommon = in_array($rowDepartmentScope, [$commonDepartmentScope, 'department:all'], true);
        if (!$isDepartmentExact && !$isDepartmentCommon) {
            return 0;
        }

        if ($isSectionExact && $isDepartmentExact) {
            return 4;
        }
        if ($isSectionExact && $isDepartmentCommon) {
            return 3;
        }
        if ($isSectionClassWide && $isDepartmentExact) {
            return 2;
        }
        if ($isSectionClassWide && $isDepartmentCommon) {
            if (!$allowClassWideCommon) {
                return 0;
            }

            return 1;
        }

        return 0;
    }

    /** @param array{rank:int,sort_order:int,row_id:int} $current */
    private function isBetterRow(int $newRank, int $newSortOrder, int $newRowId, array $current): bool
    {
        if ($newRank !== $current['rank']) {
            return $newRank > $current['rank'];
        }

        if ($newSortOrder !== $current['sort_order']) {
            return $newSortOrder < $current['sort_order'];
        }

        return $newRowId < $current['row_id'];
    }

    private function toInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        if (!preg_match('/^\d+$/', $normalized)) {
            return null;
        }

        return (int) $normalized;
    }
}
