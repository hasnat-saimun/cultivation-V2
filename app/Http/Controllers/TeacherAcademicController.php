<?php

namespace App\Http\Controllers;

use App\Models\CultivationAdmin;
use App\Services\TeacherAcademicWorkspaceService;
use App\Services\TeacherDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TeacherAcademicController extends Controller
{
    public function __construct(private TeacherAcademicWorkspaceService $workspace, private TeacherDashboardService $dashboard) {}

    public function classes(): View
    {
        $teacher = $this->teacher();
        return view('teacher.academic.classes', ['classes' => $this->workspace->classCards($teacher), 'teacher' => $teacher]
            + $this->dashboard->build($teacher));
    }

    public function students(Request $request): View
    {
        $data = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'page' => ['nullable', 'integer', 'min:1']]);
        $teacher = $this->teacher();
        return view('teacher.academic.students', ['students' => $this->workspace->students($teacher, $data['search'] ?? null),
            'search' => $data['search'] ?? '', 'teacher' => $teacher] + $this->dashboard->build($teacher));
    }

    public function student(int $student): View
    {
        $teacher = $this->teacher();
        try { $record = $this->workspace->student($teacher, $student); }
        catch (ValidationException) { throw new NotFoundHttpException(); }
        return view('teacher.academic.student', ['student' => $record,
            'results' => $this->workspace->resultSummary($teacher, $record),
            'attendance' => $this->workspace->attendanceSummary($record), 'teacher' => $teacher]
            + $this->dashboard->build($teacher));
    }

    private function teacher(): CultivationAdmin
    {
        /** @var CultivationAdmin $teacher */ $teacher = Auth::guard('teacher')->user(); return $teacher;
    }
}
