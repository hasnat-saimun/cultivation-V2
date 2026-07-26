<?php

namespace App\Services;

use App\Models\CultivationAdmin;
use App\Models\ServerConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TeacherDashboardService
{
    public function __construct(private TeacherAssignmentAcademicScopeService $academicScope) {}

    /** @return array<string,mixed> */
    public function build(CultivationAdmin $teacher): array
    {
        $instituteName = Schema::hasTable('server_configs')
            ? ServerConfig::query()->latest('id')->value('instituteName')
            : null;

        $statistics = [
            'classes' => 0,
            'subjects' => 0,
            'sections' => 0,
        ];
        $assignments = collect();
        $sessionName = null;

        if (Schema::hasTable('teacher_class_subjects')) {
            $scope = DB::table('teacher_class_subjects')
                ->where('teacher_id', $teacher->id)
                ->whereNotNull('session_id');
            $aggregate = (clone $scope)->selectRaw(
                'COUNT(DISTINCT class_id) as classes_count,
                 COUNT(DISTINCT subject_id) as subjects_count,
                 COUNT(DISTINCT section_id) as sections_count'
            )->first();

            $statistics = [
                'classes' => (int) ($aggregate->classes_count ?? 0),
                'subjects' => (int) ($aggregate->subjects_count ?? 0),
                'sections' => (int) ($aggregate->sections_count ?? 0),
            ];

            $assignments = $this->assignmentQuery($teacher->id)->limit(8)->get()
                ->each(function ($assignment) {
                    $assignment->department_label = $assignment->group_id !== null
                        ? ($assignment->group_name ?: 'Department unavailable')
                        : ($this->academicScope->requiresGroupName($assignment->class_name)
                            ? 'All Departments'
                            : 'Not Applicable');
                });
            $sessions = DB::table('teacher_class_subjects as tcs')
                ->join('session_manages as sm', 'sm.id', '=', 'tcs.session_id')
                ->where('tcs.teacher_id', $teacher->id)
                ->whereNotNull('tcs.session_id')
                ->distinct()
                ->pluck('sm.session');
            if ($sessions->count() === 1 && filled($sessions->first())) {
                $sessionName = (string) $sessions->first();
            }
        }

        $activities = Schema::hasTable('result_lifecycle_events')
            ? DB::table('result_lifecycle_events')
                ->where('actor_id', $teacher->id)
                ->where('actor_role', 'teacher')
                ->latest('created_at')
                ->limit(5)
                ->get(['action', 'created_at'])
                ->map(fn ($event) => [
                    'label' => $this->activityLabel((string) $event->action),
                    'occurred_at' => $event->created_at,
                ])
            : collect();

        return [
            'instituteName' => filled($instituteName) ? (string) $instituteName : 'Cultivation',
            'statistics' => $statistics,
            'assignments' => $assignments,
            'currentSession' => $sessionName,
            'activities' => $activities,
            'avatarUrl' => $this->safeAvatarUrl($teacher),
            'avatarInitials' => $this->initials((string) ($teacher->adminName ?: $teacher->adminUser ?: 'Teacher')),
        ];
    }

    private function assignmentQuery(int $teacherId)
    {
        return DB::table('teacher_class_subjects as tcs')
            ->leftJoin('session_manages as sm', 'sm.id', '=', 'tcs.session_id')
            ->leftJoin('class_manages as cm', 'cm.id', '=', 'tcs.class_id')
            ->leftJoin('section_manages as sec', 'sec.id', '=', 'tcs.section_id')
            ->leftJoin('subjects as sub', 'sub.id', '=', 'tcs.subject_id')
            ->leftJoin('departments as dep', 'dep.id', '=', 'tcs.group_id')
            ->where('tcs.teacher_id', $teacherId)
            ->whereNotNull('tcs.session_id')
            ->select([
                'tcs.session_id', 'sm.session as session_name',
                'tcs.class_id', 'cm.className as class_name',
                'tcs.section_id', 'sec.section as section_name',
                'tcs.subject_id', 'sub.subjectName as subject_name',
                'tcs.group_id', 'dep.departmentName as group_name',
                'tcs.gender_scope',
            ])
            ->groupBy([
                'tcs.session_id', 'sm.session',
                'tcs.class_id', 'cm.className',
                'tcs.section_id', 'sec.section',
                'tcs.subject_id', 'sub.subjectName',
                'tcs.group_id', 'dep.departmentName',
                'tcs.gender_scope',
            ])
            ->orderBy('sm.session')
            ->orderBy('cm.className')
            ->orderBy('sec.section')
            ->orderBy('sub.subjectName');
    }

    private function activityLabel(string $action): string
    {
        return match ($action) {
            'draft_marks_created' => 'Draft marks saved',
            'draft_marks_updated' => 'Draft marks updated',
            'subject_confirmed' => 'Subject result confirmed',
            'subject_reopened' => 'Subject result reopened',
            'result_published' => 'Result published',
            'result_unpublished' => 'Result unpublished',
            default => 'Result activity recorded',
        };
    }

    private function safeAvatarUrl(CultivationAdmin $teacher): ?string
    {
        $avatar = trim((string) $teacher->avatar);
        if ($avatar === '' || basename($avatar) !== $avatar) {
            return null;
        }

        $path = public_path('upload/image/admin/'.$avatar);
        return is_file($path) ? asset('public/upload/image/admin/'.rawurlencode($avatar)) : null;
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $initials = collect($parts)->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('');

        return mb_strtoupper($initials ?: 'T');
    }
}
