<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Marksheet;
use App\Models\Placement;
use App\Models\newAdmission;

class PlacementTest extends TestCase
{
    use RefreshDatabase;

    public function test_recalculate_creates_rankings(): void
    {
        // Prepare students
        $s1 = newAdmission::create(['fullName' => 'Alice', 'rollNumber' => 5]);
        $s2 = newAdmission::create(['fullName' => 'Bob', 'rollNumber' => 3]);

        // Marksheets (two subjects each)
        foreach ([$s1->id, $s2->id] as $sid) {
            Marksheet::create(['studentId' => (string) $sid, 'classId' => '10', 'sessionId' => '2025', 'groupId' => null, 'examId' => 'final', 'gradePoint' => 4.0, 'totalMarks' => 80]);
            Marksheet::create(['studentId' => (string) $sid, 'classId' => '10', 'sessionId' => '2025', 'groupId' => null, 'examId' => 'final', 'gradePoint' => 5.0, 'totalMarks' => 90]);
        }

        // Give Bob higher total marks on tiebreak
        Marksheet::where('studentId', (string) $s2->id)->update(['totalMarks' => 95]);

        $resp = $this->post(route('placements.recalculate'), [
            'sessionId' => '2025',
            'classId' => '10',
            'examId' => 'final',
        ]);
        $resp->assertRedirect();

        $this->assertDatabaseCount('exam_placements', 2);

        $top = Placement::orderBy('position')->first();
        $this->assertEquals((string) $s2->id, $top->studentId); // Bob wins on marks tiebreak
        $this->assertEquals(2, $top->subjectsCount);
        $this->assertEquals(4.50, (float) $top->gpa);
    }
}
