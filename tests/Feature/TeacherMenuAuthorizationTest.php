<?php

namespace Tests\Feature;

use App\Models\classManage;
use App\Models\CultivationAdmin;
use App\Models\newAdmission;
use App\Models\sectionManage;
use App\Models\sessionManage;
use App\Models\Subject;
use App\Services\CultivationAdminResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class TeacherMenuAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=']);
    }

    public function test_non_class_teacher_sees_marks_menu_but_not_attendance_menu(): void
    {
        $teacher = $this->createAdmin(CultivationAdmin::ROLE_TEACHER);
        $class = $this->createClass('Class 8');
        $subject = $this->createSubject('Bangla');

        DB::table('teacher_subjects')->insert([
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('teacher_classes')->insert([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Session::put('cultivationAdmin', $teacher->id);

        $html = view('cultivation.teacherMenu')->render();

        $this->assertStringContainsString('Marks Entry', $html);
        $this->assertStringNotContainsString('Attendance Management', $html);
        $this->assertStringNotContainsString('Mark Attendance', $html);
    }

    public function test_non_class_teacher_direct_attendance_url_is_denied(): void
    {
        $teacher = $this->createAdmin(CultivationAdmin::ROLE_TEACHER);
        Session::put('cultivationAdmin', $teacher->id);

        $response = $this->get(route('attendanceIndex'));

        $response->assertRedirect(route('cultivationIndex'));
        $response->assertSessionHas('error');
    }

    public function test_assigned_class_teacher_sees_attendance_menu(): void
    {
        $teacher = $this->createAdmin(CultivationAdmin::ROLE_TEACHER);
        $class = $this->createClass('Class 8');
        $section = $this->createSection('A');
        $teacher->primary_class_id = $class->id;
        $teacher->primary_section_id = $section->id;
        $teacher->save();

        Session::put('cultivationAdmin', $teacher->id);

        $html = view('cultivation.teacherMenu')->render();

        $this->assertStringContainsString('Attendance Management', $html);
        $this->assertStringContainsString('Mark Attendance', $html);
    }

    public function test_assigned_class_teacher_cannot_access_other_class_or_section_attendance_contexts(): void
    {
        $teacher = $this->createAdmin(CultivationAdmin::ROLE_TEACHER);
        $classA = $this->createClass('Class 8');
        $classB = $this->createClass('Class 9');
        $sectionA = $this->createSection('A');
        $sectionB = $this->createSection('B');
        $session = $this->createSession();

        $teacher->primary_class_id = $classA->id;
        $teacher->primary_section_id = $sectionA->id;
        $teacher->save();

        $this->createStudent($session, $classA, $sectionA, '1001');

        Session::put('cultivationAdmin', $teacher->id);

        $ok = $this->post(route('attendanceFetch'), [
            'date' => now()->toDateString(),
            'classId' => $classA->id,
            'sessionId' => $session->id,
            'sectionId' => $sectionA->id,
        ]);
        $ok->assertStatus(200);

        $wrongClass = $this->post(route('attendanceFetch'), [
            'date' => now()->toDateString(),
            'classId' => $classB->id,
            'sessionId' => $session->id,
            'sectionId' => $sectionA->id,
        ]);
        $wrongClass->assertRedirect();
        $wrongClass->assertSessionHas('error');

        $wrongSection = $this->post(route('attendanceFetch'), [
            'date' => now()->toDateString(),
            'classId' => $classA->id,
            'sessionId' => $session->id,
            'sectionId' => $sectionB->id,
        ]);
        $wrongSection->assertRedirect();
        $wrongSection->assertSessionHas('error');
    }

    public function test_admin_access_to_attendance_is_unchanged(): void
    {
        $admin = $this->createAdmin(CultivationAdmin::ROLE_GENERAL);
        Session::put('cultivationAdmin', $admin->id);

        $response = $this->get(route('attendanceIndex'));

        $response->assertStatus(200);
    }

    public function test_marks_auth_and_menu_render_do_not_write_assignments(): void
    {
        $teacher = $this->createAdmin(CultivationAdmin::ROLE_TEACHER);
        $class = $this->createClass('Class 8');
        $subject = $this->createSubject('Science');

        DB::table('teacher_subjects')->insert([
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('teacher_classes')->insert([
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Session::put('cultivationAdmin', $teacher->id);

        $beforeTs = DB::table('teacher_subjects')->count();
        $beforeTc = DB::table('teacher_classes')->count();
        $beforeTcs = DB::table('teacher_class_subjects')->count();

        $this->postJson(route('api.marks.subjects'), [
            'classId' => $class->id,
        ])->assertOk();

        view('cultivation.teacherMenu')->render();

        $this->assertSame($beforeTs, DB::table('teacher_subjects')->count());
        $this->assertSame($beforeTc, DB::table('teacher_classes')->count());
        $this->assertSame($beforeTcs, DB::table('teacher_class_subjects')->count());
    }

    public function test_current_admin_resolver_matches_session_admin_id(): void
    {
        $teacher = $this->createAdmin(CultivationAdmin::ROLE_TEACHER);

        Session::put('cultivationAdmin', $teacher->id);

        $resolver = app(CultivationAdminResolver::class);
        $resolved = $resolver->current();

        $this->assertNotNull($resolved);
        $this->assertSame($teacher->id, $resolved->id);
        $this->assertSame($teacher->id, $resolver->currentSessionAdminId());
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

    private function createClass(string $name): classManage
    {
        $class = new classManage();
        $class->className = $name;
        $class->save();

        return $class;
    }

    private function createSection(string $name): sectionManage
    {
        $section = new sectionManage();
        $section->section = $name;
        $section->save();

        return $section;
    }

    private function createSubject(string $name): Subject
    {
        $subject = new Subject();
        $subject->subjectName = $name;
        $subject->subjectType = 'Theory';
        $subject->save();

        return $subject;
    }

    private function createSession(string $name = '2026'): sessionManage
    {
        $session = new sessionManage();
        $session->session = $name;
        $session->save();

        return $session;
    }

    private function createStudent(sessionManage $session, classManage $class, sectionManage $section, string $roll): newAdmission
    {
        $student = new newAdmission();
        $student->stdId = (string) random_int(1000, 999999);
        $student->fullName = 'Student '.$roll;
        $student->sureName = $roll;
        $student->gender = '1';
        $student->sessName = (string) $session->id;
        $student->className = $class->id;
        $student->sectionName = $section->id;
        $student->rollNumber = $roll;
        $student->save();

        return $student;
    }
}
