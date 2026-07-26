<?php

namespace App\Console\Commands;

use App\Services\DepartmentBasedClassDetector;
use App\Services\TeacherSubjectAssignmentAvailabilityService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditTeacherAssignmentDepartmentScopes extends Command
{
    protected $signature = 'teacher-assignment:audit-department-scopes';
    protected $description = 'Read-only audit of concrete-session assignments whose department/group is NULL';

    public function handle(
        DepartmentBasedClassDetector $classes,
        TeacherSubjectAssignmentAvailabilityService $availability
    ): int {
        $rows = DB::table('teacher_class_subjects as tcs')
            ->leftJoin('class_manages as cls', 'cls.id', '=', 'tcs.class_id')
            ->whereNotNull('tcs.session_id')
            ->whereNull('tcs.group_id')
            ->select('tcs.id', 'tcs.session_id', 'tcs.class_id', 'cls.className as class_name',
                'tcs.section_id', 'tcs.subject_id', 'tcs.gender_scope')
            ->orderBy('tcs.id')
            ->get();

        $groupEnabled = 0;
        $notApplicable = 0;
        $ambiguous = 0;
        $concreteOverlaps = 0;
        $duplicateAllOverlaps = 0;

        foreach ($rows as $row) {
            if ($row->class_name === null || trim((string) $row->class_name) === '') {
                $ambiguous++;
                continue;
            }
            if ($classes->isDepartmentBasedClass((string) $row->class_name)) {
                $groupEnabled++;
            } else {
                $notApplicable++;
                continue;
            }

            $intersections = DB::table('teacher_class_subjects')
                ->where('id', '!=', $row->id)
                ->where('session_id', $row->session_id)
                ->where('class_id', $row->class_id)
                ->when($row->section_id === null, fn ($q) => $q->whereNull('section_id'),
                    fn ($q) => $q->where('section_id', $row->section_id))
                ->where('subject_id', $row->subject_id)
                ->get(['group_id', 'gender_scope']);

            foreach ($intersections as $other) {
                if ($availability->canGenderScopesOverlap(
                    (string) ($row->gender_scope ?? 'all'),
                    (string) ($other->gender_scope ?? 'all')
                )) {
                    $other->group_id === null ? $duplicateAllOverlaps++ : $concreteOverlaps++;
                }
            }
        }

        $this->table(['Classification', 'Count'], [
            ['Group-enabled: potential All Departments', $groupEnabled],
            ['Non-group-enabled: Not Applicable', $notApplicable],
            ['Invalid/ambiguous class metadata', $ambiguous],
            ['All-vs-concrete gender-overlap pairs', $concreteOverlaps],
            ['Duplicate All Departments gender-overlap pairs', intdiv($duplicateAllOverlaps, 2)],
        ]);
        $this->info("Concrete-session NULL department/group rows: {$rows->count()}.");
        $this->comment('READ-ONLY: no assignment or production data was modified.');

        return self::SUCCESS;
    }
}
