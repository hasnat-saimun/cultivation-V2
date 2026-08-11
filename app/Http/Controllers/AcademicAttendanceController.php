<?php

namespace App\Http\Controllers;

use App\Models\classManage;
use App\Models\Department;
use App\Models\Exam;
use App\Models\sectionManage;
use App\Models\sessionManage;
use App\Services\AcademicAttendanceService;
use App\Services\CultivationAdminResolver;
use App\Services\Students\StudentGenderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class AcademicAttendanceController extends Controller
{
    public function __construct(
        private AcademicAttendanceService $attendance,
        private CultivationAdminResolver $admins,
        private StudentGenderService $gender,
    ) {}

    public function index(Request $request)
    {
        $scope = $this->scope($request, false);
        $students = collect();
        $records = collect();
        if ($this->complete($scope)) {
            $students = $this->attendance->students($scope)->get();
            $records = $this->attendance->records($scope, $students);
        }

        return view('result.academic-attendance.index', [
            'scope' => $scope,
            'students' => $students,
            'records' => $records,
            'exams' => Exam::query()->orderBy('examName')->get(['id', 'examName']),
            'sessions' => sessionManage::query()->orderBy('session')->get(['id', 'session']),
            'classes' => classManage::query()->orderBy('className')->get(['id', 'className']),
            'sections' => sectionManage::query()->orderBy('section')->get(['id', 'section']),
            'departments' => Department::query()->orderBy('departmentName')->get(['id', 'departmentName']),
            'genderOptions' => [StudentGenderService::ALL => 'All', 'male' => 'Male', 'female' => 'Female', 'other' => 'Other / Unknown'],
            'maxWorkingDays' => AcademicAttendanceService::MAX_WORKING_DAYS,
        ]);
    }

    public function storeBulk(Request $request): RedirectResponse
    {
        $scope = $this->scope($request, true);
        $rows = $request->validate(['students' => ['required', 'array', 'min:1']])['students'];
        $this->attendance->saveBulk($scope, array_values($rows), $this->admins->current()?->id);

        return redirect()->route('academic-attendance.index', $this->query($scope))
            ->with('success', 'Academic Attendance saved successfully.');
    }

    public function storeSingle(Request $request): RedirectResponse
    {
        $scope = $this->scope($request, true);
        $data = $request->validate([
            'single_student_id' => ['required', 'integer', 'min:1'],
            'students' => ['required', 'array', 'min:1'],
        ]);
        $row = collect($data['students'])->first(fn ($item) => (int) ($item['student_id'] ?? 0) === (int) $data['single_student_id']);
        if (! is_array($row)) abort(422, 'The selected student attendance row was not submitted.');
        $this->attendance->saveOne($scope, $row, $this->admins->current()?->id);

        return redirect()->route('academic-attendance.index', $this->query($scope))
            ->with('success', 'Student Academic Attendance saved successfully.');
    }

    private function scope(Request $request, bool $required): array
    {
        $nullable = $required ? 'required' : 'nullable';
        $data = $request->validate([
            'exam_id' => [$nullable, 'integer', Rule::exists('exams', 'id')],
            'session_id' => [$nullable, 'integer', Rule::exists('session_manages', 'id')],
            'class_id' => [$nullable, 'integer', Rule::exists('class_manages', 'id')],
            'section_id' => [$nullable, 'integer', Rule::exists('section_manages', 'id')],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'gender' => ['nullable', Rule::in([StudentGenderService::ALL, 'male', 'female', 'other'])],
        ]);
        return [
            'exam_id' => isset($data['exam_id']) ? (int) $data['exam_id'] : null,
            'session_id' => isset($data['session_id']) ? (int) $data['session_id'] : null,
            'class_id' => isset($data['class_id']) ? (int) $data['class_id'] : null,
            'section_id' => isset($data['section_id']) ? (int) $data['section_id'] : null,
            'department_id' => isset($data['department_id']) ? (int) $data['department_id'] : null,
            'gender' => $this->gender->normalize($data['gender'] ?? StudentGenderService::ALL),
        ];
    }

    private function complete(array $scope): bool
    {
        return collect(['exam_id', 'session_id', 'class_id', 'section_id'])->every(fn ($key) => ! empty($scope[$key]));
    }

    private function query(array $scope): array
    {
        return array_filter($scope, fn ($value) => $value !== null && $value !== '');
    }
}
