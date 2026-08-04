<?php

namespace Tests\Feature;

use App\Models\newAdmission;
use App\Services\Students\StudentFilterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentProfessionalOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_professional_order_is_scope_gender_numeric_roll_and_stable_id_aware(): void
    {
        $rows = [
            ['stdId' => 110, 'sessName' => 2, 'className' => 1, 'sectionName' => 1, 'departmentName' => 1, 'gender' => '1', 'rollNumber' => '1'],
            ['stdId' => 120, 'sessName' => 1, 'className' => 2, 'sectionName' => 1, 'departmentName' => 1, 'gender' => '1', 'rollNumber' => '1'],
            ['stdId' => 103, 'sessName' => 1, 'className' => 1, 'sectionName' => 1, 'departmentName' => 1, 'gender' => '2', 'rollNumber' => '1'],
            ['stdId' => 102, 'sessName' => 1, 'className' => 1, 'sectionName' => 1, 'departmentName' => 1, 'gender' => '1', 'rollNumber' => '10'],
            ['stdId' => 101, 'sessName' => 1, 'className' => 1, 'sectionName' => 1, 'departmentName' => 1, 'gender' => '1', 'rollNumber' => '2'],
            ['stdId' => 100, 'sessName' => 1, 'className' => 1, 'sectionName' => 1, 'departmentName' => 1, 'gender' => '1', 'rollNumber' => '2'],
            ['stdId' => 105, 'sessName' => 1, 'className' => 1, 'sectionName' => 1, 'departmentName' => 1, 'gender' => '3', 'rollNumber' => '1'],
            ['stdId' => 106, 'sessName' => 1, 'className' => 1, 'sectionName' => 1, 'departmentName' => 1, 'gender' => '1', 'rollNumber' => ''],
            ['stdId' => 107, 'sessName' => 1, 'className' => 1, 'sectionName' => 1, 'departmentName' => 1, 'gender' => '1', 'rollNumber' => null],
            ['stdId' => 108, 'sessName' => 1, 'className' => 1, 'sectionName' => 1, 'departmentName' => 1, 'gender' => '1', 'rollNumber' => 'A-1'],
        ];

        foreach ($rows as $row) {
            newAdmission::create($row + ['fullName' => 'Ordering', 'sureName' => 'Student']);
        }

        $this->assertSame(
            [100, 101, 102, 106, 107, 108, 103, 105, 120, 110],
            newAdmission::query()->professionalOrder()->pluck('stdId')->map(fn ($id) => (int) $id)->all()
        );
    }

    public function test_pagination_has_no_overlap_or_gap_and_filter_query_uses_same_order(): void
    {
        foreach ([10, 2, 1, 4, 3, 8, 7, 6, 5, 9] as $roll) {
            newAdmission::create([
                'stdId' => 200 + $roll, 'fullName' => 'Page', 'sureName' => 'Student',
                'sessName' => 1, 'className' => 1, 'sectionName' => 1,
                'departmentName' => 1, 'gender' => $roll <= 5 ? '1' : '2',
                'rollNumber' => (string) $roll,
            ]);
        }

        $pageOne = newAdmission::query()->professionalOrder()->paginate(5, ['*'], 'page', 1);
        $pageTwo = newAdmission::query()->professionalOrder()->paginate(5, ['*'], 'page', 2);
        $all = array_merge($pageOne->pluck('stdId')->all(), $pageTwo->pluck('stdId')->all());

        $this->assertCount(10, array_unique($all));
        $this->assertSame(range(201, 210), array_map('intval', $all));

        $filters = ['sessionId' => 1, 'classId' => 1, 'sectionId' => 1, 'departmentId' => 1, 'gender' => null, 'search' => null];
        $filtered = app(StudentFilterService::class)->query($filters)->pluck('stdId')->map(fn ($id) => (int) $id)->all();
        $this->assertSame($all, $filtered);
    }
}
