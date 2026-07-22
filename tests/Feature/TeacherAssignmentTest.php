<?php

namespace Tests\Feature;

use App\Http\Controllers\CultivationController;
use App\Models\classManage;
use App\Models\CultivationAdmin;
use App\Models\sectionManage;
use App\Models\Subject;
use App\Models\TeacherClassSubject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TeacherAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_assignment_defaults_gender_scope_to_all(): void
    {
        $class = $this->createClass('TA Class');
        $section = $this->createSection('A');
        $subject = $this->createSubject('TA Subject');

        $payload = $this->teacherPayload([
            'userName' => 'ta-default-user',
            'userMail' => 'ta-default-user@example.test',
            'className' => [$class->id],
            'section' => [$section->id],
            'optionalGroup' => [''],
            'subject' => [$subject->id],
        ]);

        app(CultivationController::class)->saveUser(Request::create('/save/admin', 'POST', $payload));

        $teacherId = CultivationAdmin::where('adminUser', 'ta-default-user')->value('id');
        $this->assertNotNull($teacherId);

        $this->assertDatabaseHas('teacher_class_subjects', [
            'teacher_id' => $teacherId,
            'subject_id' => $subject->id,
            'gender_scope' => 'all',
        ]);
    }

    public function test_gender_scope_male_and_female_are_stored(): void
    {
        $class = $this->createClass('TA Gender Class');
        $section = $this->createSection('B');
        $maleSubject = $this->createSubject('TA Male Subject');
        $femaleSubject = $this->createSubject('TA Female Subject');

        $payload = $this->teacherPayload([
            'userName' => 'ta-gender-user',
            'userMail' => 'ta-gender-user@example.test',
            'className' => [$class->id, $class->id],
            'section' => [$section->id, $section->id],
            'optionalGroup' => ['', ''],
            'genderScope' => ['male', 'female'],
            'subject' => [$maleSubject->id, $femaleSubject->id],
        ]);

        app(CultivationController::class)->saveUser(Request::create('/save/admin', 'POST', $payload));

        $teacherId = CultivationAdmin::where('adminUser', 'ta-gender-user')->value('id');
        $this->assertNotNull($teacherId);

        $this->assertDatabaseHas('teacher_class_subjects', [
            'teacher_id' => $teacherId,
            'subject_id' => $maleSubject->id,
            'gender_scope' => 'male',
        ]);
        $this->assertDatabaseHas('teacher_class_subjects', [
            'teacher_id' => $teacherId,
            'subject_id' => $femaleSubject->id,
            'gender_scope' => 'female',
        ]);
    }

    public function test_blank_or_null_gender_scope_label_falls_back_to_all(): void
    {
        $row = new TeacherClassSubject();

        $row->gender_scope = null;
        $this->assertSame('All', $row->gender_scope_label);

        $row->gender_scope = '';
        $this->assertSame('All', $row->gender_scope_label);
    }

    private function teacherPayload(array $overrides = []): array
    {
        return array_merge([
            'adminName' => 'Teacher Assignment User',
            'userName' => 'teacher-assignment-user',
            'userMobile' => '01700000000',
            'userMail' => 'teacher-assignment-user@example.test',
            'userType' => CultivationAdmin::ROLE_TEACHER,
            'pass' => 'secret123',
            'confirmPass' => 'secret123',
            'primaryClass' => '',
            'primarySection' => '',
            'className' => [],
            'section' => [],
            'optionalGroup' => [],
            'genderScope' => [],
            'subject' => [],
        ], $overrides);
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

    private function createAdmin(array $attributes = []): CultivationAdmin
    {
        $admin = new CultivationAdmin();
        $admin->adminName = $attributes['adminName'] ?? 'Admin '.uniqid();
        $admin->adminUser = $attributes['adminUser'] ?? 'user_'.uniqid();
        $admin->userType = array_key_exists('userType', $attributes)
            ? $attributes['userType']
            : CultivationAdmin::ROLE_GENERAL;
        $admin->loginPassword = Hash::make('secret123');
        $admin->adminMobile = $attributes['adminMobile'] ?? '017'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        $admin->adminMail = $attributes['adminMail'] ?? uniqid('admin_', true).'@example.test';
        $admin->save();

        return $admin;
    }
}
