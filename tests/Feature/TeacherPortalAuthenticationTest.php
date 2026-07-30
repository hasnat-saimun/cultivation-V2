<?php

namespace Tests\Feature;

use App\Models\ClassManage;
use App\Models\CultivationAdmin;
use App\Models\Subject;
use App\Models\TeacherClassSubject;
use App\Services\CultivationAdminResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class TeacherPortalAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_loads_for_guest(): void
    {
        $this->get(route('teacher.login'))
            ->assertOk()
            ->assertSee('Teacher Portal')
            ->assertSee('Email / Teacher ID / Mobile')
            ->assertSee('autocomplete="username"', false)
            ->assertSee('autocapitalize="none"', false)
            ->assertSee('autocorrect="off"', false)
            ->assertSee('spellcheck="false"', false)
            ->assertSee('autocomplete="current-password"', false);
    }

    public function test_active_teacher_can_login_with_each_verified_identifier(): void
    {
        foreach (['teacher@example.test', 'T-1001', '01700000001'] as $identifier) {
            $teacher = $this->teacher([
                'adminUser' => 'T-1001',
                'adminMail' => 'teacher@example.test',
                'adminMobile' => '01700000001',
            ]);

            $response = $this->post(route('teacher.login.submit'), [
                'identifier' => $identifier,
                'password' => 'teacher-secret',
            ]);

            $response->assertRedirect(route('teacher.dashboard'));
            $this->assertSame($teacher->id, Auth::guard('teacher')->id());
            Auth::guard('teacher')->logout();
            $teacher->delete();
        }
    }

    public function test_teacher_id_login_normalizes_case_and_invisible_edge_characters(): void
    {
        foreach ([
            'salrnashs',
            'Salrnashs',
            'SALRNASHs',
            'salrnashs ',
            "salrnashs\u{00A0}",
            "salr\u{200B}nashs",
        ] as $identifier) {
            $teacher = $this->teacher(['adminUser' => 'salrnashs']);

            $this->post(route('teacher.login.submit'), [
                'identifier' => $identifier,
                'password' => 'teacher-secret',
            ])->assertRedirect(route('teacher.dashboard'));

            $this->assertAuthenticatedAs($teacher, 'teacher');
            Auth::guard('teacher')->logout();
            $teacher->delete();
        }
    }

    public function test_email_login_is_case_insensitive(): void
    {
        $teacher = $this->teacher(['adminMail' => 'teacher@example.test']);

        $this->post(route('teacher.login.submit'), [
            'identifier' => 'TEACHER@EXAMPLE.TEST',
            'password' => 'teacher-secret',
        ])->assertRedirect(route('teacher.dashboard'));

        $this->assertAuthenticatedAs($teacher, 'teacher');
    }

    public function test_bangladesh_mobile_login_accepts_equivalent_prefixes(): void
    {
        foreach (['01700000001', '8801700000001', '+8801700000001'] as $identifier) {
            $teacher = $this->teacher(['adminMobile' => '01700000001']);

            $this->post(route('teacher.login.submit'), [
                'identifier' => $identifier,
                'password' => 'teacher-secret',
            ])->assertRedirect(route('teacher.dashboard'));

            $this->assertAuthenticatedAs($teacher, 'teacher');
            Auth::guard('teacher')->logout();
            $teacher->delete();
        }
    }

    public function test_wrong_password_and_unknown_identifier_are_rejected_generically(): void
    {
        $this->teacher();

        foreach ([
            ['identifier' => 'T-1001', 'password' => 'wrong-password'],
            ['identifier' => 'unknown@example.test', 'password' => 'teacher-secret'],
        ] as $credentials) {
            $this->post(route('teacher.login.submit'), $credentials)
                ->assertSessionHasErrors([
                    'identifier' => 'Unable to sign in with the provided credentials.',
                ]);
            $this->assertGuest('teacher');
        }
    }

    public function test_non_teacher_account_is_ineligible_and_rejected_generically(): void
    {
        $this->teacher(['userType' => CultivationAdmin::ROLE_CASH]);

        $this->post(route('teacher.login.submit'), [
            'identifier' => 'T-1001',
            'password' => 'teacher-secret',
        ])->assertSessionHasErrors([
            'identifier' => 'Unable to sign in with the provided credentials.',
        ]);

        $this->assertGuest('teacher');
    }

    public function test_ambiguous_identifier_is_rejected_safely(): void
    {
        $this->teacher(['adminUser' => 'shared-value']);
        $this->teacher(['adminUser' => 'shared-value', 'adminMail' => 'other@example.test']);

        $this->post(route('teacher.login.submit'), [
            'identifier' => 'shared-value',
            'password' => 'teacher-secret',
        ])->assertSessionHasErrors([
            'identifier' => 'Unable to sign in with the provided credentials.',
        ]);

        $this->assertGuest('teacher');
    }

    public function test_successful_login_regenerates_session_and_reaches_dashboard(): void
    {
        $teacher = $this->teacher();
        $this->get(route('teacher.login'));
        $oldSessionId = session()->getId();

        $this->post(route('teacher.login.submit'), [
            'identifier' => $teacher->adminUser,
            'password' => 'teacher-secret',
        ])->assertRedirect(route('teacher.dashboard'));

        $this->assertNotSame($oldSessionId, session()->getId());
        $this->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee($teacher->adminName)
            ->assertSee($teacher->adminUser);
    }

    public function test_guest_dashboard_redirects_to_teacher_login(): void
    {
        $this->get(route('teacher.dashboard'))
            ->assertRedirect(route('teacher.login'));
    }

    public function test_authenticated_teacher_is_redirected_away_from_login(): void
    {
        $teacher = $this->teacher();
        $this->actingAs($teacher, 'teacher')
            ->get(route('teacher.login'))
            ->assertRedirect(route('teacher.dashboard'));
    }

    public function test_teacher_logout_invalidates_teacher_session_but_preserves_admin_session(): void
    {
        $teacher = $this->teacher();
        $admin = $this->teacher([
            'adminUser' => 'admin-user',
            'adminMail' => 'admin@example.test',
            'adminMobile' => '01800000000',
            'userType' => CultivationAdmin::ROLE_GENERAL,
        ]);

        $this->actingAs($teacher, 'teacher')
            ->withSession(['cultivationAdmin' => $admin->id])
            ->post(route('teacher.logout'))
            ->assertRedirect(route('teacher.login'))
            ->assertSessionHas('cultivationAdmin', $admin->id);

        $this->assertGuest('teacher');
    }

    public function test_admin_logout_does_not_end_teacher_guard_session(): void
    {
        $teacher = $this->teacher();

        $this->actingAs($teacher, 'teacher')
            ->withSession(['cultivationAdmin' => 999])
            ->get(route('adminLogout'))
            ->assertRedirect(route('adminLogin'))
            ->assertSessionMissing('cultivationAdmin');

        $this->assertAuthenticatedAs($teacher, 'teacher');
    }

    public function test_admin_and_teacher_sessions_cannot_substitute_for_each_other(): void
    {
        $teacher = $this->teacher();

        $this->actingAs($teacher, 'teacher')
            ->get(route('cultivationIndex'))
            ->assertRedirect(route('adminLogin'));

        Auth::guard('teacher')->logout();

        $this->withSession(['cultivationAdmin' => $teacher->id])
            ->get(route('teacher.dashboard'))
            ->assertRedirect(route('teacher.login'));
    }

    public function test_login_is_rate_limited(): void
    {
        $ip = '203.0.113.20';
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->post(route('teacher.login.submit'), [
                'identifier' => 'rate-limit@example.test',
                'password' => 'wrong-password',
            ])->assertRedirect();
        }

        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->post(route('teacher.login.submit'), [
                'identifier' => 'rate-limit@example.test',
                'password' => 'wrong-password',
            ])->assertTooManyRequests();
    }

    public function test_successful_logins_do_not_consume_shared_network_failure_quota(): void
    {
        $teacher = $this->teacher();

        for ($device = 1; $device <= 8; $device++) {
            $this->withServerVariables([
                'REMOTE_ADDR' => '203.0.113.10',
                'HTTP_USER_AGENT' => 'Supported Browser Device '.$device,
            ])->post(route('teacher.login.submit'), [
                'identifier' => $teacher->adminUser,
                'password' => 'teacher-secret',
            ])->assertRedirect(route('teacher.dashboard'));

            $this->assertAuthenticatedAs($teacher, 'teacher');
            Auth::guard('teacher')->logout();
        }
    }

    public function test_successful_login_clears_only_its_matching_failure_bucket(): void
    {
        $teacher = $this->teacher();
        $ip = '203.0.113.30';
        $matchingKey = 'teacher-login:'.hash('sha256', mb_strtolower($teacher->adminUser)).'|'.$ip;
        $otherKey = 'teacher-login:'.hash('sha256', 'another-teacher').'|'.$ip;

        RateLimiter::hit($matchingKey, 60);
        RateLimiter::hit($otherKey, 60);

        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->post(route('teacher.login.submit'), [
                'identifier' => $teacher->adminUser,
                'password' => 'teacher-secret',
            ])->assertRedirect(route('teacher.dashboard'));

        $this->assertSame(0, RateLimiter::attempts($matchingKey));
        $this->assertSame(1, RateLimiter::attempts($otherKey));
    }

    public function test_teacher_login_route_does_not_count_successes_in_route_middleware(): void
    {
        $route = app('router')->getRoutes()->getByName('teacher.login.submit');

        $this->assertNotNull($route);
        $this->assertNotContains('throttle:teacher-login', $route->middleware());
        $this->assertContains('web', $route->middleware());
        $this->assertContains('teacher.guest', $route->middleware());
    }

    public function test_https_canonical_host_is_preserved_after_successful_login(): void
    {
        $teacher = $this->teacher();

        $response = $this->withServerVariables([
            'HTTP_HOST' => 'school.example.test',
            'HTTPS' => 'on',
            'SERVER_PORT' => 443,
        ])->post('https://school.example.test/teacher/login', [
            'identifier' => $teacher->adminUser,
            'password' => 'teacher-secret',
        ]);

        $response->assertRedirect('https://school.example.test/teacher/dashboard');
        $this->assertAuthenticatedAs($teacher, 'teacher');
    }

    public function test_csrf_protection_is_active_on_login_and_logout_routes(): void
    {
        foreach (['teacher.login.submit', 'teacher.logout'] as $routeName) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertNotNull($route);
            $this->assertContains('web', $route->middleware());
        }
    }

    public function test_teacher_identity_resolves_for_result_engine_without_legacy_admin_session(): void
    {
        $teacher = $this->teacher();
        $this->actingAs($teacher, 'teacher');

        $resolved = app(CultivationAdminResolver::class)->current();

        $this->assertNotNull($resolved);
        $this->assertTrue($resolved->is($teacher));
    }

    public function test_one_teacher_does_not_inherit_another_teachers_academic_scope(): void
    {
        $assigned = $this->teacher();
        $other = $this->teacher([
            'adminUser' => 'T-2002',
            'adminMail' => 'other@example.test',
            'adminMobile' => '01700000002',
        ]);
        $class = new ClassManage();
        $class->forceFill(['className' => 'Class Nine']);
        $class->save();
        $subject = Subject::create(['subjectName' => 'Mathematics']);

        TeacherClassSubject::create([
            'teacher_id' => $assigned->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'gender_scope' => 'all',
        ]);

        $this->assertTrue($assigned->canTeachClassSubject($class->id, $subject->id));
        $this->assertFalse($other->canTeachClassSubject($class->id, $subject->id));
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
}
