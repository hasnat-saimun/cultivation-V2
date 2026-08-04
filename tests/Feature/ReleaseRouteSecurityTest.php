<?php

namespace Tests\Feature;

use App\Models\CultivationAdmin;
use App\Models\Exam;
use App\Models\newAdmission;
use App\Models\Subject;
use App\Models\classManage;
use App\Models\sectionManage;
use App\Models\sessionManage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReleaseRouteSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_transcript_denies_guest(): void
    {
        $scope = $this->transcriptScope();
        $url = route('marksheetGenerate', ['studentId' => $scope['student']->id, 'examId' => $scope['exam']->id]);

        $this->get($url)->assertRedirect(route('adminLogin'));
    }

    public function test_single_transcript_denies_cash_admin(): void
    {
        $scope = $this->transcriptScope();
        $cash = $this->admin(CultivationAdmin::ROLE_CASH);
        $this->withSession(['cultivationAdmin' => $cash->id])->get(route('marksheetGenerate', [
            'studentId' => $scope['student']->id, 'examId' => $scope['exam']->id,
        ]))->assertForbidden();
    }

    public function test_single_transcript_allows_authorized_admin(): void
    {
        $scope = $this->transcriptScope();
        $general = $this->admin(CultivationAdmin::ROLE_GENERAL);
        $this->withSession(['cultivationAdmin' => $general->id])->get(route('marksheetGenerate', [
            'studentId' => $scope['student']->id, 'examId' => $scope['exam']->id,
        ]))
            ->assertOk()->assertViewIs('result.marksheetGenerate');
    }

    public function test_transcript_rejects_tampered_exam_scope(): void
    {
        $scope = $this->transcriptScope();
        $otherClass = new classManage(); $otherClass->className = 'Other'; $otherClass->save();
        $wrongExam = new Exam(); $wrongExam->examName = 'Wrong Class'; $wrongExam->className = (string) $otherClass->id; $wrongExam->save();
        $general = $this->admin(CultivationAdmin::ROLE_GENERAL);

        $this->withSession(['cultivationAdmin' => $general->id])->get(route('marksheetGenerate', [
            'studentId' => $scope['student']->id, 'examId' => $wrongExam->id,
        ]))->assertNotFound();
    }

    public function test_teacher_transcript_access_is_limited_to_assigned_student_scope(): void
    {
        $scope = $this->transcriptScope();
        $teacher = $this->admin(CultivationAdmin::ROLE_TEACHER);
        DB::table('teacher_class_subjects')->insert([
            'teacher_id' => $teacher->id, 'session_id' => $scope['session']->id,
            'class_id' => $scope['class']->id, 'section_id' => $scope['section']->id,
            'group_id' => null, 'subject_id' => $scope['subject']->id, 'gender_scope' => 'male',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->withSession(['cultivationAdmin' => $teacher->id])->get(route('marksheetGenerate', [
            'studentId' => $scope['student']->id, 'examId' => $scope['exam']->id,
        ]))->assertOk();

        $otherSection = new sectionManage(); $otherSection->section = 'B'; $otherSection->save();
        $otherStudent = newAdmission::create([
            'stdId' => 99002, 'fullName' => 'Unauthorized', 'sureName' => 'Student', 'gender' => '1',
            'sessName' => $scope['session']->id, 'className' => $scope['class']->id,
            'sectionName' => $otherSection->id, 'rollNumber' => '2',
        ]);
        $this->withSession(['cultivationAdmin' => $teacher->id])->get(route('marksheetGenerate', [
            'studentId' => $otherStudent->id, 'examId' => $scope['exam']->id,
        ]))->assertNotFound();
    }

    public function test_mutation_routes_are_not_get_and_rejected_requests_preserve_target(): void
    {
        $mutationActions = collect(Route::getRoutes())->filter(function ($route) {
            $action = (string) $route->getActionName();
            $name = (string) $route->getName();

            return preg_match('/@(del|delete|remove|dlt|toggle)/i', $action)
                || preg_match('/^(del|delete|remove|dlt)|toggle/i', $name);
        });

        $this->assertNotEmpty($mutationActions);
        foreach ($mutationActions as $route) {
            $this->assertNotContains('GET', $route->methods(), $route->uri().' must not mutate through GET.');
        }

        $class = new classManage(); $class->className = 'Protected Class'; $class->save();
        $url = route('delClass', ['itemId' => $class->id]);
        $this->get($url)->assertMethodNotAllowed();
        $this->assertDatabaseHas('class_manages', ['id' => $class->id]);

        $this->delete($url)->assertRedirect(route('adminLogin'));
        $this->assertDatabaseHas('class_manages', ['id' => $class->id]);

        $admin = $this->admin(CultivationAdmin::ROLE_GENERAL);
        $this->withSession(['cultivationAdmin' => $admin->id])->delete($url)->assertRedirect();
        $this->assertDatabaseMissing('class_manages', ['id' => $class->id]);
    }

    public function test_delete_component_contains_csrf_and_method_spoofing(): void
    {
        $html = Blade::render('<x-delete-action action="/secured-delete">Delete</x-delete-action>');

        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringContainsString('name="_method" value="DELETE"', $html);
        $this->assertStringContainsString('method="POST"', $html);
    }

    private function transcriptScope(): array
    {
        $session = new sessionManage(); $session->session = '2026'; $session->save();
        $class = new classManage(); $class->className = 'Ten'; $class->save();
        $section = new sectionManage(); $section->section = 'A'; $section->save();
        $exam = new Exam(); $exam->examName = 'Annual'; $exam->className = (string) $class->id; $exam->passingSystem = 2; $exam->save();
        $subject = Subject::create(['subjectName' => 'Bangla', 'subjectType' => 'Main', 'CQ' => 100]);
        $student = newAdmission::create([
            'stdId' => 99001, 'fullName' => 'Authorized', 'sureName' => 'Student', 'gender' => '1',
            'sessName' => $session->id, 'className' => $class->id,
            'sectionName' => $section->id, 'rollNumber' => '1',
        ]);
        DB::table('curriculum_subject_mappings')->insert([
            'session_id' => (string) $session->id, 'class_id' => (string) $class->id,
            'section_id' => (string) $section->id, 'department_id' => null,
            'subject_id' => $subject->id, 'mapping_type' => 'main', 'sort_order' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return compact('session', 'class', 'section', 'exam', 'subject', 'student');
    }

    private function admin(int $role): CultivationAdmin
    {
        $admin = new CultivationAdmin();
        $admin->adminName = 'Security User'; $admin->adminUser = uniqid('security_'); $admin->userType = $role;
        $admin->loginPassword = Hash::make('secret'); $admin->adminMobile = '01700000000';
        $admin->adminMail = uniqid().'@test.local'; $admin->save();

        return $admin;
    }
}
