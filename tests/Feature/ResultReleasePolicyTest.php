<?php

namespace Tests\Feature;

use App\Services\ResultHistoricalExceptionManifest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ResultReleasePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_exact_manifest_is_warning_but_new_invalid_reference_blocks(): void
    {
        $this->seedAcademicMasters();
        DB::table('marksheets')->insert([
            $this->mark(1, 900, 20, 30),
            $this->mark(2, 10, 800, 700),
        ]);

        Config::set('result_engine.historical_exception_manifest.databases', ['cultivation_test']);
        $verification = (new ResultHistoricalExceptionManifest(DB::connection()))->verify();
        foreach ($verification['actual'] as $key => $value) {
            Config::set('result_engine.historical_exception_manifest.'.$key, $value);
        }

        $this->assertSame(0, Artisan::call('result-engine:integrity-preflight', ['--json' => true]));
        $output = Artisan::output();
        $this->assertStringContainsString('"status": "PASS"', $output);
        $this->assertStringContainsString('"category": "historical_legacy_exception"', $output);
        $this->assertStringContainsString('"orphan_student_ids"', $output);

        DB::table('marksheets')->insert($this->mark(3, 901, 20, 30));

        $this->assertSame(1, Artisan::call('result-engine:integrity-preflight', ['--json' => true]));
        $output = Artisan::output();
        $this->assertStringContainsString('"status": "BLOCKED"', $output);
        $this->assertStringContainsString('"code": "historical_exception_manifest"', $output);
        $this->assertStringContainsString('"category": "integrity_violation"', $output);
    }

    private function seedAcademicMasters(): void
    {
        DB::table('new_admissions')->insert(['id' => 10, 'stdId' => 10010]);
        DB::table('exams')->insert(['id' => 20, 'examName' => 'Manifest test exam']);
        DB::table('subjects')->insert(['id' => 30, 'subjectName' => 'Manifest test subject']);
        DB::table('class_manages')->insert(['id' => 40, 'className' => 'Manifest test class']);
        DB::table('session_manages')->insert(['id' => 50, 'session' => '2099']);
        DB::table('section_manages')->insert(['id' => 60, 'section' => 'Manifest']);
    }

    private function mark(int $id, int $studentId, int $examId, int $subjectId): array
    {
        return [
            'id' => $id,
            'studentId' => (string) $studentId,
            'sessionId' => '50',
            'classId' => '40',
            'groupId' => '60',
            'examId' => (string) $examId,
            'subjectId' => (string) $subjectId,
            'subjectMarks' => '50',
            'objectMarks' => null,
            'practicalMarks' => null,
            'totalMarks' => '50',
            'laterGrade' => 'B',
            'gradePoint' => '3',
            'created_at' => '2030-01-01 00:00:00',
            'updated_at' => '2030-01-01 00:00:00',
        ];
    }
}
