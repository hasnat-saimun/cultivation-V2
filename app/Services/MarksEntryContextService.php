<?php

namespace App\Services;

use App\Models\CultivationAdmin;
use App\Models\Department;
use App\Models\Subject;
use App\Models\classManage;
use App\Models\newAdmission;
use App\Models\sectionManage;
use App\Models\sessionManage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MarksEntryContextService
{
    private MarksEntryAuthorizationService $marksAuth;
    private DepartmentBasedClassDetector $departmentBasedClassDetector;

    public function __construct(
        MarksEntryAuthorizationService $marksAuth,
        DepartmentBasedClassDetector $departmentBasedClassDetector
    )
    {
        $this->marksAuth = $marksAuth;
        $this->departmentBasedClassDetector = $departmentBasedClassDetector;
    }

    public function classGroupRequirementMap(Collection $classes): array
    {
        $map = [];
        foreach ($classes as $class) {
            $map[(string) $class->id] = $this->classRequiresOptionalGroup((string) $class->className);
        }

        return $map;
    }

    public function classRequiresOptionalGroup(?string $className): bool
    {
        return $this->departmentBasedClassDetector->isDepartmentBasedClass($className);
    }

    public function teacherAndAdminClassIds(?CultivationAdmin $user): array
    {
        if (!$user || !$user->isTeacher()) {
            return classManage::query()->orderBy('id', 'DESC')->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        return $this->marksAuth->authorizedClassIds($user);
    }

    public function classesForContext(?CultivationAdmin $user): Collection
    {
        $classIds = $this->teacherAndAdminClassIds($user);

        if (empty($classIds)) {
            return collect();
        }

        return classManage::query()
            ->whereIn('id', $classIds)
            ->orderBy('id', 'DESC')
            ->get(['id', 'className']);
    }

    public function sectionsForContext(?CultivationAdmin $user, int $classId, ?int $sessionId = null): array
    {
        if ($classId <= 0) {
            return [];
        }

        if ($user && $user->isTeacher()) {
            return $this->teacherSectionsForContext($user, $classId, $sessionId);
        }

        return $this->adminSectionsForContext($classId, $sessionId);
    }

    public function groupsForContext(?CultivationAdmin $user, int $classId, ?int $sectionId = null, ?int $sessionId = null): array
    {
        if ($classId <= 0) {
            return [];
        }

        if ($user && $user->isTeacher()) {
            return $this->teacherGroupsForContext($user, $classId, $sectionId, $sessionId);
        }

        return $this->adminGroupsForContext($classId, $sectionId, $sessionId);
    }

    public function subjectsForContext(
        ?CultivationAdmin $user,
        int $classId,
        ?int $sectionId = null,
        ?int $optionalGroupId = null,
        ?int $sessionId = null
    ): Collection {
        if ($classId <= 0) {
            return collect();
        }

        if ($user && $user->isTeacher()) {
            return $this->marksAuth->authorizedSubjectsForMarks($user, $classId, $sectionId, $optionalGroupId, $sessionId);
        }

        $subjectIds = $this->adminSubjectIdsForContext($classId, $sectionId, $optionalGroupId, $sessionId);
        if (empty($subjectIds)) {
            return collect();
        }

        return Subject::query()
            ->whereIn('id', $subjectIds)
            ->orderBy('subjectName')
            ->get(['id', 'subjectName']);
    }

    public function normalizeSessionId($sessionValue): ?int
    {
        if ($sessionValue === null || $sessionValue === '') {
            return null;
        }

        if (is_numeric($sessionValue)) {
            return (int) $sessionValue;
        }

        $mappedId = sessionManage::where('session', (string) $sessionValue)->value('id');
        return $mappedId ? (int) $mappedId : null;
    }

    private function adminSectionsForContext(int $classId, ?int $sessionId): array
    {
        $ids = [];
        $sessionLabel = $sessionId ? (string) sessionManage::where('id', $sessionId)->value('session') : null;

        $studentSectionIds = newAdmission::query()
            ->where('className', $classId)
            ->when($sessionId || $sessionLabel, function ($query) use ($sessionId, $sessionLabel) {
                $query->where(function ($sq) use ($sessionId, $sessionLabel) {
                    if ($sessionId) {
                        $sq->orWhere('sessName', (string) $sessionId);
                    }
                    if ($sessionLabel) {
                        $sq->orWhere('sessName', $sessionLabel);
                    }
                });
            })
            ->whereNotNull('sectionName')
            ->pluck('sectionName')
            ->map(fn ($id) => (int) $id)
            ->all();

        $ids = array_merge($ids, $studentSectionIds);

        $routineSectionIds = DB::table('class_routines')
            ->where(function ($q) use ($classId) {
                $q->where('assignClass', (string) $classId)
                    ->orWhere('assignClass', $classId);
            })
            ->when($sessionId, function ($q) use ($sessionId) {
                $q->where(function ($sq) use ($sessionId) {
                    $sq->whereNull('assignSession')
                        ->orWhere('assignSession', '')
                        ->orWhere('assignSession', (string) $sessionId)
                        ->orWhere('assignSession', $sessionId);
                });
            })
            ->whereNotNull('assignSection')
            ->pluck('assignSection')
            ->map(fn ($id) => (int) $id)
            ->all();

        $ids = array_merge($ids, $routineSectionIds);

        $examRoutineSectionIds = DB::table('exam_routines')
            ->where(function ($q) use ($classId) {
                $q->where('assignClass', (string) $classId)
                    ->orWhere('assignClass', $classId);
            })
            ->when($sessionId, function ($q) use ($sessionId) {
                $q->where(function ($sq) use ($sessionId) {
                    $sq->whereNull('assignSession')
                        ->orWhere('assignSession', '')
                        ->orWhere('assignSession', (string) $sessionId)
                        ->orWhere('assignSession', $sessionId);
                });
            })
            ->whereNotNull('assignSection')
            ->pluck('assignSection')
            ->map(fn ($id) => (int) $id)
            ->all();

        $ids = array_merge($ids, $examRoutineSectionIds);

        $marksheetSectionIds = DB::table('marksheets')
            ->where('classId', $classId)
            ->when($sessionId, function ($q) use ($sessionId) {
                $q->where('sessionId', (string) $sessionId);
            })
            ->whereNotNull('groupId')
            ->pluck('groupId')
            ->map(fn ($id) => (int) $id)
            ->all();

        $ids = array_merge($ids, $marksheetSectionIds);

        $ids = array_values(array_unique(array_filter($ids, fn ($id) => $id > 0)));

        if (empty($ids)) {
            return [];
        }

        return sectionManage::query()
            ->whereIn('id', $ids)
            ->orderBy('id', 'ASC')
            ->get(['id', 'section'])
            ->map(fn ($s) => ['id' => (int) $s->id, 'name' => (string) $s->section])
            ->values()
            ->all();
    }

    private function teacherSectionsForContext(CultivationAdmin $user, int $classId, ?int $sessionId): array
    {
        $teacherId = (int) $user->id;

        $compositeRows = DB::table('teacher_class_subjects as tcs')
            ->join('class_manages as cm', 'cm.id', '=', 'tcs.class_id')
            ->leftJoin('section_manages as sm', 'sm.id', '=', 'tcs.section_id')
            ->where('tcs.teacher_id', $teacherId)
            ->where('tcs.class_id', $classId)
            ->when(Schema::hasColumn('teacher_class_subjects', 'session_id'), function ($query) use ($sessionId) {
                $query->when($sessionId !== null, function ($q) use ($sessionId) {
                    $q->where(function ($sq) use ($sessionId) {
                        $sq->whereNull('tcs.session_id')->orWhere('tcs.session_id', $sessionId);
                    });
                }, function ($q) {
                    $q->whereNull('tcs.session_id');
                });
            })
            ->where(function ($q) {
                $q->whereNull('tcs.section_id')
                    ->orWhereNotNull('sm.id');
            })
            ->select('tcs.section_id')
            ->distinct()
            ->get();

        $legacyRows = DB::table('teacher_sections as ts')
            ->join('section_manages as sm', 'sm.id', '=', 'ts.section_id')
            ->where('ts.teacher_id', $teacherId)
            ->where(function ($q) use ($classId) {
                $q->whereNull('ts.class_id')
                    ->orWhere('ts.class_id', $classId);
            })
            ->select('ts.section_id')
            ->distinct()
            ->get();

        $ids = [];
        foreach ($compositeRows as $row) {
            if ($row->section_id !== null) {
                $ids[] = (int) $row->section_id;
            }
        }
        foreach ($legacyRows as $row) {
            if ($row->section_id !== null) {
                $ids[] = (int) $row->section_id;
            }
        }

        $ids = array_values(array_unique(array_filter($ids, fn ($id) => $id > 0)));
        if (empty($ids)) {
            return $this->adminSectionsForContext($classId, $sessionId);
        }

        return sectionManage::query()
            ->whereIn('id', $ids)
            ->orderBy('id', 'ASC')
            ->get(['id', 'section'])
            ->map(fn ($s) => ['id' => (int) $s->id, 'name' => (string) $s->section])
            ->values()
            ->all();
    }

    private function adminGroupsForContext(int $classId, ?int $sectionId, ?int $sessionId): array
    {
        $ids = [];
        $sessionLabel = $sessionId ? (string) sessionManage::where('id', $sessionId)->value('session') : null;

        $studentGroups = newAdmission::query()
            ->where('className', $classId)
            ->when($sectionId, fn ($q) => $q->where('sectionName', $sectionId))
            ->when($sessionId || $sessionLabel, function ($query) use ($sessionId, $sessionLabel) {
                $query->where(function ($sq) use ($sessionId, $sessionLabel) {
                    if ($sessionId) {
                        $sq->orWhere('sessName', (string) $sessionId);
                    }
                    if ($sessionLabel) {
                        $sq->orWhere('sessName', $sessionLabel);
                    }
                });
            })
            ->whereNotNull('departmentName')
            ->pluck('departmentName')
            ->map(fn ($id) => (int) $id)
            ->all();

        $ids = array_merge($ids, $studentGroups);

        $routineGroups = DB::table('class_routines')
            ->where(function ($q) use ($classId) {
                $q->where('assignClass', (string) $classId)
                    ->orWhere('assignClass', $classId);
            })
            ->when($sectionId, function ($q) use ($sectionId) {
                $q->where(function ($sq) use ($sectionId) {
                    $sq->whereNull('assignSection')
                        ->orWhere('assignSection', '')
                        ->orWhere('assignSection', (string) $sectionId)
                        ->orWhere('assignSection', $sectionId);
                });
            })
            ->when($sessionId, function ($q) use ($sessionId) {
                $q->where(function ($sq) use ($sessionId) {
                    $sq->whereNull('assignSession')
                        ->orWhere('assignSession', '')
                        ->orWhere('assignSession', (string) $sessionId)
                        ->orWhere('assignSession', $sessionId);
                });
            })
            ->whereNotNull('assignDepartment')
            ->pluck('assignDepartment')
            ->map(fn ($id) => (int) $id)
            ->all();

        $ids = array_merge($ids, $routineGroups);

        $examRoutineGroups = DB::table('exam_routines')
            ->where(function ($q) use ($classId) {
                $q->where('assignClass', (string) $classId)
                    ->orWhere('assignClass', $classId);
            })
            ->when($sectionId, function ($q) use ($sectionId) {
                $q->where(function ($sq) use ($sectionId) {
                    $sq->whereNull('assignSection')
                        ->orWhere('assignSection', '')
                        ->orWhere('assignSection', (string) $sectionId)
                        ->orWhere('assignSection', $sectionId);
                });
            })
            ->when($sessionId, function ($q) use ($sessionId) {
                $q->where(function ($sq) use ($sessionId) {
                    $sq->whereNull('assignSession')
                        ->orWhere('assignSession', '')
                        ->orWhere('assignSession', (string) $sessionId)
                        ->orWhere('assignSession', $sessionId);
                });
            })
            ->whereNotNull('assignDepartment')
            ->pluck('assignDepartment')
            ->map(fn ($id) => (int) $id)
            ->all();

        $ids = array_merge($ids, $examRoutineGroups);

        $ids = array_values(array_unique(array_filter($ids, fn ($id) => $id > 0)));

        if (empty($ids)) {
            return [];
        }

        return Department::query()
            ->whereIn('id', $ids)
            ->orderBy('id', 'ASC')
            ->get(['id', 'departmentName'])
            ->map(fn ($d) => ['id' => (int) $d->id, 'name' => (string) $d->departmentName])
            ->values()
            ->all();
    }

    private function teacherGroupsForContext(CultivationAdmin $user, int $classId, ?int $sectionId, ?int $sessionId): array
    {
        $teacherId = (int) $user->id;

        $rows = DB::table('teacher_class_subjects as tcs')
            ->join('class_manages as cm', 'cm.id', '=', 'tcs.class_id')
            ->leftJoin('section_manages as sm', 'sm.id', '=', 'tcs.section_id')
            ->leftJoin('departments as d', 'd.id', '=', 'tcs.group_id')
            ->join('subjects as s', 's.id', '=', 'tcs.subject_id')
            ->where('tcs.teacher_id', $teacherId)
            ->where('tcs.class_id', $classId)
            ->when(Schema::hasColumn('teacher_class_subjects', 'session_id'), function ($query) use ($sessionId) {
                $query->when($sessionId !== null, function ($q) use ($sessionId) {
                    $q->where(function ($sq) use ($sessionId) {
                        $sq->whereNull('tcs.session_id')->orWhere('tcs.session_id', $sessionId);
                    });
                }, function ($q) {
                    $q->whereNull('tcs.session_id');
                });
            })
            ->where(function ($q) use ($sectionId) {
                if ($sectionId === null) {
                    $q->whereNull('tcs.section_id')->orWhereNotNull('sm.id');
                    return;
                }

                $q->whereNull('tcs.section_id')->orWhere('tcs.section_id', $sectionId);
            })
            ->where(function ($q) {
                $q->whereNull('tcs.group_id')->orWhereNotNull('d.id');
            })
            ->select('tcs.group_id')
            ->distinct()
            ->get();

        $ids = [];
        foreach ($rows as $row) {
            if ($row->group_id !== null) {
                $ids[] = (int) $row->group_id;
            }
        }

        $ids = array_values(array_unique(array_filter($ids, fn ($id) => $id > 0)));
        if (!empty($ids)) {
            return Department::query()
                ->whereIn('id', $ids)
                ->orderBy('id', 'ASC')
                ->get(['id', 'departmentName'])
                ->map(fn ($d) => ['id' => (int) $d->id, 'name' => (string) $d->departmentName])
                ->values()
                ->all();
        }

        return $this->adminGroupsForContext($classId, $sectionId, $sessionId);
    }

    private function adminSubjectIdsForContext(int $classId, ?int $sectionId, ?int $optionalGroupId, ?int $sessionId): array
    {
        $ids = [];

        $routineSubjectIds = DB::table('class_routine_items as cri')
            ->join('class_routines as cr', 'cr.id', '=', 'cri.class_routine_id')
            ->where(function ($q) use ($classId) {
                $q->where('cr.assignClass', (string) $classId)
                    ->orWhere('cr.assignClass', $classId);
            })
            ->when($sectionId, function ($q) use ($sectionId) {
                $q->where(function ($sq) use ($sectionId) {
                    $sq->whereNull('cr.assignSection')
                        ->orWhere('cr.assignSection', '')
                        ->orWhere('cr.assignSection', (string) $sectionId)
                        ->orWhere('cr.assignSection', $sectionId);
                });
            })
            ->when($optionalGroupId, function ($q) use ($optionalGroupId) {
                $q->where(function ($sq) use ($optionalGroupId) {
                    $sq->whereNull('cr.assignDepartment')
                        ->orWhere('cr.assignDepartment', '')
                        ->orWhere('cr.assignDepartment', (string) $optionalGroupId)
                        ->orWhere('cr.assignDepartment', $optionalGroupId);
                });
            })
            ->whereNotNull('cri.subject_id')
            ->pluck('cri.subject_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $ids = array_merge($ids, $routineSubjectIds);

        $examRoutineSubjectIds = DB::table('exam_routine_items as eri')
            ->join('exam_routines as er', 'er.id', '=', 'eri.exam_routine_id')
            ->where(function ($q) use ($classId) {
                $q->where('er.assignClass', (string) $classId)
                    ->orWhere('er.assignClass', $classId);
            })
            ->when($sectionId, function ($q) use ($sectionId) {
                $q->where(function ($sq) use ($sectionId) {
                    $sq->whereNull('er.assignSection')
                        ->orWhere('er.assignSection', '')
                        ->orWhere('er.assignSection', (string) $sectionId)
                        ->orWhere('er.assignSection', $sectionId);
                });
            })
            ->when($optionalGroupId, function ($q) use ($optionalGroupId) {
                $q->where(function ($sq) use ($optionalGroupId) {
                    $sq->whereNull('er.assignDepartment')
                        ->orWhere('er.assignDepartment', '')
                        ->orWhere('er.assignDepartment', (string) $optionalGroupId)
                        ->orWhere('er.assignDepartment', $optionalGroupId);
                });
            })
            ->whereNotNull('eri.subject_id')
            ->pluck('eri.subject_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $ids = array_merge($ids, $examRoutineSubjectIds);

        $marksheetSubjectIds = DB::table('marksheets')
            ->where('classId', $classId)
            ->when($sessionId, function ($q) use ($sessionId) {
                $q->where('sessionId', (string) $sessionId);
            })
            ->when($sectionId, function ($q) use ($sectionId) {
                $q->where('groupId', $sectionId);
            })
            ->whereNotNull('subjectId')
            ->pluck('subjectId')
            ->map(fn ($id) => (int) $id)
            ->all();

        $ids = array_merge($ids, $marksheetSubjectIds);

        // Last-resort fallback: class-scoped subjects plus global "All classes" rows (assign_class=0).
        $classMappedSubjectIds = Subject::query()
            ->where(function ($q) use ($classId) {
                $q->where('assign_class', (string) $classId)
                    ->orWhere('assign_class', '0')
                    ->orWhere('assign_class', 0)
                    ->orWhere('assign_class', 'like', $classId . ',%')
                    ->orWhere('assign_class', 'like', '%,' . $classId . ',%')
                    ->orWhere('assign_class', 'like', '%,' . $classId);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $ids = array_merge($ids, $classMappedSubjectIds);

        $ids = array_values(array_unique(array_filter($ids, fn ($id) => $id > 0)));
        if (empty($ids)) {
            return [];
        }

        $validSubjectIds = Subject::query()->whereIn('id', $ids)->pluck('id')->map(fn ($id) => (int) $id)->all();

        sort($validSubjectIds);
        return $validSubjectIds;
    }
}
