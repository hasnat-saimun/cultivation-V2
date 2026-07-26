<?php

namespace Tests\Feature;

use App\Models\ClassManage;
use App\Models\CultivationAdmin;
use App\Models\Department;
use App\Models\ResultLifecycleEvent;
use App\Models\SectionManage;
use App\Models\SessionManage;
use App\Models\Subject;
use App\Models\TeacherClassSubject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class TeacherPortalDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_teacher_loads_dedicated_dashboard_layout(): void
    {
        $teacher = $this->teacher();

        $this->actingAs($teacher, 'teacher')
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('Teacher Portal')
            ->assertSee('Welcome, Portal Teacher')
            ->assertSee('id="teacher-shell"', false)
            ->assertSee('name="csrf-token"', false);
    }

    public function test_guest_and_admin_session_alone_cannot_access_dashboard(): void
    {
        $this->get(route('teacher.dashboard'))->assertRedirect(route('teacher.login'));

        $this->withSession(['cultivationAdmin' => 1])
            ->get(route('teacher.dashboard'))
            ->assertRedirect(route('teacher.login'));
    }

    public function test_dashboard_never_shows_another_teachers_identity_or_assignments(): void
    {
        $teacher = $this->teacher();
        $other = $this->teacher([
            'adminName' => 'Private Other Teacher',
            'adminUser' => 'OTHER-2',
            'adminMail' => 'other@example.test',
            'adminMobile' => '01700000002',
        ]);
        [$class, $section, $subject, $session, $group] = $this->academicScope('Other Private Subject');
        $this->assignment($other, $class, $section, $subject, $session, $group);

        $this->actingAs($teacher, 'teacher')
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertDontSee('Private Other Teacher')
            ->assertDontSee('OTHER-2')
            ->assertDontSee('Other Private Subject');
    }

    public function test_assignment_statistics_and_summary_are_teacher_scoped_and_distinct(): void
    {
        $teacher = $this->teacher();
        $other = $this->teacher([
            'adminUser' => 'OTHER-2',
            'adminMail' => 'other@example.test',
            'adminMobile' => '01700000002',
        ]);
        [$class, $section, $subject, $session, $group] = $this->academicScope('Mathematics');
        $this->assignment($teacher, $class, $section, $subject, $session, $group, 'male');
        $this->assignment($teacher, $class, $section, $subject, $session, $group, 'female');

        [$otherClass, $otherSection, $otherSubject, $otherSession, $otherGroup] = $this->academicScope('Other Subject', 'Other Class');
        $this->assignment($other, $otherClass, $otherSection, $otherSubject, $otherSession, $otherGroup);

        $this->actingAs($teacher, 'teacher')
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Assigned Classes', '>1<'], false)
            ->assertSeeInOrder(['Assigned Subjects', '>1<'], false)
            ->assertSeeInOrder(['Assigned Sections', '>1<'], false)
            ->assertSee('Mathematics')
            ->assertSee('Class Nine')
            ->assertSee('Section A')
            ->assertDontSee('Other Subject')
            ->assertDontSee('Other Class');
    }

    public function test_missing_optional_teacher_fields_render_professionally(): void
    {
        $teacher = $this->teacher(['adminName' => null, 'adminUser' => null]);

        $this->actingAs($teacher, 'teacher')
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('Welcome, Teacher')
            ->assertDontSee('Teacher ID:')
            ->assertDontSee('null');
    }

    public function test_unimplemented_navigation_and_actions_have_no_fake_links(): void
    {
        $teacher = $this->teacher();

        $response = $this->actingAs($teacher, 'teacher')->get(route('teacher.dashboard'));

        $response->assertOk()
            ->assertSee('Attendance')
            ->assertSee('Results')
            ->assertSee('Coming Soon')
            ->assertDontSee('href="#"', false)
            ->assertDontSee('href="/attendance"', false);
    }

    public function test_logout_is_post_csrf_protected_and_mobile_navigation_is_accessible(): void
    {
        $teacher = $this->teacher();
        $route = app('router')->getRoutes()->getByName('teacher.logout');

        $this->assertNotNull($route);
        $this->assertSame(['POST'], $route->methods());
        $this->assertContains('web', $route->middleware());

        $this->actingAs($teacher, 'teacher')
            ->get(route('teacher.dashboard'))
            ->assertSee('aria-controls="teacher-sidebar"', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('aria-label="Close navigation"', false)
            ->assertSee('name="_token"', false);
    }

    public function test_dashboard_does_not_expose_admin_navigation(): void
    {
        $teacher = $this->teacher();

        $this->actingAs($teacher, 'teacher')
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertDontSee('Configuration')
            ->assertDontSee('Admin Management')
            ->assertDontSee('Financial Management');
    }

    public function test_recent_activity_is_teacher_scoped_and_payload_is_not_rendered(): void
    {
        $teacher = $this->teacher();
        $other = $this->teacher([
            'adminUser' => 'OTHER-2',
            'adminMail' => 'other@example.test',
            'adminMobile' => '01700000002',
        ]);
        $this->event($teacher, 'draft_marks_updated', ['secret' => 'CURRENT_SECRET']);
        $this->event($other, 'subject_confirmed', ['secret' => 'OTHER_SECRET']);

        $this->actingAs($teacher, 'teacher')
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('Draft marks updated')
            ->assertDontSee('Subject result confirmed')
            ->assertDontSee('CURRENT_SECRET')
            ->assertDontSee('OTHER_SECRET');
    }

    public function test_dashboard_query_count_is_bounded_as_assignments_grow(): void
    {
        $teacher = $this->teacher();
        for ($index = 1; $index <= 15; $index++) {
            [$class, $section, $subject, $session, $group] = $this->academicScope("Subject {$index}", "Class {$index}");
            $this->assignment($teacher, $class, $section, $subject, $session, $group);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($teacher, 'teacher')->get(route('teacher.dashboard'))->assertOk();
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(8, $queryCount);
    }

    public function test_dashboard_displays_readable_groups_fallback_and_keeps_group_and_gender_scopes_separate(): void
    {
        $teacher = $this->teacher();
        [$class, $section, $subject, $session, $science] = $this->academicScope('Dashboard Subject');
        $commerce = new Department();
        $commerce->forceFill(['departmentName' => 'Business Studies']);
        $commerce->save();

        $this->assignment($teacher, $class, $section, $subject, $session, $science, 'male');
        $this->assignment($teacher, $class, $section, $subject, $session, $commerce, 'female');
        TeacherClassSubject::create([
            'teacher_id' => $teacher->id, 'session_id' => $session->id, 'class_id' => $class->id,
            'section_id' => $section->id, 'subject_id' => $subject->id, 'group_id' => null,
            'gender_scope' => 'all',
        ]);

        $response = $this->actingAs($teacher, 'teacher')->get(route('teacher.dashboard'));
        $response->assertOk()
            ->assertSee('Department / Group')
            ->assertSee('Science')
            ->assertSee('Business Studies')
            ->assertSee('All Departments')
            ->assertSee('Male')
            ->assertSee('Female');

        $this->assertSame(3, $response->viewData('assignments')->count());
        $this->assertStringNotContainsString('>'.$science->id.'<', $response->getContent());
    }

    private function teacher(array $overrides = []): CultivationAdmin
    {
        $teacher = new CultivationAdmin();
        $teacher->forceFill(array_merge([
            'adminName' => 'Portal Teacher',
            'adminUser' => 'T-1001',
            'adminMail' => 'teacher@example.test',
            'adminMobile' => '01700000001',
            'userType' => CultivationAdmin::ROLE_TEACHER,
            'loginPassword' => Hash::make('teacher-secret'),
        ], $overrides));
        $teacher->save();
        return $teacher;
    }

    private function academicScope(string $subjectName, string $className = 'Class Nine'): array
    {
        $class = new ClassManage();
        $class->forceFill(['className' => $className]);
        $class->save();

        $section = new SectionManage();
        $section->forceFill(['section' => 'Section A']);
        $section->save();

        $subject = Subject::create(['subjectName' => $subjectName]);

        $session = new SessionManage();
        $session->forceFill(['session' => '2026']);
        $session->save();

        $group = new Department();
        $group->forceFill(['departmentName' => 'Science']);
        $group->save();

        return [$class, $section, $subject, $session, $group];
    }

    private function assignment(
        CultivationAdmin $teacher,
        ClassManage $class,
        SectionManage $section,
        Subject $subject,
        SessionManage $session,
        Department $group,
        string $gender = 'all',
    ): void {
        TeacherClassSubject::create([
            'teacher_id' => $teacher->id,
            'session_id' => $session->id,
            'class_id' => $class->id,
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'group_id' => $group->id,
            'gender_scope' => $gender,
        ]);
    }

    private function event(CultivationAdmin $teacher, string $action, array $payload): void
    {
        $event = new ResultLifecycleEvent();
        $event->forceFill([
            'event_uuid' => (string) Str::uuid(),
            'actor_id' => $teacher->id,
            'actor_role' => 'teacher',
            'action' => $action,
            'entity_type' => 'subject_scope',
            'change_set' => $payload,
            'created_at' => now(),
        ]);
        $event->save();
    }
}
