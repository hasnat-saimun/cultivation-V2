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

class FourthSubjectMarksEntryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=']);
    }

    public function test_student_assigned_higher_math_appears_for_same_fourth_subject(): void
    {
        [$session, $class, $section, $science, $commerce, $exam] = $this->createAcademicScope('Class 9');
        $higherMath = $this->createSubject('Higher Mathematics', 'Optional', (string) $class->id);
        $agriculture = $this->createSubject('Agricultural Studies', 'Optional', (string) $class->id);

        $matching = $this->createStudent($session, $class, $section, $science, '1', '01', null, $higherMath->id);
        $this->createStudent($session, $class, $section, $commerce, '2', '02', null, $agriculture->id);
        $this->createStudent($session, $class, $section, $science, '1', '03', null, null);

        $response = app(MarksheetController::class)->getMarks($this->marksRequest([
            'sessionId' => $session->id,
            'classId' => $class->id,
            'groupId' => $section->id,
            'optionalGroupId' => 0,
            'gender' => 'all',
            'examId' => $exam->id,
            'subjectId' => $higherMath->id,
        ]));

        $this->assertSame([$matching->id], $response->getData()['studentList']->pluck('id')->all());
    }

    public function test_student_assigned_agriculture_is_excluded_for_higher_math(): void
    {
        [$session, $class, $section, $science, $commerce, $exam] = $this->createAcademicScope('Class 9');
        $higherMath = $this->createSubject('Higher Mathematics', 'Optional', (string) $class->id);
        $agriculture = $this->createSubject('Agricultural Studies', 'Optional', (string) $class->id);

        $this->createStudent($session, $class, $section, $science, '1', '01', null, $agriculture->id);

        $response = app(MarksheetController::class)->getMarks($this->marksRequest([
            'sessionId' => $session->id,
            'classId' => $class->id,
            'groupId' => $section->id,
            'optionalGroupId' => 0,
            'gender' => 'all',
            'examId' => $exam->id,
            'subjectId' => $higherMath->id,
        ]));

        $this->assertSame([], $response->getData()['studentList']->pluck('id')->all());
    }

    public function test_student_assigned_agriculture_appears_for_agriculture(): void
    {
        [$session, $class, $section, $science, $commerce, $exam] = $this->createAcademicScope('Class 9');
        $agriculture = $this->createSubject('Agricultural Studies', 'Optional', (string) $class->id);

        $matching = $this->createStudent($session, $class, $section, $science, '1', '01', null, $agriculture->id);

        $response = app(MarksheetController::class)->getMarks($this->marksRequest([
            'sessionId' => $session->id,
            'classId' => $class->id,
            'groupId' => $section->id,
            'optionalGroupId' => 0,
            'gender' => 'all',
            'examId' => $exam->id,
            'subjectId' => $agriculture->id,
        ]));

        $this->assertSame([$matching->id], $response->getData()['studentList']->pluck('id')->all());
    }

    public function test_student_with_null_or_invalid_fourth_subject_is_excluded_for_fourth_subject(): void
    {
        [$session, $class, $section, $science, $commerce, $exam] = $this->createAcademicScope('Class 9');
        $higherMath = $this->createSubject('Higher Mathematics', 'Optional', (string) $class->id);

        $this->createStudent($session, $class, $section, $science, '1', '01', null, null);
        $this->createStudent($session, $class, $section, $science, '2', '02', null, 999999);

        $response = app(MarksheetController::class)->getMarks($this->marksRequest([
            'sessionId' => $session->id,
            'classId' => $class->id,
            'groupId' => $section->id,
            'optionalGroupId' => 0,
            'gender' => 'all',
            'examId' => $exam->id,
            'subjectId' => $higherMath->id,
        ]));

        $this->assertSame([], $response->getData()['studentList']->pluck('id')->all());
    }

    public function test_compulsory_subject_loads_students_regardless_of_fourth_subject_assignment(): void
    {
        [$session, $class, $section, $science, $commerce, $exam] = $this->createAcademicScope('Class 9');
        $higherMath = $this->createSubject('Higher Mathematics', 'Optional', (string) $class->id);
        $bangla = $this->createSubject('Bangla', 'Theory', '0');

        $scienceStudent = $this->createStudent($session, $class, $section, $science, '1', '01', null, $higherMath->id);
        $commerceStudent = $this->createStudent($session, $class, $section, $commerce, '2', '02', null, null);

        $response = app(MarksheetController::class)->getMarks($this->marksRequest([
            'sessionId' => $session->id,
            'classId' => $class->id,
            'groupId' => $section->id,
            'optionalGroupId' => 0,
            'gender' => 'all',
            'examId' => $exam->id,
            'subjectId' => $bangla->id,
        ]));

        $this->assertSame([$scienceStudent->id, $commerceStudent->id], $response->getData()['studentList']->pluck('id')->all());
    }

    public function test_teacher_authorization_combines_with_fourth_subject_filter(): void
    {
        [$session, $class, $section, $science, $commerce, $exam] = $this->createAcademicScope('Class 9');
        $higherMath = $this->createSubject('Higher Mathematics', 'Optional', (string) $class->id);
        $teacher = $this->createAdmin(CultivationAdmin::ROLE_TEACHER);

        $scienceMale = $this->createStudent($session, $class, $section, $science, '1', '01', null, $higherMath->id);
        $this->createStudent($session, $class, $section, $science, '2', '02', null, $higherMath->id);
        $this->createStudent($session, $class, $section, $commerce, '1', '03', null, $higherMath->id);

        DB::table('teacher_class_subjects')->insert([
            'teacher_id' => $teacher->id,
            'session_id' => $session->id,
            'class_id' => $class->id,
            'section_id' => $section->id,
            'group_id' => $science->id,
            'gender_scope' => 'male',
            'subject_id' => $higherMath->id,
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
            'subjectId' => $higherMath->id,
        ]));

        $this->assertSame([$scienceMale->id], $response->getData()['studentList']->pluck('id')->all());
    }

    public function test_all_departments_returns_only_authorized_students_matching_fourth_subject(): void
    {
        [$session, $class, $section, $science, $commerce, $exam] = $this->createAcademicScope('Class 9');
        $higherMath = $this->createSubject('Higher Mathematics', 'Optional', (string) $class->id);

        $scienceMatch = $this->createStudent($session, $class, $section, $science, '1', '01', null, $higherMath->id);
        $commerceMatch = $this->createStudent($session, $class, $section, $commerce, '2', '02', null, $higherMath->id);
        $this->createStudent($session, $class, $section, $commerce, '1', '03', null, null);

        $response = app(MarksheetController::class)->getMarks($this->marksRequest([
            'sessionId' => $session->id,
            'classId' => $class->id,
            'groupId' => $section->id,
            'optionalGroupId' => 0,
            'gender' => 'all',
            'examId' => $exam->id,
            'subjectId' => $higherMath->id,
        ]));

        $this->assertSame([$scienceMatch->id, $commerceMatch->id], $response->getData()['studentList']->pluck('id')->all());
    }

    public function test_existing_marks_loading_excludes_nonmatching_fourth_subject_students(): void
    {
        [$session, $class, $section, $science, $commerce, $exam] = $this->createAcademicScope('Class 9');
        $higherMath = $this->createSubject('Higher Mathematics', 'Optional', (string) $class->id);
        $agriculture = $this->createSubject('Agricultural Studies', 'Optional', (string) $class->id);

        $matching = $this->createStudent($session, $class, $section, $science, '1', '01', null, $higherMath->id);
        $nonMatching = $this->createStudent($session, $class, $section, $commerce, '2', '02', null, $agriculture->id);

        foreach ([$matching, $nonMatching] as $student) {
            $mark = new Marksheet();
            $mark->studentId = $student->id;
            $mark->classId = $class->id;
            $mark->sessionId = (string) $session->id;
            $mark->groupId = $section->id;
            $mark->examId = $exam->id;
            $mark->subjectId = $higherMath->id;
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
            'subjectId' => $higherMath->id,
        ]));

        $this->assertSame([$matching->id], $response->getData()['studentList']->pluck('id')->all());
    }

    public function test_save_path_cannot_write_marks_for_nonmatching_fourth_subject_student(): void
    {
        [$session, $class, $section, $science, $commerce, $exam] = $this->createAcademicScope('Class 9');
        $higherMath = $this->createSubject('Higher Mathematics', 'Optional', (string) $class->id);
        $agriculture = $this->createSubject('Agricultural Studies', 'Optional', (string) $class->id);
        $teacher = $this->createAdmin(CultivationAdmin::ROLE_TEACHER);

        $matching = $this->createStudent($session, $class, $section, $science, '1', '01', null, $higherMath->id);
        $nonMatching = $this->createStudent($session, $class, $section, $science, '2', '02', null, $agriculture->id);

        DB::table('teacher_class_subjects')->insert([
            'teacher_id' => $teacher->id,
            'session_id' => $session->id,
            'class_id' => $class->id,
            'section_id' => $section->id,
            'group_id' => $science->id,
            'gender_scope' => 'all',
            'subject_id' => $higherMath->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Session::put('cultivationAdmin', $teacher->id);

        $response = app(MarksheetController::class)->confirmMarks(Request::create('/marks/add/confirm', 'POST', [
            'sessionId' => $session->id,
            'classId' => $class->id,
            'groupId' => $section->id,
            'optionalGroupId' => $science->id,
            'gender' => 'all',
            'examId' => $exam->id,
            'subjectId' => $higherMath->id,
            'studentId' => [$matching->id, $nonMatching->id],
            'cqMarks' => [70, 80],
            'mcqMarks' => ['', ''],
            'practical' => ['', ''],
        ]));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(0, \App\Models\Marksheet::count());
        $this->assertDatabaseMissing('marksheets', [
            'studentId' => $nonMatching->id,
            'subjectId' => $higherMath->id,
        ]);
    }

    public function test_malformed_subject_payload_is_rejected(): void
    {
        [$session, $class, $section, $science, $commerce, $exam] = $this->createAcademicScope('Class 9');

        $request = $this->marksRequest([
            'sessionId' => $session->id,
            'classId' => $class->id,
            'groupId' => $section->id,
            'optionalGroupId' => 0,
            'gender' => 'all',
            'examId' => $exam->id,
            'subjectId' => ['bad'],
        ]);

        try {
            app(MarksheetController::class)->getMarks($request);
            $this->fail('Expected malformed subject payload validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('subjectId', $exception->errors());
        }
    }

    public function test_optional_subject_follows_class_specific_assign_class_context(): void
    {
        [$session, $classNine, $section, $science, $commerce, $exam] = $this->createAcademicScope('Class 9');
        [$sessionTwo, $classTen] = $this->createSecondClassScope('Class 10');
        $higherMath = $this->createSubject('Higher Mathematics', 'Optional', (string) $classNine->id);

        $classNineMatch = $this->createStudent($session, $classNine, $section, $science, '1', '01', null, $higherMath->id);
        $classTenStudent = $this->createStudent($sessionTwo, $classTen, $section, $science, '1', '02', null, null);

        $responseNine = app(MarksheetController::class)->getMarks($this->marksRequest([
            'sessionId' => $session->id,
            'classId' => $classNine->id,
            'groupId' => $section->id,
            'optionalGroupId' => 0,
            'gender' => 'all',
            'examId' => $exam->id,
            'subjectId' => $higherMath->id,
        ]));
        $this->assertSame([$classNineMatch->id], $responseNine->getData()['studentList']->pluck('id')->all());

        $responseTen = app(MarksheetController::class)->getMarks($this->marksRequest([
            'sessionId' => $sessionTwo->id,
            'classId' => $classTen->id,
            'groupId' => $section->id,
            'optionalGroupId' => 0,
            'gender' => 'all',
            'examId' => $exam->id,
            'subjectId' => $higherMath->id,
        ]));
        $this->assertSame([$classTenStudent->id], $responseTen->getData()['studentList']->pluck('id')->all());
    }

    private function marksRequest(array $payload): Request
    {
        return Request::create('/marks/add/getData', 'POST', $payload);
    }

    private function createAcademicScope(string $className): array
    {
        $session = new sessionManage();
        $session->session = '2026';
        $session->save();

        $class = new classManage();
        $class->className = $className;
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

    private function createSecondClassScope(string $className): array
    {
        $session = new sessionManage();
        $session->session = '2027';
        $session->save();

        $class = new classManage();
        $class->className = $className;
        $class->save();

        return [$session, $class];
    }

    private function createSubject(string $name, string $subjectType, string $assignClass): Subject
    {
        $subject = new Subject();
        $subject->subjectName = $name;
        $subject->subjectType = $subjectType;
        $subject->assign_class = $assignClass;
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
        ?int $religiousSubjectId,
        $fourthSubjectId
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
        $student->fourthSubjectId = $fourthSubjectId;
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
