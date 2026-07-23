<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditTeacherAssignments extends Command
{
    protected $signature = 'teacher-assignment:audit';

    protected $description = 'Read-only audit of teacher assignment conflicts, overlaps, and reference issues.';

    public function handle(): int
    {
        $this->info('Running read-only teacher assignment audit...');

        $hasSessionId = Schema::hasColumn('teacher_class_subjects', 'session_id');
        $sessionExpr = $hasSessionId ? 'COALESCE(session_id, 0)' : '0';

        $exactDuplicates = DB::table('teacher_class_subjects')
            ->selectRaw($sessionExpr.' as context_session_id, class_id, section_id, group_id, subject_id, gender_scope, teacher_id, COUNT(*) as cnt')
            ->groupBy('context_session_id', 'class_id', 'section_id', 'group_id', 'subject_id', 'gender_scope', 'teacher_id')
            ->having('cnt', '>', 1)
            ->get();

        $allOverlaps = DB::table('teacher_class_subjects as t1')
            ->join('teacher_class_subjects as t2', function ($join) use ($hasSessionId) {
                $join->on('t1.class_id', '=', 't2.class_id')
                    ->on('t1.subject_id', '=', 't2.subject_id')
                    ->whereRaw(($hasSessionId ? 'COALESCE(t1.session_id, 0)' : '0').' = '.($hasSessionId ? 'COALESCE(t2.session_id, 0)' : '0'))
                    ->whereRaw('COALESCE(t1.section_id, 0) = COALESCE(t2.section_id, 0)')
                    ->whereRaw('COALESCE(t1.group_id, 0) = COALESCE(t2.group_id, 0)')
                    ->whereRaw('t1.id < t2.id');
            })
            ->where('t1.gender_scope', 'all')
            ->whereIn('t2.gender_scope', ['male', 'female'])
            ->selectRaw('t1.id as all_row_id, t2.id as conflicting_row_id, '.($hasSessionId ? 't1.session_id' : 'null').' as context_session_id, t1.class_id, t1.section_id, t1.group_id, t1.subject_id, t2.gender_scope')
            ->get();

        $duplicateMale = DB::table('teacher_class_subjects')
            ->where('gender_scope', 'male')
            ->selectRaw($sessionExpr.' as context_session_id, class_id, section_id, group_id, subject_id, COUNT(*) as cnt')
            ->groupBy('context_session_id', 'class_id', 'section_id', 'group_id', 'subject_id')
            ->having('cnt', '>', 1)
            ->get();

        $duplicateFemale = DB::table('teacher_class_subjects')
            ->where('gender_scope', 'female')
            ->selectRaw($sessionExpr.' as context_session_id, class_id, section_id, group_id, subject_id, COUNT(*) as cnt')
            ->groupBy('context_session_id', 'class_id', 'section_id', 'group_id', 'subject_id')
            ->having('cnt', '>', 1)
            ->get();

        $missingSession = $hasSessionId
            ? DB::table('teacher_class_subjects')->whereNull('session_id')->count()
            : DB::table('teacher_class_subjects')->count();

        $invalidGender = DB::table('teacher_class_subjects')
            ->whereNotIn('gender_scope', ['all', 'male', 'female'])
            ->orWhereNull('gender_scope')
            ->orWhere('gender_scope', '')
            ->count();

        $brokenTeacherRefs = DB::table('teacher_class_subjects as tcs')
            ->leftJoin('cultivation_admins as ca', 'ca.id', '=', 'tcs.teacher_id')
            ->whereNull('ca.id')
            ->count();

        $brokenClassRefs = DB::table('teacher_class_subjects as tcs')
            ->leftJoin('class_manages as cm', 'cm.id', '=', 'tcs.class_id')
            ->whereNull('cm.id')
            ->count();

        $brokenSectionRefs = DB::table('teacher_class_subjects as tcs')
            ->leftJoin('section_manages as sm', 'sm.id', '=', 'tcs.section_id')
            ->whereNotNull('tcs.section_id')
            ->whereNull('sm.id')
            ->count();

        $brokenGroupRefs = DB::table('teacher_class_subjects as tcs')
            ->leftJoin('departments as d', 'd.id', '=', 'tcs.group_id')
            ->whereNotNull('tcs.group_id')
            ->whereNull('d.id')
            ->count();

        $brokenSubjectRefs = DB::table('teacher_class_subjects as tcs')
            ->leftJoin('subjects as s', 's.id', '=', 'tcs.subject_id')
            ->whereNull('s.id')
            ->count();

        $brokenSessionRefs = 0;
        if ($hasSessionId) {
            $brokenSessionRefs = DB::table('teacher_class_subjects as tcs')
                ->leftJoin('session_manages as smg', 'smg.id', '=', 'tcs.session_id')
                ->whereNotNull('tcs.session_id')
                ->whereNull('smg.id')
                ->count();
        }

        $this->table(
            ['Check', 'Count'],
            [
                ['Exact duplicate rows', $exactDuplicates->count()],
                ['All + Male/Female overlaps', $allOverlaps->count()],
                ['Duplicate Male rows in same context', $duplicateMale->count()],
                ['Duplicate Female rows in same context', $duplicateFemale->count()],
                ['Missing session context', $missingSession],
                ['Invalid gender_scope values', $invalidGender],
                ['Missing teacher references', $brokenTeacherRefs],
                ['Missing class references', $brokenClassRefs],
                ['Missing section references', $brokenSectionRefs],
                ['Missing group references', $brokenGroupRefs],
                ['Missing subject references', $brokenSubjectRefs],
                ['Missing session references', $brokenSessionRefs],
            ]
        );

        if ($exactDuplicates->isNotEmpty()) {
            $this->warn('Exact duplicate samples:');
            $this->table(
                ['session_key', 'class_id', 'section_id', 'group_id', 'subject_id', 'gender_scope', 'teacher_id', 'cnt'],
                $exactDuplicates->take(20)->map(function ($row) {
                    return [
                        $row->context_session_id,
                        $row->class_id,
                        $row->section_id,
                        $row->group_id,
                        $row->subject_id,
                        $row->gender_scope,
                        $row->teacher_id,
                        $row->cnt,
                    ];
                })->all()
            );
        }

        if ($allOverlaps->isNotEmpty()) {
            $this->warn('All scope overlap samples:');
            $this->table(
                ['all_row_id', 'conflicting_row_id', 'session_key', 'class_id', 'section_id', 'group_id', 'subject_id', 'conflicting_gender'],
                $allOverlaps->take(20)->map(function ($row) {
                    return [
                        $row->all_row_id,
                        $row->conflicting_row_id,
                        $row->context_session_id,
                        $row->class_id,
                        $row->section_id,
                        $row->group_id,
                        $row->subject_id,
                        $row->gender_scope,
                    ];
                })->all()
            );
        }

        $this->info('Audit completed. No data was modified.');

        return self::SUCCESS;
    }
}
