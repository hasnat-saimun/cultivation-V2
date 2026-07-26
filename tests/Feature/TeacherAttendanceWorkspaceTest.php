<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ClassManage;
use App\Models\CultivationAdmin;
use App\Models\NewAdmission;
use App\Models\SectionManage;
use App\Models\SessionManage;
use App\Models\TeacherClassSubject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TeacherAttendanceWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_class_teacher_sees_fixed_primary_scope_and_sessions(): void
    {
        $s = $this->scope();
        $this->actingAs($s['teacher'], 'teacher')->get(route('teacher.attendance.index'))
            ->assertOk()->assertSee($s['class']->className)->assertSee($s['section']->section)
            ->assertSee($s['session']->session)->assertDontSee('name="classId"', false);
    }

    public function test_missing_primary_class_or_section_shows_unavailable_and_blocks_workspace(): void
    {
        foreach ([['class' => null, 'section' => null], ['class' => true, 'section' => null]] as $case) {
            $s = $this->scope();
            $s['teacher']->forceFill([
                'primary_class_id' => $case['class'] ? $s['class']->id : null,
                'primary_section_id' => null,
            ])->save();
            $this->actingAs($s['teacher'], 'teacher')->get(route('teacher.attendance.index'))
                ->assertOk()->assertSee('Attendance unavailable');
            $this->get(route('teacher.attendance.workspace', $this->query($s)))
                ->assertRedirect(route('teacher.attendance.index'));
        }
    }

    public function test_subject_assignment_alone_does_not_grant_access(): void
    {
        $s = $this->scope();
        $s['teacher']->forceFill(['primary_class_id' => null, 'primary_section_id' => null])->save();
        TeacherClassSubject::create(['teacher_id' => $s['teacher']->id, 'class_id' => $s['class']->id, 'section_id' => $s['section']->id]);
        $this->actingAs($s['teacher'], 'teacher')->get(route('teacher.attendance.workspace', $this->query($s)))
            ->assertRedirect(route('teacher.attendance.index'));
    }

    public function test_guest_and_admin_session_cannot_access_teacher_attendance(): void
    {
        $this->get(route('teacher.attendance.index'))->assertRedirect(route('teacher.login'));
        $this->withSession(['cultivationAdmin' => 1])->get(route('teacher.attendance.index'))
            ->assertRedirect(route('teacher.login'));
    }

    public function test_workspace_loads_only_session_class_section_population_and_existing_status(): void
    {
        $s = $this->scope();
        $outside = $this->student($s, 'Outside', sectionId: $this->section('B')->id);
        Attendance::create($this->identity($s, $s['student']->id) + ['session_id' => $s['session']->id, 'teacher_id' => $s['teacher']->id, 'status' => 'Late']);
        $this->actingAs($s['teacher'], 'teacher')->get(route('teacher.attendance.workspace', $this->query($s)))
            ->assertOk()->assertSee($s['student']->fullName)->assertDontSee($outside->fullName)
            ->assertSee('<option value="Late" selected>', false);
    }

    public function test_invalid_session_is_rejected(): void
    {
        $s = $this->scope();
        $this->actingAs($s['teacher'], 'teacher')
            ->get(route('teacher.attendance.workspace', ['date' => '2026-07-25', 'sessionId' => 999999]))
            ->assertRedirect(route('teacher.attendance.index'));
    }

    public function test_all_verified_statuses_save_successfully(): void
    {
        foreach (['Present', 'Absent', 'Late', 'Excused'] as $status) {
            $s = $this->scope();
            $this->actingAs($s['teacher'], 'teacher')->post(route('teacher.attendance.save'), $this->payload($s, $status))
                ->assertRedirect();
            $this->assertDatabaseHas('attendances', $this->identity($s, $s['student']->id) + ['status' => $status]);
        }
    }

    public function test_repeated_save_updates_same_identity(): void
    {
        $s = $this->scope();
        $this->actingAs($s['teacher'], 'teacher')->post(route('teacher.attendance.save'), $this->payload($s, 'Present'));
        $this->post(route('teacher.attendance.save'), $this->payload($s, 'Absent'));
        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseHas('attendances', $this->identity($s, $s['student']->id) + ['status' => 'Absent']);
    }

    public function test_unknown_status_and_duplicate_rows_are_rejected(): void
    {
        $s = $this->scope();
        $this->actingAs($s['teacher'], 'teacher')->post(route('teacher.attendance.save'), $this->payload($s, 'Holiday'))
            ->assertSessionHasErrors('status.0');
        $payload = $this->payload($s, 'Present');
        $payload['studentId'][] = $s['student']->id; $payload['status'][] = 'Absent';
        $this->post(route('teacher.attendance.save'), $payload)->assertSessionHasErrors('studentId.1');
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_students_outside_class_section_or_session_are_rejected_without_partial_write(): void
    {
        foreach (['class', 'section', 'session'] as $dimension) {
            $s = $this->scope();
            $outside = $this->student($s, "Other {$dimension}",
                sessionId: $dimension === 'session' ? $this->academicSession('2027')->id : null,
                classId: $dimension === 'class' ? $this->class('Other Class')->id : null,
                sectionId: $dimension === 'section' ? $this->section('Other Section')->id : null,
            );
            $payload = $this->payload($s, 'Present');
            $payload['studentId'][] = $outside->id; $payload['status'][] = 'Present';
            $this->actingAs($s['teacher'], 'teacher')->post(route('teacher.attendance.save'), $payload)
                ->assertSessionHasErrors('studentId');
            $this->assertDatabaseCount('attendances', 0);
        }
    }

    public function test_forged_class_section_are_ignored_and_other_teacher_is_blocked(): void
    {
        $s = $this->scope();
        $payload = $this->payload($s, 'Present') + ['classId' => 999, 'sectionId' => 999];
        $this->actingAs($s['teacher'], 'teacher')->post(route('teacher.attendance.save'), $payload);
        $this->assertDatabaseHas('attendances', $this->identity($s, $s['student']->id));
        $other = $this->scope()['teacher'];
        $this->actingAs($other, 'teacher')->get(route('teacher.attendance.workspace', $this->query($s)))
            ->assertOk()->assertDontSee($s['student']->fullName);
    }

    public function test_existing_admin_save_route_still_creates_and_updates_attendance(): void
    {
        $s = $this->scope();
        $admin = $this->teacher(); $admin->forceFill(['userType' => CultivationAdmin::ROLE_GENERAL])->save();
        $payload = ['date' => '2026-07-25', 'classId' => $s['class']->id, 'sectionId' => $s['section']->id,
            'sessionId' => $s['session']->id, 'studentId' => [$s['student']->id], 'status' => ['Present']];
        $this->withSession(['cultivationAdmin' => $admin->id])->post(route('attendanceStore'), $payload)->assertRedirect(route('attendanceIndex'));
        $payload['status'] = ['Late'];
        $this->post(route('attendanceStore'), $payload)->assertRedirect(route('attendanceIndex'));
        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseHas('attendances', ['student_id' => $s['student']->id, 'status' => 'Late']);
    }

    public function test_daily_and_monthly_admin_reports_still_render_existing_attendance(): void
    {
        $s = $this->scope();
        $admin = $this->teacher(); $admin->forceFill(['userType' => CultivationAdmin::ROLE_GENERAL])->save();
        Attendance::create($this->identity($s, $s['student']->id) + [
            'session_id' => $s['session']->id, 'teacher_id' => $admin->id, 'status' => 'Excused',
        ]);
        $this->withSession(['cultivationAdmin' => $admin->id])
            ->get(route('attendanceReport', ['date' => '2026-07-25', 'classId' => $s['class']->id,
                'sectionId' => $s['section']->id, 'sessionId' => $s['session']->id]))
            ->assertOk()->assertSee('Excused');
        $this->get(route('attendanceMonthly', ['month' => 7, 'year' => 2026, 'classId' => $s['class']->id,
            'sectionId' => $s['section']->id, 'sessionId' => $s['session']->id]))
            ->assertOk()->assertSee($s['student']->fullName);
    }

    private function scope(): array
    {
        $class = $this->class('Class Five'); $section = $this->section('Section A'); $session = $this->academicSession('2026');
        $teacher = $this->teacher();
        $teacher->forceFill(['primary_class_id' => $class->id, 'primary_section_id' => $section->id])->save();
        $scope = compact('class', 'section', 'session', 'teacher');
        return $scope + ['student' => $this->student($scope, 'Authorized Student')];
    }
    private function teacher(): CultivationAdmin { $m = new CultivationAdmin(); $m->forceFill(['adminName'=>'Teacher','adminUser'=>uniqid('t'), 'adminMail'=>uniqid().'@test.local','adminMobile'=>'01700000000','userType'=>1,'loginPassword'=>Hash::make('secret')]); $m->save(); return $m; }
    private function class(string $name): ClassManage { $m=new ClassManage(); $m->forceFill(['className'=>$name]); $m->save(); return $m; }
    private function section(string $name): SectionManage { $m=new SectionManage(); $m->forceFill(['section'=>$name]); $m->save(); return $m; }
    private function academicSession(string $name): SessionManage { $m=new SessionManage(); $m->forceFill(['session'=>$name]); $m->save(); return $m; }
    private function student(array $s, string $name, ?int $sessionId=null, ?int $classId=null, ?int $sectionId=null): NewAdmission { return NewAdmission::create(['stdId'=>random_int(10000,99999),'fullName'=>$name,'gender'=>'1','sessName'=>(string)($sessionId??$s['session']->id),'className'=>(string)($classId??$s['class']->id),'sectionName'=>(string)($sectionId??$s['section']->id),'rollNumber'=>'1']); }
    private function query(array $s): array { return ['date'=>'2026-07-25','sessionId'=>$s['session']->id]; }
    private function payload(array $s, string $status): array { return $this->query($s)+['studentId'=>[$s['student']->id],'status'=>[$status]]; }
    private function identity(array $s, int $student): array { return ['attendance_date'=>'2026-07-25','class_id'=>$s['class']->id,'section_id'=>$s['section']->id,'student_id'=>$student]; }
}
