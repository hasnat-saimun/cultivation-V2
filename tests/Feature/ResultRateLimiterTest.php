<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\CultivationAdmin;
use App\Models\Exam;
use App\Models\Marksheet;
use App\Models\MarksScopeState;
use App\Models\classManage;
use App\Models\newAdmission;
use App\Models\sectionManage;
use App\Models\sessionManage;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ResultRateLimiterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_named_result_limiters_are_registered(): void
    {
        $this->assertNotNull(RateLimiter::limiter('result-draft'));
        $this->assertNotNull(RateLimiter::limiter('result-transition'));
    }

    public function test_draft_route_saves_and_marks_reopen_restores_saved_values_without_missing_limiter(): void
    {
        [$scope, $admin] = $this->scopeWithAdmin(CultivationAdmin::ROLE_GENERAL);

        $draftPayload = $this->draftPayload($scope, 80.0);
        $response = $this
            ->withSession(['cultivationAdmin' => $admin->id])
            ->post(route('marks.draft.save'), $draftPayload);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('marksheets', [
            'studentId' => (string) $scope['student']->id,
            'sessionId' => (string) $scope['session']->id,
            'classId' => (string) $scope['class']->id,
            'groupId' => (string) $scope['section']->id,
            'examId' => (string) $scope['exam']->id,
            'subjectId' => (string) $scope['subject']->id,
            'subjectMarks' => 80.0,
        ]);

        $getDataResponse = $this
            ->withSession(['cultivationAdmin' => $admin->id])
            ->post(route('getMarks'), [
                'examId' => $scope['exam']->id,
                'classId' => $scope['class']->id,
                'subjectId' => $scope['subject']->id,
                'sessionId' => $scope['session']->id,
                'groupId' => $scope['section']->id,
                'optionalGroupId' => '',
                'gender' => 'all',
            ]);

        $getDataResponse->assertStatus(200);
        $getDataResponse->assertSee((string) $scope['student']->rollNumber);
        $getDataResponse->assertSee('80');

        $state = $this->scopeState($scope);
        $this->assertSame(MarksScopeState::STATUS_DRAFT, (string) $state->status);
    }

    public function test_confirm_subject_route_transitions_without_missing_limiter_and_without_duplicate_rows(): void
    {
        [$scope, $admin] = $this->scopeWithAdmin(CultivationAdmin::ROLE_GENERAL);

        $this
            ->withSession(['cultivationAdmin' => $admin->id])
            ->post(route('marks.draft.save'), $this->draftPayload($scope, 75.0))
            ->assertStatus(302);

        $scopeStateBefore = $this->scopeState($scope);
        $markCountBefore = Marksheet::count();

        $confirmPayload = [
            'examId' => $scope['exam']->id,
            'classId' => $scope['class']->id,
            'subjectId' => $scope['subject']->id,
            'sessionId' => $scope['session']->id,
            'groupId' => $scope['section']->id,
            'optionalGroupId' => '',
            'scope_revision' => (int) $scopeStateBefore->revision,
        ];

        $confirmResponse = $this
            ->withSession(['cultivationAdmin' => $admin->id])
            ->post(route('marks.subject.confirm'), $confirmPayload);

        $confirmResponse->assertStatus(302);
        $confirmResponse->assertSessionHas('success');

        $scopeStateAfter = $this->scopeState($scope);
        $this->assertSame(MarksScopeState::STATUS_CONFIRMED, (string) $scopeStateAfter->status);
        $this->assertSame($markCountBefore, Marksheet::count());
    }

    public function test_invalid_or_unauthorized_confirm_transition_is_rejected(): void
    {
        [$scope, $generalAdmin] = $this->scopeWithAdmin(CultivationAdmin::ROLE_GENERAL);
        $cashAdmin = $this->createAdmin('cash-admin', CultivationAdmin::ROLE_CASH);

        $this
            ->withSession(['cultivationAdmin' => $generalAdmin->id])
            ->post(route('marks.draft.save'), $this->draftPayload($scope, 65.0))
            ->assertStatus(302);

        $state = $this->scopeState($scope);

        $response = $this
            ->withSession(['cultivationAdmin' => $cashAdmin->id])
            ->post(route('marks.subject.confirm'), [
                'examId' => $scope['exam']->id,
                'classId' => $scope['class']->id,
                'subjectId' => $scope['subject']->id,
                'sessionId' => $scope['session']->id,
                'groupId' => $scope['section']->id,
                'optionalGroupId' => '',
                'scope_revision' => (int) $state->revision,
            ]);

        $response->assertStatus(403);

        $state->refresh();
        $this->assertSame(MarksScopeState::STATUS_DRAFT, (string) $state->status);
    }

    public function test_repeated_requests_hit_result_draft_limiter_and_return_429(): void
    {
        [$scope, $admin] = $this->scopeWithAdmin(CultivationAdmin::ROLE_GENERAL);
        $freshAdmin = $this->createAdmin('admin-fresh-draft-'.uniqid(), CultivationAdmin::ROLE_GENERAL);

        $got429 = false;
        for ($i = 0; $i < 40; $i++) {
            $response = $this
                ->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
                ->withSession(['cultivationAdmin' => $admin->id])
                ->post(route('marks.draft.save'), []);

            if ($response->getStatusCode() === 429) {
                $got429 = true;
                break;
            }
        }

        $this->assertTrue($got429, 'Expected the result-draft limiter to eventually return HTTP 429.');

        $singleResponse = $this
            ->withServerVariables(['REMOTE_ADDR' => '198.51.100.11'])
            ->withSession(['cultivationAdmin' => $freshAdmin->id])
            ->post(route('marks.draft.save'), []);

        $this->assertNotSame(429, $singleResponse->getStatusCode());
    }

    public function test_repeated_requests_hit_result_transition_limiter_and_return_429(): void
    {
        [$scope, $admin] = $this->scopeWithAdmin(CultivationAdmin::ROLE_GENERAL);
        $freshAdmin = $this->createAdmin('admin-fresh-transition-'.uniqid(), CultivationAdmin::ROLE_GENERAL);

        $got429 = false;
        for ($i = 0; $i < 20; $i++) {
            $response = $this
                ->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
                ->withSession(['cultivationAdmin' => $admin->id])
                ->post(route('marks.subject.confirm'), []);

            if ($response->getStatusCode() === 429) {
                $got429 = true;
                break;
            }
        }

        $this->assertTrue($got429, 'Expected the result-transition limiter to eventually return HTTP 429.');

        $singleResponse = $this
            ->withServerVariables(['REMOTE_ADDR' => '198.51.100.21'])
            ->withSession(['cultivationAdmin' => $freshAdmin->id])
            ->post(route('marks.subject.confirm'), []);

        $this->assertNotSame(429, $singleResponse->getStatusCode());
    }

    private function scopeWithAdmin(int $role): array
    {
        $session = new sessionManage();
        $session->session = '2026';
        $session->save();

        $class = new classManage();
        $class->className = 'Class 8';
        $class->save();

        $section = new sectionManage();
        $section->section = 'A';
        $section->save();

        $exam = new Exam();
        $exam->examName = 'Annual';
        $exam->passingSystem = 0;
        $exam->save();

        $subject = new Subject();
        $subject->subjectName = 'Bangla';
        $subject->subjectType = 'Theory';
        $subject->assign_class = (string) $class->id;
        $subject->CQ = 100;
        $subject->MCQ = 0;
        $subject->Practical = 0;
        $subject->save();

        DB::table('curriculum_subject_mappings')->insert([
            'session_id' => (string) $session->id,
            'class_id' => (string) $class->id,
            'section_id' => (string) $section->id,
            'department_id' => null,
            'subject_id' => (int) $subject->id,
            'mapping_type' => 'main',
            'sort_order' => 1,
            'is_active' => 1,
            'source' => 'test-fixture',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $student = new newAdmission();
        $student->stdId = 7001;
        $student->fullName = 'Draft Student';
        $student->sureName = 'One';
        $student->gender = '1';
        $student->sessName = (string) $session->id;
        $student->className = (string) $class->id;
        $student->sectionName = (string) $section->id;
        $student->rollNumber = '1';
        $student->save();

        $admin = $this->createAdmin('admin-'.uniqid(), $role);

        return [
            [
                'session' => $session,
                'class' => $class,
                'section' => $section,
                'exam' => $exam,
                'subject' => $subject,
                'student' => $student,
            ],
            $admin,
        ];
    }

    private function draftPayload(array $scope, float $marks): array
    {
        return [
            'examId' => $scope['exam']->id,
            'classId' => $scope['class']->id,
            'subjectId' => $scope['subject']->id,
            'sessionId' => $scope['session']->id,
            'groupId' => $scope['section']->id,
            'optionalGroupId' => '',
            'gender' => 'all',
            'studentId' => [$scope['student']->id],
            'cqMarks' => [$marks],
            'mcqMarks' => [''],
            'practical' => [''],
            'scope_revision' => 1,
        ];
    }

    private function scopeState(array $scope): MarksScopeState
    {
        return MarksScopeState::query()
            ->where('sessionId', (string) $scope['session']->id)
            ->where('classId', (string) $scope['class']->id)
            ->where('groupId', (string) $scope['section']->id)
            ->where('examId', (string) $scope['exam']->id)
            ->where('subjectId', (string) $scope['subject']->id)
            ->firstOrFail();
    }

    private function createAdmin(string $username, int $userType): CultivationAdmin
    {
        $admin = new CultivationAdmin();
        $admin->adminName = 'Rate Limiter Admin';
        $admin->adminUser = $username;
        $admin->adminMobile = '01700000000';
        $admin->adminMail = $username.'@example.test';
        $admin->userType = $userType;
        $admin->loginPassword = Hash::make('secret123');
        $admin->save();

        return $admin;
    }
}
