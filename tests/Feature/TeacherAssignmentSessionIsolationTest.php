<?php

namespace Tests\Feature;

use App\Http\Controllers\CultivationController;
use App\Models\ClassManage;
use App\Models\CultivationAdmin;
use App\Models\Exam;
use App\Models\SectionManage;
use App\Models\SessionManage;
use App\Models\Subject;
use App\Services\MarksEntryAuthorizationService;
use App\Services\TeacherAssignmentSessionReconciliationService;
use App\Services\TeacherResultWorkspaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TeacherAssignmentSessionIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_admin_assignment_requires_an_existing_concrete_session(): void
    {
        $scope = $this->scope();

        foreach ([null, 999999] as $sessionId) {
            try {
                app(CultivationController::class)->saveUser(Request::create('/save/admin', 'POST', $this->payload($scope, [
                    'userName' => 'session-'.($sessionId ?? 'missing'),
                    'userMail' => 'session-'.($sessionId ?? 'missing').'@example.test',
                    'assignmentSessionId' => $sessionId,
                ])));
                $this->fail('Expected missing or invalid session to be rejected.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('assignmentSessionId', $exception->errors());
            }
        }

        $this->assertDatabaseCount('teacher_class_subjects', 0);
    }

    public function test_assignment_appears_once_only_in_its_stored_session_and_future_sessions_do_not_expand_it(): void
    {
        $scope = $this->scope();
        $this->save($scope);
        $service = app(TeacherResultWorkspaceService::class);

        $before = $service->assignments($scope['teacher']);
        $this->assertCount(1, $before);
        $this->assertSame($scope['session']->id, $before->first()->session_id);
        $this->academicSession('Future Session');

        $after = $service->assignments($scope['teacher']);
        $this->assertCount(1, $after);
        $this->assertSame($scope['session']->id, $after->first()->session_id);
        $this->assertDatabaseCount('teacher_class_subjects', 1);
    }

    public function test_authorization_requires_exact_stored_session_and_null_session_fails_closed(): void
    {
        $scope = $this->scope();
        $this->save($scope);
        $otherSession = $this->academicSession('Other Session');
        $authorization = app(MarksEntryAuthorizationService::class);

        $this->assertTrue($authorization->canEnterMarksFor(
            $scope['teacher'], $scope['class']->id, $scope['subject']->id,
            $scope['section']->id, null, $scope['session']->id
        ));
        $this->assertFalse($authorization->canEnterMarksFor(
            $scope['teacher'], $scope['class']->id, $scope['subject']->id,
            $scope['section']->id, null, $otherSession->id
        ));

        DB::table('teacher_class_subjects')->where('teacher_id', $scope['teacher']->id)->update(['session_id' => null]);
        $this->assertFalse($authorization->canEnterMarksFor(
            $scope['teacher'], $scope['class']->id, $scope['subject']->id,
            $scope['section']->id, null, $scope['session']->id
        ));
        $this->assertCount(0, app(TeacherResultWorkspaceService::class)->assignments($scope['teacher']));
    }

    public function test_conclusive_row_is_dry_run_then_reconciled_only_with_actor_and_backup(): void
    {
        $scope = $this->scope();
        $assignmentId = $this->legacyAssignment($scope);
        DB::table('result_lifecycle_events')->insert([
            'event_uuid' => (string) Str::uuid(),
            'actor_id' => $scope['teacher']->id, 'actor_role' => 'teacher',
            'action' => 'draft_marks_created', 'entity_type' => 'marks',
            'sessionId' => (string) $scope['session']->id, 'classId' => (string) $scope['class']->id,
            'groupId' => (string) $scope['section']->id, 'examId' => (string) $scope['exam']->id,
            'subjectId' => (string) $scope['subject']->id, 'created_at' => now(),
        ]);

        $this->assertSame(0, Artisan::call('teacher-assignment:reconcile-sessions', [
            '--assignment' => [$assignmentId],
        ]));
        $this->assertDatabaseHas('teacher_class_subjects', ['id' => $assignmentId, 'session_id' => null]);

        $backup = tempnam(sys_get_temp_dir(), 'teacher-assignment-backup-');
        file_put_contents($backup, 'verified test backup');
        $this->assertSame(0, Artisan::call('teacher-assignment:reconcile-sessions', [
            '--assignment' => [$assignmentId],
            '--execute' => true,
            '--actor' => $scope['admin']->id,
            '--backup' => $backup,
        ]));
        $this->assertDatabaseHas('teacher_class_subjects', [
            'id' => $assignmentId, 'session_id' => $scope['session']->id,
        ]);
        unlink($backup);
    }

    public function test_ambiguous_row_is_not_automatically_changed(): void
    {
        $scope = $this->scope();
        $assignmentId = $this->legacyAssignment($scope);
        $backup = tempnam(sys_get_temp_dir(), 'teacher-assignment-backup-');
        file_put_contents($backup, 'verified test backup');

        $this->assertSame(0, Artisan::call('teacher-assignment:reconcile-sessions', [
            '--execute' => true,
            '--actor' => $scope['admin']->id,
            '--backup' => $backup,
        ]));
        $this->assertDatabaseHas('teacher_class_subjects', ['id' => $assignmentId, 'session_id' => null]);
        unlink($backup);
    }

    public function test_manual_reconciliation_rejects_gender_collision_in_target_session(): void
    {
        $scope = $this->scope();
        $legacyId = $this->legacyAssignment($scope, 'all');
        DB::table('teacher_class_subjects')->insert([
            'teacher_id' => $this->teacher('Other Teacher', 'other-teacher')->id,
            'session_id' => $scope['session']->id,
            'class_id' => $scope['class']->id,
            'section_id' => $scope['section']->id,
            'group_id' => null,
            'subject_id' => $scope['subject']->id,
            'gender_scope' => 'male',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $backup = tempnam(sys_get_temp_dir(), 'teacher-assignment-backup-');
        file_put_contents($backup, 'verified test backup');

        $this->expectException(ValidationException::class);
        try {
            app(TeacherAssignmentSessionReconciliationService::class)->reconcile(
                $legacyId, $scope['session']->id, $scope['admin'], $backup
            );
        } finally {
            unlink($backup);
        }
    }

    private function scope(): array
    {
        $session = $this->academicSession('Session A');
        $class = new ClassManage(); $class->className = 'Class C1'; $class->save();
        $section = new SectionManage(); $section->section = 'Section A'; $section->save();
        $subject = new Subject(); $subject->subjectName = 'Mathematics'; $subject->subjectType = 'Theory'; $subject->save();
        $exam = new Exam(); $exam->examName = 'Annual'; $exam->className = (string) $class->id;
        $exam->baseMark = 100; $exam->passingSystem = 2; $exam->save();

        return [
            'session' => $session, 'class' => $class, 'section' => $section,
            'subject' => $subject, 'exam' => $exam,
            'teacher' => $this->teacher('Session Teacher', 'session-teacher'),
            'admin' => $this->teacher('General Admin', 'general-admin', CultivationAdmin::ROLE_GENERAL),
        ];
    }

    private function save(array $scope): void
    {
        app(CultivationController::class)->saveUser(Request::create('/save/admin', 'POST', $this->payload($scope)));
    }

    private function payload(array $scope, array $override = []): array
    {
        return array_merge([
            'userId' => $scope['teacher']->id,
            'adminName' => $scope['teacher']->adminName,
            'userName' => $scope['teacher']->adminUser,
            'userMobile' => $scope['teacher']->adminMobile,
            'userMail' => $scope['teacher']->adminMail,
            'userType' => CultivationAdmin::ROLE_TEACHER,
            'pass' => '', 'confirmPass' => '',
            'assignmentSessionId' => $scope['session']->id,
            'primaryClass' => '', 'primarySection' => '',
            'className' => [$scope['class']->id],
            'section' => [$scope['section']->id],
            'optionalGroup' => [''],
            'genderScope' => ['all'],
            'subject' => [$scope['subject']->id],
        ], $override);
    }

    private function legacyAssignment(array $scope, string $gender = 'all'): int
    {
        return (int) DB::table('teacher_class_subjects')->insertGetId([
            'teacher_id' => $scope['teacher']->id, 'session_id' => null,
            'class_id' => $scope['class']->id, 'section_id' => $scope['section']->id,
            'group_id' => null, 'subject_id' => $scope['subject']->id,
            'gender_scope' => $gender, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function academicSession(string $name): SessionManage
    {
        $session = new SessionManage(); $session->session = $name; $session->save(); return $session;
    }

    private function teacher(string $name, string $user, int $role = CultivationAdmin::ROLE_TEACHER): CultivationAdmin
    {
        $teacher = new CultivationAdmin();
        $teacher->adminName = $name; $teacher->adminUser = $user;
        $teacher->adminMail = $user.'@example.test'; $teacher->adminMobile = '017'.random_int(10000000, 99999999);
        $teacher->userType = $role; $teacher->loginPassword = Hash::make('secret'); $teacher->save();
        return $teacher;
    }
}
