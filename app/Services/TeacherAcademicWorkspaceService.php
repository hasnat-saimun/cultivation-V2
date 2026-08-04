<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\CultivationAdmin;
use App\Models\Exam;
use App\Models\Marksheet;
use App\Models\newAdmission;
use App\Models\Subject;
use App\Services\ResultCalculation\BoardResultCalculator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeacherAcademicWorkspaceService
{
    public function __construct(private BoardResultCalculator $calculator) {}

    public function assignments(CultivationAdmin $teacher): Collection
    {
        return DB::table('teacher_class_subjects as t')
            ->join('session_manages as se', 'se.id', '=', 't.session_id')
            ->join('class_manages as c', 'c.id', '=', 't.class_id')
            ->join('subjects as su', 'su.id', '=', 't.subject_id')
            ->leftJoin('section_manages as sc', 'sc.id', '=', 't.section_id')
            ->leftJoin('departments as d', 'd.id', '=', 't.group_id')
            ->where('t.teacher_id', $teacher->id)
            ->whereNotNull('t.session_id')->whereNotNull('t.subject_id')
            ->select('t.session_id', 'se.session as session_name', 't.class_id', 'c.className as class_name',
                't.section_id', 'sc.section as section_name', 't.group_id', 'd.departmentName as department_name',
                't.subject_id', 'su.subjectName as subject_name', 't.gender_scope')
            ->orderByDesc('t.session_id')->orderBy('t.class_id')->orderBy('t.subject_id')->get();
    }

    public function students(CultivationAdmin $teacher, ?string $search = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->authorizedQuery($teacher)->with(['classInfo:id,className', 'sectionInfo:id,section',
            'sessionInfo:id,session', 'departmentInfo:id,departmentName']);
        if ($search !== null && trim($search) !== '') {
            $term = '%'.trim($search).'%';
            $query->where(fn ($q) => $q->where('stdId', 'like', $term)->orWhere('rollNumber', 'like', $term)
                ->orWhere('fullName', 'like', $term)->orWhere('sureName', 'like', $term));
        }
        return $query->professionalOrder()
            ->paginate($perPage)->withQueryString();
    }

    public function student(CultivationAdmin $teacher, int $id): newAdmission
    {
        $student = $this->authorizedQuery($teacher)->with(['classInfo', 'sectionInfo', 'sessionInfo', 'departmentInfo'])
            ->find($id);
        if (!$student) throw ValidationException::withMessages(['student' => 'Student is outside the authorized academic scope.']);
        return $student;
    }

    public function classCards(CultivationAdmin $teacher): Collection
    {
        $assignments = $this->assignments($teacher);
        $students = $this->authorizedQuery($teacher)->get();
        return $assignments->map(function ($a) use ($students) {
            $a->student_count = $students->filter(fn ($s) =>
                $this->matches($s, $a)
            )->count();
            return $a;
        });
    }

    public function resultSummary(CultivationAdmin $teacher, newAdmission $student): Collection
    {
        $subjectIds = $this->assignments($teacher)->filter(fn ($a) => $this->matches($student, $a))
            ->pluck('subject_id')->map(fn ($id) => (int) $id)->unique();
        $marks = Marksheet::query()->where('studentId', $student->id)->whereIn('subjectId', $subjectIds)->get();
        $subjects = Subject::whereIn('id', $marks->pluck('subjectId'))->get()->keyBy('id');
        $exams = Exam::whereIn('id', $marks->pluck('examId'))->get()->keyBy('id');
        return $marks->map(function ($mark) use ($subjects, $exams) {
            $subject = $subjects->get((int) $mark->subjectId);
            $exam = $exams->get((int) $mark->examId);
            if (!$subject || !$exam) return null;
            $calculated = $this->calculator->calculateSubject(
                ['id' => $mark->studentId],
                $exam,
                $mark,
                $subject,
            );
            return (object) ['exam' => $exam->examName, 'subject' => $subject->subjectName,
                'marks' => $calculated->obtainedMarks, 'grade' => $calculated->letterGrade, 'point' => $calculated->gradePoint];
        })->filter()->values();
    }

    public function attendanceSummary(newAdmission $student): Collection
    {
        return Attendance::query()->where('student_id', $student->id)
            ->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
    }

    private function authorizedQuery(CultivationAdmin $teacher): Builder
    {
        $assignments = $this->assignments($teacher);
        if ($assignments->isEmpty()) return newAdmission::query()->whereRaw('1 = 0');
        return newAdmission::query()->where(function ($outer) use ($assignments) {
            foreach ($assignments as $a) {
                $outer->orWhere(function ($q) use ($a) {
                    $q->where('className', (string) $a->class_id)
                        ->where(fn ($session) => $session->where('sessName', (string) $a->session_id)
                            ->orWhere('sessName', (string) $a->session_name))
                        ->when($a->section_id, fn ($x) => $x->where('sectionName', (string) $a->section_id))
                        ->when($a->group_id, fn ($x) => $x->where('departmentName', (string) $a->group_id))
                        ->when(in_array($a->gender_scope, ['male', 'female', '1', '2'], true),
                            fn ($x) => $x->where('gender', in_array($a->gender_scope, ['male', '1'], true) ? '1' : '2'));
                });
            }
        });
    }

    private function matches(newAdmission $student, object $a): bool
    {
        $session = (string) $student->sessName;
        return (int) $student->className === (int) $a->class_id
            && ($session === (string) $a->session_id || $session === (string) $a->session_name)
            && (!$a->section_id || (int) $student->sectionName === (int) $a->section_id)
            && (!$a->group_id || (int) $student->departmentName === (int) $a->group_id)
            && (!in_array($a->gender_scope, ['male', 'female', '1', '2'], true)
                || (string) $student->gender === (in_array($a->gender_scope, ['male', '1'], true) ? '1' : '2'));
    }
}
