<?php

namespace App\Services;

use App\Models\CultivationAdmin;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TeacherAssignmentSessionReconciliationService
{
    public function __construct(
        private TeacherSubjectAssignmentAvailabilityService $availability,
    ) {}

    public function audit(): Collection
    {
        return DB::table('teacher_class_subjects as tcs')
            ->leftJoin('cultivation_admins as teacher', 'teacher.id', '=', 'tcs.teacher_id')
            ->leftJoin('class_manages as class', 'class.id', '=', 'tcs.class_id')
            ->leftJoin('section_manages as section', 'section.id', '=', 'tcs.section_id')
            ->leftJoin('departments as department', 'department.id', '=', 'tcs.group_id')
            ->leftJoin('subjects as subject', 'subject.id', '=', 'tcs.subject_id')
            ->whereNull('tcs.session_id')
            ->orderBy('tcs.id')
            ->get([
                'tcs.id', 'tcs.teacher_id', 'teacher.adminName as teacher_name',
                'tcs.class_id', 'class.className as class_name',
                'tcs.section_id', 'section.section as section_name',
                'tcs.group_id', 'department.departmentName as department_name',
                'tcs.subject_id', 'subject.subjectName as subject_name',
                'tcs.gender_scope', 'tcs.created_at', 'tcs.updated_at',
            ])
            ->map(function ($row) {
                $evidence = $this->plausibleSessionEvidence($row);
                $sessionIds = collect($evidence)
                    ->pluck('session_id')
                    ->unique()
                    ->sort()
                    ->values();

                return [
                    'assignment_id' => (int) $row->id,
                    'teacher_id' => (int) $row->teacher_id,
                    'teacher' => $row->teacher_name ?: 'Missing teacher #'.$row->teacher_id,
                    'class_id' => (int) $row->class_id,
                    'class' => $row->class_name ?: 'Missing class #'.$row->class_id,
                    'section_id' => $row->section_id === null ? null : (int) $row->section_id,
                    'section' => $row->section_name ?: 'No Section',
                    'department_id' => $row->group_id === null ? null : (int) $row->group_id,
                    'department' => $row->department_name ?: 'All Departments',
                    'subject_id' => (int) $row->subject_id,
                    'subject' => $row->subject_name ?: 'Missing subject #'.$row->subject_id,
                    'gender' => $this->availability->normalizeGenderScope($row->gender_scope) ?? 'invalid',
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                    'plausible_sessions' => $sessionIds->all(),
                    'evidence' => $evidence,
                    'resolution' => $sessionIds->count() === 1 ? 'conclusive' : 'ambiguous',
                    'proposed_session_id' => $sessionIds->count() === 1 ? (int) $sessionIds->first() : null,
                ];
            });
    }

    public function reconcile(
        int $assignmentId,
        int $sessionId,
        CultivationAdmin $actor,
        string $backupPath,
    ): array {
        if (!$actor->isGeneral() && (int) $actor->userType <= CultivationAdmin::ROLE_GENERAL) {
            throw ValidationException::withMessages(['actor' => ['A General or Super Admin is required.']]);
        }
        if (!is_file($backupPath) || filesize($backupPath) === 0) {
            throw ValidationException::withMessages(['backup' => ['A non-empty verified backup artifact is required before execution.']]);
        }

        return DB::transaction(function () use ($assignmentId, $sessionId, $actor, $backupPath) {
            $row = DB::table('teacher_class_subjects')->where('id', $assignmentId)->lockForUpdate()->first();
            if (!$row || $row->session_id !== null) {
                throw ValidationException::withMessages(['assignment' => ['The assignment is missing or already reconciled.']]);
            }
            if (!DB::table('session_manages')->where('id', $sessionId)->exists()) {
                throw ValidationException::withMessages(['session' => ['The selected academic session does not exist.']]);
            }

            $context = [
                'session_id' => $sessionId,
                'class_id' => $row->class_id,
                'section_id' => $row->section_id,
                'group_id' => $row->group_id,
                'subject_id' => $row->subject_id,
            ];
            DB::table('subjects')->where('id', $row->subject_id)->lockForUpdate()->first();
            $this->availability->lockContextRows($context, (int) $row->id);
            $gender = $this->availability->normalizeGenderScope($row->gender_scope);
            if ($gender === null || !$this->availability->canAssignGender($context, $gender, (int) $row->id)) {
                throw ValidationException::withMessages([
                    'session' => ['The selected session conflicts with existing subject/gender coverage.'],
                ]);
            }

            DB::table('teacher_class_subjects')->where('id', $assignmentId)->update([
                'session_id' => $sessionId,
                'updated_at' => now(),
            ]);
            $audit = [
                'assignment_id' => $assignmentId,
                'actor_id' => (int) $actor->id,
                'before_session_id' => null,
                'after_session_id' => $sessionId,
                'backup_path' => realpath($backupPath) ?: $backupPath,
            ];
            Log::notice('teacher_assignment_session_reconciled', $audit);

            return $audit;
        });
    }

    private function plausibleSessionEvidence(object $assignment): array
    {
        $evidence = [];
        $validSessions = DB::table('session_manages')->pluck('id')->map(fn ($id) => (int) $id)->flip();

        $marks = DB::table('marksheets as m')
            ->leftJoin('new_admissions as student', 'student.id', '=', 'm.studentId')
            ->where(function ($query) use ($assignment) {
                $query->where('m.teacher_id', $assignment->teacher_id)
                    ->orWhere(function ($entered) use ($assignment) {
                        $entered->where('m.entered_by', $assignment->teacher_id)
                            ->where('m.entered_by_role', 'teacher');
                    });
            })
            ->where('m.classId', (string) $assignment->class_id)
            ->where('m.subjectId', (string) $assignment->subject_id)
            ->when($assignment->section_id !== null, fn ($query) =>
                $query->where('m.groupId', (string) $assignment->section_id))
            ->when($assignment->group_id !== null, fn ($query) =>
                $query->where('student.departmentName', (string) $assignment->group_id))
            ->whereNotNull('m.sessionId')
            ->selectRaw('m.sessionId as session_id, COUNT(*) as evidence_count')
            ->groupBy('m.sessionId')
            ->get();
        foreach ($marks as $mark) {
            if (ctype_digit((string) $mark->session_id) && $validSessions->has((int) $mark->session_id)) {
                $evidence[] = [
                    'session_id' => (int) $mark->session_id,
                    'source' => 'marksheets',
                    'count' => (int) $mark->evidence_count,
                ];
            }
        }

        $events = DB::table('result_lifecycle_events')
            ->where('actor_id', $assignment->teacher_id)
            ->where('actor_role', 'teacher')
            ->where('classId', (string) $assignment->class_id)
            ->where('subjectId', (string) $assignment->subject_id)
            ->when($assignment->section_id !== null, fn ($query) =>
                $query->where('groupId', (string) $assignment->section_id))
            ->whereNotNull('sessionId')
            ->selectRaw('sessionId as session_id, COUNT(*) as evidence_count')
            ->groupBy('sessionId')
            ->get();
        foreach ($events as $event) {
            if (ctype_digit((string) $event->session_id) && $validSessions->has((int) $event->session_id)) {
                $evidence[] = [
                    'session_id' => (int) $event->session_id,
                    'source' => 'result_lifecycle_events',
                    'count' => (int) $event->evidence_count,
                ];
            }
        }

        return $evidence;
    }
}
