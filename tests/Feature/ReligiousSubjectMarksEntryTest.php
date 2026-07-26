<?php

namespace Tests\Feature;

use App\Http\Controllers\MarksheetController;
use App\Models\classManage;
use App\Models\CultivationAdmin;
use App\Models\Department;
use App\Models\Exam;
use App\Models\Marksheet;
use App\Models\newAdmission;
use App\Models\sectionManage;
use App\Models\sessionManage;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReligiousSubjectMarksEntryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=']);
    }

    public function test_religious_subject_loads_only_students_with_exact_assigned_subject_id(): void
    {
        [$session, $class, $section, $science, $commerce, $exam] = $this->createAcademicScope();
        $islamic = $this->createSubject('Islam and Moral Education', true);
        $hindu = $this->createSubject('Hindu Religion', true);

        $matching = $this->createStudent($session, $class, $section, $science, '1', '01', $islamic->id);
        $this->createStudent($session, $class, $section, $science, '2', '02', $hindu->id);
        $this->createStudent($session, $class, $section, $commerce, '1', '03', null);

        $response = app(MarksheetController::class)->getMarks($this->marksRequest([
            'sessionId' => $session->id,
            'classId' => $class->id,
            'groupId' => $section->id,
            'optionalGroupId' => 0,
            'gender' => 'all',
            'examId' => $exam->id,
            'subjectId' => $islamic->id,
        ]));

        $this->assertSame([$matching->id], $response->getData()['studentList']->pluck('id')->all());
    }

    public function test_non_religious_subject_does_not_filter_by_student_religious_subject_assignment(): void
    {
        [$session, $class, $section, $science, $commerce, $exam] = $this->createAcademicScope();
        $islamic = $this->createSubject('Islam and Moral Education', true);
        $general = $this->createSubject('Bangla', false);

        $scienceStudent = $this->createStudent($session, $class, $section, $science, '1', '01', $islamic->id);
        $commerceStudent = $this->createStudent($session, $class, $section, $commerce, '2', '02', null);

        $response = app(MarksheetController::class)->getMarks($this->marksRequest([
            'sessionId' => $session->id,
            'classId' => $class->id,
            'groupId' => $section->id,
            'optionalGroupId' => 0,
            'gender' => 'all',
            'examId' => $exam->id,
            'subjectId' => $general->id,
        ]));

        $this->assertSame([$scienceStudent->id, $commerceStudent->id], $response->getData()['studentList']->pluck('id')->all());
    }

    public function test_teacher_assignment_gender_and_religious_subject_filters_combine_correctly(): void
    {
        [$session, $class, $section, $science, $commerce, $exam] = $this->createAcademicScope();
        $islamic = $this->createSubject('Islam and Moral Education', true);
        $teacher = $this->createAdmin(CultivationAdmin::ROLE_TEACHER);

        $scienceMale = $this->createStudent($session, $class, $section, $science, '1', '01', $islamic->id);
        $this->createStudent($session, $class, $section, $science, '2', '02', $islamic->id);
        $this->createStudent($session, $class, $section, $commerce, '1', '03', $islamic->id);

        DB::table('teacher_class_subjects')->insert([
            'teacher_id' => $teacher->id,
            'session_id' => $session->id,
            'class_id' => $class->id,
            'section_id' => $section->id,
            'group_id' => $science->id,
            'gender_scope' => 'male',
            'subject_id' => $islamic->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Session::put('cultivationAdmin', $teacher->id);

        $response = app(MarksheetController::class)->getMarks($this->marksRequest([
            'sessionId' => $session->id,
            'classId' => $class->id,
            'groupId' => $section->id,
            'optionalGroupId' => $science->id,
            'gender' => 'all',
            'examId' => $exam->id,
            'subjectId' => $islamic->id,
        ]));

        $this->assertSame([$scienceMale->id], $response->getData()['studentList']->pluck('id')->all());
    }

    public function test_all_departments_still_respects_each_students_assigned_religious_subject(): void
    {
        [$session, $class, $section, $science, $commerce, $exam] = $this->createAcademicScope();
        $islamic = $this->createSubject('Islam and Moral Education', true);
        $hindu = $this->createSubject('Hindu Religion', true);

        $scienceMatch = $this->createStudent($session, $class, $section, $science, '1', '01', $islamic->id);
        $commerceMatch = $this->createStudent($session, $class, $section, $commerce, '2', '02', $islamic->id);
        $this->createStudent($session, $class, $section, $commerce, '1', '03', $hindu->id);

        $response = app(MarksheetController::class)->getMarks($this->marksRequest([
            'sessionId' => $session->id,
            'classId' => $class->id,
            'groupId' => $section->id,
            'optionalGroupId' => 0,
            'gender' => 'all',
            'examId' => $exam->id,
            'subjectId' => $islamic->id,
        ]));

        $this->assertSame([$scienceMatch->id, $commerceMatch->id], $response->getData()['studentList']->pluck('id')->all());
    }

    public function test_existing_marks_loading_excludes_students_with_nonmatching_religious_subject(): void
    {
        [$session, $class, $section, $science, $commerce, $exam] = $this->createAcademicScope();
        $islamic = $this->createSubject('Islam and Moral Education', true);
        $hindu = $this->createSubject('Hindu Religion', true);

        $matching = $this->createStudent($session, $class, $section, $science, '1', '01', $islamic->id);
        $nonMatching = $this->createStudent($session, $class, $section, $commerce, '2', '02', $hindu->id);

        foreach ([$matching, $nonMatching] as $student) {
            $mark = new Marksheet();
            $mark->studentId = $student->id;
            $mark->classId = $class->id;
            $mark->sessionId = (string) $session->id;
            $mark->groupId = $section->id;
            $mark->examId = $exam->id;
            $mark->subjectId = $islamic->id;
            $mark->subjectMarks = 50;
            $mark->totalMarks = 50;
            $mark->laterGrade = 'B';
            $mark->gradePoint = 3;
            $mark->save();
        }

        $response = app(MarksheetController::class)->getMarks($this->marksRequest([
            'sessionId' => $session->id,
            'classId' => $class->id,
            'groupId' => $section->id,
            'optionalGroupId' => 0,
            'gender' => 'all',
            'examId' => $exam->id,
            'subjectId' => $islamic->id,
        ]));

        $this->assertSame([$matching->id], $response->getData()['studentList']->pluck('id')->all());
    }

    public function test_teacher_cannot_save_marks_for_student_with_nonmatching_religious_subject(): void
    {
        [$session, $class, $section, $science, $commerce, $exam] = $this->createAcademicScope();
        $islamic = $this->createSubject('Islam and Moral Education', true);
        $hindu = $this->createSubject('Hindu Religion', true);
        $teacher = $this->createAdmin(CultivationAdmin::ROLE_TEACHER);

        $matching = $this->createStudent($session, $class, $section, $science, '1', '01', $islamic->id);
        $nonMatching = $this->createStudent($session, $class, $section, $science, '2', '02', $hindu->id);

        DB::table('teacher_class_subjects')->insert([
            'teacher_id' => $teacher->id,
            'session_id' => $session->id,
            'class_id' => $class->id,
            'section_id' => $section->id,
            'group_id' => $science->id,
            'gender_scope' => 'all',
            'subject_id' => $islamic->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Session::put('cultivationAdmin', $teacher->id);

        $response = app(MarksheetController::class)->confirmMarks(Request::create('/marks/add/confirm', 'POST', [
            'sessionId' => $session->id,
            'classId' => $class->id,
            'groupId' => $section->id,
            'optionalGroupId' => 0,
            'gender' => 'all',
            'examId' => $exam->id,
            'subjectId' => $islamic->id,
            'studentId' => [$matching->id, $nonMatching->id],
            'cqMarks' => [70, 80],
            'mcqMarks' => ['', ''],
            'practical' => ['', ''],
        ]));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(0, \App\Models\Marksheet::count());
        $this->assertDatabaseMissing('marksheets', [
            'studentId' => $nonMatching->id,
            'subjectId' => $islamic->id,
        ]);
    }

    public function test_malformed_religious_subject_payload_does_not_broaden_access(): void
    {
        [$session, $class, $section, $science, $commerce, $exam] = $this->createAcademicScope();
        $this->createSubject('Bangla', false);

        $this->createStudent($session, $class, $section, $science, '1', '01', null);
        $this->createStudent($session, $class, $section, $commerce, '2', '02', null);

        $request = $this->marksRequest([
            'sessionId' => $session->id,
            'classId' => $class->id,
            'groupId' => $section->id,
            'optionalGroupId' => 0,
            'gender' => 'all',
            'examId' => $exam->id,
            'subjectId' => 999999,
        ]);

        try {
            app(MarksheetController::class)->getMarks($request);
            $this->fail('Expected invalid subject validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('subjectId', $exception->errors());
        }
    }

    private function marksRequest(array $payload): Request
    {
        return Request::create('/marks/add/getData', 'POST', $payload);
    }

    private function createAcademicScope(): array
    {
        $session = new sessionManage();
        $session->session = '2026';
        $session->save();

        $class = new classManage();
        $class->className = 'Class 9';
        $class->save();

        $section = new sectionManage();
        $section->section = 'A';
        $section->save();

        $science = new Department();
        $science->departmentName = 'Science';
        $science->save();

        $commerce = new Department();
        $commerce->departmentName = 'Commerce';
        $commerce->save();

        $exam = new Exam();
        $exam->examName = 'Annual';
        $exam->passingSystem = 0;
        $exam->save();

        return [$session, $class, $section, $science, $commerce, $exam];
    }

    private function createSubject(string $name, bool $isReligious): Subject
    {
        $subject = new Subject();
        $subject->subjectName = $name;
        $subject->subjectType = 'Theory';
        $subject->isReligious = $isReligious ? 1 : 0;
        $subject->save();

        return $subject;
    }

    private function createStudent(
        sessionManage $session,
        classManage $class,
        sectionManage $section,
        Department $department,
        string $gender,
        string $roll,
        ?int $religiousSubjectId
    ): newAdmission {
        $student = new newAdmission();
        $student->stdId = (string) random_int(1000, 999999);
        $student->fullName = 'Student';
        $student->sureName = $roll;
        $student->gender = $gender;
        $student->sessName = (string) $session->id;
        $student->className = $class->id;
        $student->sectionName = $section->id;
        $student->departmentName = $department->id;
        $student->religiousSubjectId = $religiousSubjectId;
        $student->rollNumber = $roll;
        $student->save();

        return $student;
    }

    private function createAdmin(int $role): CultivationAdmin
    {
        $admin = new CultivationAdmin();
        $admin->adminName = 'Admin '.uniqid();
        $admin->adminUser = 'user_'.uniqid();
        $admin->userType = $role;
        $admin->loginPassword = Hash::make('secret123');
        $admin->adminMobile = '01700000000';
        $admin->adminMail = uniqid('admin_', true).'@example.test';
        $admin->save();

        return $admin;
    }
}
