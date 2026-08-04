<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\classManage;
use App\Models\CultivationAdmin;
use App\Models\newAdmission;
use App\Models\sectionManage;
use App\Models\sessionManage;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TeacherAttendanceWorkspaceService
{
    /** @return array{class:classManage,section:sectionManage} */
    public function assignment(CultivationAdmin $teacher): array
    {
        if (!$teacher->isTeacher() || !$teacher->primary_class_id || !$teacher->primary_section_id) {
            throw ValidationException::withMessages(['attendance' => 'A primary class and section assignment is required.']);
        }
        $class = classManage::find((int) $teacher->primary_class_id);
        $section = sectionManage::find((int) $teacher->primary_section_id);
        if (!$class || !$section) {
            throw ValidationException::withMessages(['attendance' => 'The primary attendance assignment is unavailable.']);
        }
        return compact('class', 'section');
    }

    public function sessions(): Collection
    {
        return sessionManage::query()->orderByDesc('id')->get();
    }

    /** @return array{assignment:array,session:sessionManage,date:string,population:Collection,existing:Collection} */
    public function workspace(CultivationAdmin $teacher, string $date, int $sessionId): array
    {
        $assignment = $this->assignment($teacher);
        $session = sessionManage::find($sessionId);
        if (!$session) {
            throw ValidationException::withMessages(['sessionId' => 'The selected session is invalid.']);
        }
        $population = $this->population(
            $sessionId,
            (int) $assignment['class']->id,
            (int) $assignment['section']->id,
        );
        $existing = Attendance::query()
            ->whereDate('attendance_date', $date)
            ->where('class_id', $assignment['class']->id)
            ->where('section_id', $assignment['section']->id)
            ->whereIn('student_id', $population->pluck('id'))
            ->get()
            ->keyBy('student_id');

        return compact('assignment', 'session', 'date', 'population', 'existing');
    }

    public function population(int $sessionId, int $classId, int $sectionId): Collection
    {
        return newAdmission::query()
            ->where('sessName', $sessionId)
            ->where('className', $classId)
            ->where('sectionName', $sectionId)
            ->professionalOrder()
            ->get();
    }

    public function recent(CultivationAdmin $teacher): Collection
    {
        try {
            $assignment = $this->assignment($teacher);
        } catch (ValidationException) {
            return collect();
        }
        return Attendance::query()
            ->where('class_id', $assignment['class']->id)
            ->where('section_id', $assignment['section']->id)
            ->selectRaw('attendance_date, COUNT(*) as total, SUM(status = ?) as present', ['Present'])
            ->groupBy('attendance_date')
            ->orderByDesc('attendance_date')
            ->limit(5)
            ->get();
    }
}
