<?php

namespace App\Http\Controllers;

use App\Models\CultivationAdmin;
use App\Services\AttendanceSaveService;
use App\Services\TeacherAttendanceWorkspaceService;
use App\Services\TeacherDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TeacherAttendanceController extends Controller
{
    public function __construct(
        private TeacherAttendanceWorkspaceService $workspace,
        private AttendanceSaveService $saver,
        private TeacherDashboardService $dashboard,
    ) {}

    public function index(): View
    {
        $teacher = $this->teacher();
        try {
            $assignment = $this->workspace->assignment($teacher);
            $unavailable = null;
        } catch (ValidationException) {
            $assignment = null;
            $unavailable = 'Attendance requires an assigned primary class and primary section.';
        }
        return view('teacher.attendance.index', [
            'teacher' => $teacher,
            'assignment' => $assignment,
            'sessions' => $this->workspace->sessions(),
            'recent' => $this->workspace->recent($teacher),
            'unavailable' => $unavailable,
        ] + $this->dashboard->build($teacher));
    }

    public function load(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'sessionId' => ['required', 'integer'],
        ]);
        try {
            $this->workspace->workspace($this->teacher(), $data['date'], (int) $data['sessionId']);
            return redirect()->route('teacher.attendance.workspace', $data);
        } catch (ValidationException) {
            return back()->withInput()->with('error', 'The selected attendance scope is unavailable.');
        }
    }

    public function workspace(Request $request): View|RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'sessionId' => ['required', 'integer'],
        ]);
        try {
            return view('teacher.attendance.workspace',
                $this->workspace->workspace($this->teacher(), $data['date'], (int) $data['sessionId'])
                + ['teacher' => $this->teacher()]
                + $this->dashboard->build($this->teacher())
            );
        } catch (ValidationException) {
            return redirect()->route('teacher.attendance.index')
                ->with('error', 'The selected attendance scope is unavailable.');
        }
    }

    public function save(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'sessionId' => ['required', 'integer'],
            'studentId' => ['required', 'array', 'min:1', 'max:500'],
            'studentId.*' => ['required', 'integer', 'distinct'],
            'status' => ['required', 'array', 'min:1', 'max:500'],
            'status.*' => ['required', 'string', 'in:Present,Absent,Late,Excused'],
        ]);
        try {
            $teacher = $this->teacher();
            $scope = $this->workspace->workspace($teacher, $data['date'], (int) $data['sessionId']);
            $result = $this->saver->save(
                $data['date'],
                (int) $scope['assignment']['class']->id,
                (int) $scope['assignment']['section']->id,
                (int) $scope['session']->id,
                $teacher,
                $data['studentId'],
                $data['status'],
                $scope['population'],
            );
            Log::info('Teacher attendance saved', [
                'teacher_id' => $teacher->id,
                'attendance_date' => $data['date'],
                'class_id' => $scope['assignment']['class']->id,
                'section_id' => $scope['assignment']['section']->id,
                'submitted_count' => $result['submitted'],
                'created_count' => $result['created'],
                'updated_count' => $result['updated'],
            ]);
            return redirect()->route('teacher.attendance.workspace', [
                'date' => $data['date'],
                'sessionId' => $data['sessionId'],
            ])->with('success', "Attendance saved. Created: {$result['created']}, Updated: {$result['updated']}");
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable) {
            return back()->withInput($request->except(['studentId', 'status']))
                ->with('error', 'Attendance could not be saved safely.');
        }
    }

    private function teacher(): CultivationAdmin
    {
        /** @var CultivationAdmin $teacher */
        $teacher = Auth::guard('teacher')->user();
        return $teacher;
    }
}
