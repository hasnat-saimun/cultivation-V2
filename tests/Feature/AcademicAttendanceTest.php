<?php

namespace Tests\Feature;

use App\Models\AcademicAttendance;
use App\Models\CultivationAdmin;
use App\Models\newAdmission;
use App\Services\AcademicAttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class AcademicAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_create_update_and_bidirectional_contract(): void
    {
        [$scope, $students] = $this->fixture(1);
        $service = app(AcademicAttendanceService::class);

        $present = $service->synchronize(120, 112, 'present_days');
        $this->assertSame(['working_days' => 120, 'present_days' => 112, 'absent_days' => 8], $present);
        $record = $service->saveOne($scope, ['student_id' => $students[0]->id] + $present, 7);
        $this->assertSame(7, $record->created_by);

        $absent = $service->synchronize(120, 5, 'absent_days');
        $updated = $service->saveOne($scope, ['student_id' => $students[0]->id] + $absent, 8);
        $this->assertSame(115, $updated->present_days);
        $this->assertSame(5, $updated->absent_days);
        $this->assertSame(8, $updated->updated_by);
        $this->assertDatabaseCount('academic_attendances', 1);
    }

    public function test_bulk_create_update_reload_and_unique_scope(): void
    {
        [$scope, $students] = $this->fixture(2);
        $service = app(AcademicAttendanceService::class);
        $rows = $students->map(fn ($student, $i) => ['student_id' => $student->id, 'working_days' => 100, 'present_days' => 90 + $i, 'absent_days' => 10 - $i])->all();
        $service->saveBulk($scope, $rows, 1);
        $rows[0]['present_days'] = 95; $rows[0]['absent_days'] = 5;
        $service->saveBulk($scope, $rows, 1);

        $records = $service->records($scope, $students);
        $this->assertCount(2, $records);
        $this->assertSame(95, $records[$students[0]->id]->present_days);
        $this->assertDatabaseCount('academic_attendances', 2);
        $this->expectException(\Illuminate\Database\QueryException::class);
        AcademicAttendance::create($records->first()->only((new AcademicAttendance)->getFillable()));
    }

    #[DataProvider('invalidRows')]
    public function test_invalid_or_tampered_arithmetic_is_rejected(array $row): void
    {
        [$scope, $students] = $this->fixture(1);
        $row['student_id'] = $students[0]->id;
        $this->expectException(ValidationException::class);
        app(AcademicAttendanceService::class)->saveOne($scope, $row, 1);
    }

    public static function invalidRows(): array
    {
        return [
            'working zero' => [['working_days' => 0, 'present_days' => 0, 'absent_days' => 0]],
            'present over total' => [['working_days' => 100, 'present_days' => 101, 'absent_days' => 0]],
            'absent over total' => [['working_days' => 100, 'present_days' => 0, 'absent_days' => 101]],
            'mismatch' => [['working_days' => 100, 'present_days' => 90, 'absent_days' => 9]],
            'negative' => [['working_days' => 100, 'present_days' => -1, 'absent_days' => 101]],
        ];
    }

    public function test_bulk_invalid_row_rolls_back_all_rows(): void
    {
        [$scope, $students] = $this->fixture(2);
        try {
            app(AcademicAttendanceService::class)->saveBulk($scope, [
                ['student_id' => $students[0]->id, 'working_days' => 100, 'present_days' => 90, 'absent_days' => 10],
                ['student_id' => $students[1]->id, 'working_days' => 100, 'present_days' => 90, 'absent_days' => 9],
            ], 1);
            $this->fail('Expected validation failure.');
        } catch (ValidationException) {}
        $this->assertDatabaseCount('academic_attendances', 0);
    }

    public function test_out_of_scope_student_is_rejected(): void
    {
        [$scope] = $this->fixture(1);
        $outside = newAdmission::create(['stdId' => 999, 'sessName' => $scope['session_id'], 'className' => 999, 'sectionName' => $scope['section_id']]);
        $this->expectException(ValidationException::class);
        app(AcademicAttendanceService::class)->saveOne($scope, ['student_id' => $outside->id, 'working_days' => 10, 'present_days' => 8, 'absent_days' => 2], 1);
    }

    public function test_transcript_contract_hides_missing_and_returns_saved_values(): void
    {
        [$scope, $students] = $this->fixture(1);
        $service = app(AcademicAttendanceService::class);
        $this->assertNull($service->forTranscript($students[0], $scope['exam_id']));
        $service->saveOne($scope, ['student_id' => $students[0]->id, 'working_days' => 120, 'present_days' => 112, 'absent_days' => 8], 1);
        $this->assertSame(['workingDays' => 120, 'presentDays' => 112, 'absentDays' => 8], $service->forTranscript($students[0], $scope['exam_id']));

        $shown = view('result.partials.academic-attendance', ['attendance' => $service->forTranscript($students[0], $scope['exam_id'])])->render();
        $hidden = view('result.partials.academic-attendance', ['attendance' => null])->render();
        $this->assertStringContainsString('Academic Attendance', $shown);
        $this->assertStringContainsString('112', $shown);
        $this->assertStringNotContainsString('Academic Attendance', $hidden);
    }

    public function test_all_departments_persists_the_students_effective_department_scope(): void
    {
        [$scope, $students] = $this->fixture(1);
        $expectedDepartment = $scope['department_id'];
        $scope['department_id'] = null;
        app(AcademicAttendanceService::class)->saveOne($scope, [
            'student_id' => $students[0]->id, 'working_days' => 50, 'present_days' => 45, 'absent_days' => 5,
        ], 1);
        $this->assertDatabaseHas('academic_attendances', ['student_id' => $students[0]->id, 'department_id' => $expectedDepartment]);
        $this->assertSame(45, app(AcademicAttendanceService::class)->forTranscript($students[0], $scope['exam_id'])['presentDays']);
    }

    public function test_admin_routes_are_protected_and_scope_page_uses_professional_population(): void
    {
        [$scope, $students] = $this->fixture(2);
        $this->get(route('academic-attendance.index'))->assertRedirect();
        $adminId = DB::table('cultivation_admins')->insertGetId(['adminName' => 'General', 'adminUser' => 'general', 'userType' => 3, 'created_at' => now(), 'updated_at' => now()]);
        $admin = CultivationAdmin::findOrFail($adminId);
        $response = $this->withSession(['cultivationAdmin' => $admin->id])->get(route('academic-attendance.index', $scope));
        $response->assertOk()->assertViewHas('students', fn ($items) => $items->pluck('id')->all() === $students->pluck('id')->all());
    }

    public function test_academic_population_sql_order_keeps_males_before_females_then_numeric_roll(): void
    {
        [$scope, $students] = $this->fixture(4);
        $values = [
            ['gender' => '2', 'rollNumber' => '1', 'stdId' => 26000001],
            ['gender' => '1', 'rollNumber' => '2', 'stdId' => 26000002],
            ['gender' => '2', 'rollNumber' => '2', 'stdId' => 26000003],
            ['gender' => '1', 'rollNumber' => '1', 'stdId' => 26000004],
        ];
        foreach ($students as $index => $student) $student->update($values[$index]);

        $ordered = app(AcademicAttendanceService::class)->students($scope)->get();
        $this->assertSame(['1:1', '1:2', '2:1', '2:2'], $ordered->map(fn ($student) => $student->gender.':'.(int) $student->rollNumber)->all());
        $this->assertStringContainsString('CASE LOWER(TRIM', app(AcademicAttendanceService::class)->students($scope)->toSql());
    }

    public function test_split_source_ids_are_deduplicated_then_globally_requeried_and_ordered(): void
    {
        [$scope, $students] = $this->fixture(9);
        $secondDepartment = DB::table('departments')->insertGetId(['departmentName' => 'Business', 'created_at' => now(), 'updated_at' => now()]);
        $values = [
            ['gender' => '1', 'rollNumber' => '1', 'stdId' => 4101, 'departmentName' => $scope['department_id']],
            ['gender' => '1', 'rollNumber' => '2', 'stdId' => 4102, 'departmentName' => $scope['department_id']],
            ['gender' => '1', 'rollNumber' => '10', 'stdId' => 4110, 'departmentName' => $scope['department_id']],
            ['gender' => '2', 'rollNumber' => '1', 'stdId' => 4201, 'departmentName' => $scope['department_id']],
            ['gender' => '2', 'rollNumber' => '2', 'stdId' => 4202, 'departmentName' => $scope['department_id']],
            ['gender' => '1', 'rollNumber' => '3', 'stdId' => 4103, 'departmentName' => $secondDepartment],
            ['gender' => '1', 'rollNumber' => '11', 'stdId' => 4111, 'departmentName' => $secondDepartment],
            ['gender' => '2', 'rollNumber' => '3', 'stdId' => 4203, 'departmentName' => $secondDepartment],
            ['gender' => '1', 'rollNumber' => '4', 'stdId' => 4104, 'departmentName' => $secondDepartment],
        ];
        foreach ($students as $index => $student) $student->update($values[$index]);
        $femaleFour = newAdmission::create([
            'stdId' => 4204, 'fullName' => 'Female Four', 'gender' => '2', 'rollNumber' => '4',
            'sessName' => $scope['session_id'], 'className' => $scope['class_id'],
            'sectionName' => $scope['section_id'], 'departmentName' => $secondDepartment,
        ]);
        $sourceA = [$students[0]->id, $students[1]->id, $students[2]->id, $students[3]->id, $students[4]->id];
        $sourceB = [$students[5]->id, $students[8]->id, $students[6]->id, $students[7]->id, $femaleFour->id, $students[0]->id];
        $scope['department_id'] = null;

        $ordered = app(AcademicAttendanceService::class)->students($scope, array_merge($sourceA, $sourceB))->get();
        $this->assertSame([4101, 4102, 4103, 4104, 4110, 4111, 4201, 4202, 4203, 4204], $ordered->pluck('stdId')->map(fn ($id) => (int) $id)->all());
        $this->assertSame($ordered->count(), $ordered->pluck('id')->unique()->count());
    }

    public function test_duplicate_and_invalid_rolls_keep_stable_fallback_order_inside_each_gender(): void
    {
        [$scope, $students] = $this->fixture(8);
        $values = [
            ['gender' => '2', 'rollNumber' => '10', 'stdId' => 3004],
            ['gender' => '1', 'rollNumber' => '10', 'stdId' => 2004],
            ['gender' => '1', 'rollNumber' => '2', 'stdId' => 2003],
            ['gender' => '1', 'rollNumber' => '1', 'stdId' => 2002],
            ['gender' => '1', 'rollNumber' => '1', 'stdId' => 2001],
            ['gender' => '1', 'rollNumber' => '', 'stdId' => 2005],
            ['gender' => '1', 'rollNumber' => 'N/A', 'stdId' => 2006],
            ['gender' => '2', 'rollNumber' => '1', 'stdId' => 3001],
        ];
        foreach ($students as $index => $student) $student->update($values[$index]);

        $this->assertSame(
            [2001, 2002, 2003, 2004, 2005, 2006, 3001, 3004],
            app(AcademicAttendanceService::class)->students($scope)->pluck('stdId')->map(fn ($id) => (int) $id)->all()
        );
    }

    public function test_bulk_route_persists_every_loaded_row_beyond_the_first_data_table_page(): void
    {
        [$scope, $students] = $this->fixture(51);
        $adminId = DB::table('cultivation_admins')->insertGetId(['adminName' => 'General', 'adminUser' => 'general-bulk', 'userType' => 3, 'created_at' => now(), 'updated_at' => now()]);
        $rows = $students->map(fn ($student) => [
            'student_id' => $student->id, 'working_days' => 120, 'present_days' => 112, 'absent_days' => 8,
        ])->all();

        $this->withSession(['cultivationAdmin' => $adminId])->post(route('academic-attendance.bulk.store'), $scope + ['students' => $rows])
            ->assertRedirect(route('academic-attendance.index', $scope));
        $this->assertDatabaseCount('academic_attendances', 51);
    }

    public function test_page_owns_cross_page_data_table_state_and_restores_all_rows_before_submit(): void
    {
        [$scope] = $this->fixture(1);
        $adminId = DB::table('cultivation_admins')->insertGetId(['adminName' => 'General', 'adminUser' => 'general-js', 'userType' => 3, 'created_at' => now(), 'updated_at' => now()]);
        $html = $this->withSession(['cultivationAdmin' => $adminId])->get(route('academic-attendance.index', $scope))->getContent();

        $this->assertStringContainsString("const attendanceState = new Map()", $html);
        $this->assertStringContainsString('dataTable.rows().nodes()', $html);
        $this->assertStringContainsString("jQuery(tableElement).on('draw.dt'", $html);
        $this->assertStringContainsString('dataTable.destroy()', $html);
        $this->assertStringContainsString('ordering: false', $html);
        $this->assertStringContainsString('order: []', $html);
        $this->assertStringContainsString('aaSorting: []', $html);
        $this->assertStringNotContainsString('data-table text-nowrap', $html);
    }

    public function test_daily_attendance_table_is_untouched(): void
    {
        [$scope, $students] = $this->fixture(1);
        DB::table('attendances')->insert(['attendance_date' => '2026-08-11', 'class_id' => $scope['class_id'], 'section_id' => $scope['section_id'], 'session_id' => $scope['session_id'], 'student_id' => $students[0]->id, 'teacher_id' => 1, 'status' => 'Present', 'created_at' => now(), 'updated_at' => now()]);
        app(AcademicAttendanceService::class)->saveOne($scope, ['student_id' => $students[0]->id, 'working_days' => 20, 'present_days' => 18, 'absent_days' => 2], 1);
        $this->assertDatabaseHas('attendances', ['student_id' => $students[0]->id, 'status' => 'Present']);
    }

    private function fixture(int $count): array
    {
        $session = DB::table('session_manages')->insertGetId(['session' => '2026', 'created_at' => now(), 'updated_at' => now()]);
        $class = DB::table('class_manages')->insertGetId(['className' => 'Nine', 'created_at' => now(), 'updated_at' => now()]);
        $section = DB::table('section_manages')->insertGetId(['section' => 'A', 'created_at' => now(), 'updated_at' => now()]);
        $department = DB::table('departments')->insertGetId(['departmentName' => 'Science', 'created_at' => now(), 'updated_at' => now()]);
        $exam = DB::table('exams')->insertGetId(['examName' => 'Annual', 'created_at' => now(), 'updated_at' => now()]);
        $students = collect(range(1, $count))->map(fn ($number) => newAdmission::create([
            'stdId' => 26000000 + $number, 'fullName' => 'Student '.$number, 'gender' => $number % 2 ? '1' : '2',
            'sessName' => $session, 'className' => $class, 'sectionName' => $section, 'departmentName' => $department, 'rollNumber' => $number,
        ]));
        return [[
            'exam_id' => $exam, 'session_id' => $session, 'class_id' => $class,
            'section_id' => $section, 'department_id' => $department, 'gender' => 'all',
        ], $students];
    }
}
