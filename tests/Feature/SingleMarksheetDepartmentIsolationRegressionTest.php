<?php

namespace Tests\Feature;

use App\Models\classManage;
use App\Models\Department;
use App\Models\Exam;
use App\Models\Marksheet;
use App\Models\newAdmission;
use App\Models\sectionManage;
use App\Models\sessionManage;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SingleMarksheetDepartmentIsolationRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_marksheet_route_excludes_cross_department_subjects_and_keeps_optional_separate(): void
    {
        $this->withoutMiddleware();

        $scope = $this->buildClassTenScope();
        $subjects = $this->buildSubjects($scope);

        $scienceStudent = $this->student($scope, $scope['science']->id, $subjects['religion']->id, $subjects['higherMath']->id, '26000001');
        $humanitiesStudent = $this->student($scope, $scope['humanities']->id, $subjects['religion']->id, null, '26000051');
        $businessStudent = $this->student($scope, $scope['business']->id, $subjects['religion']->id, null, '26000081');

        $this->mark($scienceStudent, $scope, $subjects['bangla'], 82);
        $this->mark($scienceStudent, $scope, $subjects['physics'], 78);
        $this->mark($scienceStudent, $scope, $subjects['chemistry'], 76);
        $this->mark($scienceStudent, $scope, $subjects['biology'], 74);
        $this->mark($scienceStudent, $scope, $subjects['religion'], 83);
        $this->mark($scienceStudent, $scope, $subjects['higherMath'], 88);

        // Cross-department marks for other students must not leak into science transcript.
        $this->mark($humanitiesStudent, $scope, $subjects['history'], 66);
        $this->mark($humanitiesStudent, $scope, $subjects['civics'], 67);
        $this->mark($humanitiesStudent, $scope, $subjects['geography'], 68);
        $this->mark($businessStudent, $scope, $subjects['accounting'], 69);
        $this->mark($businessStudent, $scope, $subjects['finance'], 70);
        $this->mark($businessStudent, $scope, $subjects['entrepreneurship'], 71);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->get(route('marksheetGenerate', [
            'studentId' => $scienceStudent->id,
            'stdId' => $scienceStudent->stdId,
            'examId' => $scope['exam']->id,
        ]));

        $response->assertOk()->assertViewIs('result.marksheetGenerate');
        $html = $response->getContent();

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $response->assertSee('Physics-136');
        $response->assertSee('Chemistry-137');
        $response->assertSee('Biology-138');

        $response->assertDontSee('Accounting-146');
        $response->assertDontSee('Finance and Banking-152');
        $response->assertDontSee('Business Entrepreneurship-143');
        $response->assertDontSee('History of Bangladesh and World Civilization-153');
        $response->assertDontSee('Civics and Citizenship-140');
        $response->assertDontSee('Geography and Environment-110');

        // Science-127 is intentionally mapped to non-science departments in this fixture.
        $response->assertDontSee('Science-127');

        $result = $response->viewData('transcriptResult');
        $mainNames = collect($result['mainRows'])->pluck('name')->all();
        $optionalNames = collect($result['optionalRows'])->pluck('name')->all();

        $this->assertContains('Higher Math-126', $optionalNames);
        $this->assertNotContains('Higher Math-126', $mainNames);
        $this->assertSame([], array_values(array_intersect($mainNames, $optionalNames)));

        // Blade must render exactly presenter-provided collections; no appended subjects.
        $mainNamesFromHtml = $this->extractSubjectNamesFromSection($html, 'Main Subject');
        $optionalNamesFromHtml = $this->extractSubjectNamesFromSection($html, 'Optional Subject');
        $this->assertSame($mainNames, $mainNamesFromHtml);
        $this->assertSame($optionalNames, $optionalNamesFromHtml);

        // Keep query growth bounded for this real route.
        $this->assertLessThanOrEqual(30, $queryCount);
    }

    public function test_all_department_tabulation_uses_per_student_applicability_matrix(): void
    {
        $this->withoutMiddleware();

        $scope = $this->buildClassTenScope();
        $subjects = $this->buildSubjects($scope);

        $scienceStudent = $this->student($scope, $scope['science']->id, $subjects['religion']->id, $subjects['higherMath']->id, '26000101');
        $humanitiesStudent = $this->student($scope, $scope['humanities']->id, $subjects['religion']->id, null, '26000102');
        $businessStudent = $this->student($scope, $scope['business']->id, $subjects['religion']->id, null, '26000103');

        foreach ([$scienceStudent, $humanitiesStudent, $businessStudent] as $student) {
            $this->mark($student, $scope, $subjects['bangla'], 80);
            $this->mark($student, $scope, $subjects['religion'], 80);
        }

        $this->mark($scienceStudent, $scope, $subjects['physics'], 79);
        $this->mark($scienceStudent, $scope, $subjects['chemistry'], 78);
        $this->mark($scienceStudent, $scope, $subjects['biology'], 77);

        $this->mark($humanitiesStudent, $scope, $subjects['history'], 76);
        $this->mark($humanitiesStudent, $scope, $subjects['civics'], 75);
        $this->mark($humanitiesStudent, $scope, $subjects['geography'], 74);

        $this->mark($businessStudent, $scope, $subjects['accounting'], 73);
        $this->mark($businessStudent, $scope, $subjects['finance'], 72);
        $this->mark($businessStudent, $scope, $subjects['entrepreneurship'], 71);

        $response = $this->get(route('allMarksheet', [
            'examId' => $scope['exam']->id,
            'classId' => $scope['class']->id,
            'sessionId' => $scope['session']->id,
            'sectionId' => $scope['section']->id,
        ]));

        $response->assertOk();

        $rows = collect($response->viewData('tabulationRows') ?? []);
        $scienceRow = $rows->first(fn ($row) => (int) ($row['student']->id ?? 0) === (int) $scienceStudent->id);
        $this->assertNotNull($scienceRow);

        $cells = $scienceRow['cells'] ?? [];
        $this->assertArrayHasKey('Physics-136', $cells);

        // Business and Humanities cells can exist as columns globally but must not carry marks for science student.
        foreach (['Accounting-146', 'Finance and Banking-152', 'Business Entrepreneurship-143', 'History of Bangladesh and World Civilization-153', 'Civics and Citizenship-140', 'Geography and Environment-110'] as $name) {
            if (!array_key_exists($name, $cells)) {
                continue;
            }
            $value = $cells[$name]['total'] ?? '-';
            $this->assertSame('-', (string) $value);
        }
    }

    public function test_non_group_class_does_not_pick_department_specific_subjects(): void
    {
        $this->withoutMiddleware();

        $session = new sessionManage();
        $session->session = '2026';
        $session->save();

        $class = new classManage();
        $class->className = 'Class 8';
        $class->save();

        $section = new sectionManage();
        $section->section = 'A';
        $section->save();

        $science = new Department();
        $science->departmentName = 'Science';
        $science->save();

        $humanities = new Department();
        $humanities->departmentName = 'Humanities';
        $humanities->save();

        $exam = new Exam();
        $exam->examName = 'Annual 2026';
        $exam->passingSystem = 2;
        $exam->save();

        $common = $this->subject('Common English-108', 'Main', (string) $class->id);
        $deptOnly = $this->subject('Dept Only Subject', 'Main', (string) $class->id);

        $this->mapSubject($class->id, $section->id, $session->id, null, $common->id, 1);
        $this->mapSubject($class->id, $section->id, $session->id, $science->id, $deptOnly->id, 2);
        $this->mapSubject($class->id, $section->id, $session->id, $humanities->id, $deptOnly->id, 2);

        $student = $this->student([
            'session' => $session,
            'class' => $class,
            'section' => $section,
            'exam' => $exam,
            'science' => $science,
            'humanities' => $humanities,
            'business' => $science,
        ], null, null, null, '28000001');

        $this->mark($student, [
            'session' => $session,
            'class' => $class,
            'section' => $section,
            'exam' => $exam,
        ], $common, 77);

        $response = $this->get(route('marksheetGenerate', [
            'studentId' => $student->id,
            'stdId' => $student->stdId,
            'examId' => $exam->id,
        ]));

        $response->assertOk();
        $response->assertSee('Common English-108');
        $response->assertDontSee('Dept Only Subject');
    }

    private function buildClassTenScope(): array
    {
        $session = new sessionManage();
        $session->session = '2026';
        $session->save();

        $class = new classManage();
        $class->className = 'Class 10';
        $class->save();

        $section = new sectionManage();
        $section->section = 'Super';
        $section->save();

        $science = new Department();
        $science->departmentName = 'Science';
        $science->save();

        $humanities = new Department();
        $humanities->departmentName = 'Humanities';
        $humanities->save();

        $business = new Department();
        $business->departmentName = 'Business Studies';
        $business->save();

        $exam = new Exam();
        $exam->examName = 'Annual 2026';
        $exam->passingSystem = 2;
        $exam->save();

        return compact('session', 'class', 'section', 'science', 'humanities', 'business', 'exam');
    }

    private function buildSubjects(array $scope): array
    {
        $subjects = [
            'bangla' => $this->subject('Bangla-101', 'Main', '0'),
            'religion' => $this->subject('Islam and moral education-111', 'Main', '0', true),
            'higherMath' => $this->subject('Higher Math-126', 'Optional', '0'),
            'agriculture' => $this->subject('Agriculture-134', 'Optional', '0'),

            'physics' => $this->subject('Physics-136', 'Main', '0'),
            'chemistry' => $this->subject('Chemistry-137', 'Main', '0'),
            'biology' => $this->subject('Biology-138', 'Main', '0'),

            'accounting' => $this->subject('Accounting-146', 'Main', '0'),
            'finance' => $this->subject('Finance and Banking-152', 'Main', '0'),
            'entrepreneurship' => $this->subject('Business Entrepreneurship-143', 'Main', '0'),

            'history' => $this->subject('History of Bangladesh and World Civilization-153', 'Main', '0'),
            'civics' => $this->subject('Civics and Citizenship-140', 'Main', '0'),
            'geography' => $this->subject('Geography and Environment-110', 'Main', '0'),
            'science127' => $this->subject('Science-127', 'Main', '0'),
        ];

        $this->mapSubject($scope['class']->id, $scope['section']->id, $scope['session']->id, null, $subjects['bangla']->id, 1);
        $this->mapSubject($scope['class']->id, $scope['section']->id, $scope['session']->id, null, $subjects['religion']->id, 2);

        $this->mapSubject($scope['class']->id, $scope['section']->id, $scope['session']->id, $scope['science']->id, $subjects['physics']->id, 10);
        $this->mapSubject($scope['class']->id, $scope['section']->id, $scope['session']->id, $scope['science']->id, $subjects['chemistry']->id, 11);
        $this->mapSubject($scope['class']->id, $scope['section']->id, $scope['session']->id, $scope['science']->id, $subjects['biology']->id, 12);

        $this->mapSubject($scope['class']->id, $scope['section']->id, $scope['session']->id, $scope['business']->id, $subjects['accounting']->id, 20);
        $this->mapSubject($scope['class']->id, $scope['section']->id, $scope['session']->id, $scope['business']->id, $subjects['finance']->id, 21);
        $this->mapSubject($scope['class']->id, $scope['section']->id, $scope['session']->id, $scope['business']->id, $subjects['entrepreneurship']->id, 22);

        $this->mapSubject($scope['class']->id, $scope['section']->id, $scope['session']->id, $scope['humanities']->id, $subjects['history']->id, 30);
        $this->mapSubject($scope['class']->id, $scope['section']->id, $scope['session']->id, $scope['humanities']->id, $subjects['civics']->id, 31);
        $this->mapSubject($scope['class']->id, $scope['section']->id, $scope['session']->id, $scope['humanities']->id, $subjects['geography']->id, 32);

        // Explicitly map Science-127 away from Science to verify applicability behavior.
        $this->mapSubject($scope['class']->id, $scope['section']->id, $scope['session']->id, $scope['humanities']->id, $subjects['science127']->id, 33);

        return $subjects;
    }

    private function mapSubject(int $classId, int $sectionId, int $sessionId, ?int $departmentId, int $subjectId, int $order): void
    {
        DB::table('curriculum_subject_mappings')->insert([
            'session_id' => (string) $sessionId,
            'class_id' => (string) $classId,
            'section_id' => (string) $sectionId,
            'department_id' => $departmentId === null ? null : (string) $departmentId,
            'subject_id' => $subjectId,
            'mapping_type' => 'main',
            'sort_order' => $order,
            'is_active' => 1,
            'source' => 'test-fixture',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function subject(string $name, string $type, string $assignClass, bool $isReligious = false): Subject
    {
        return Subject::create([
            'subjectName' => $name,
            'subjectType' => $type,
            'assign_class' => $assignClass,
            'isReligious' => $isReligious,
            'CQ' => 100,
        ]);
    }

    private function student(array $scope, ?int $departmentId, ?int $religiousSubjectId, ?int $fourthSubjectId, string $stdId): newAdmission
    {
        $student = new newAdmission();
        $student->stdId = $stdId;
        $student->fullName = 'Student '.$stdId;
        $student->sureName = 'Test';
        $student->sessName = $scope['session']->id;
        $student->className = $scope['class']->id;
        $student->sectionName = $scope['section']->id;
        $student->departmentName = $departmentId;
        $student->religiousSubjectId = $religiousSubjectId;
        $student->fourthSubjectId = $fourthSubjectId;
        $student->rollNumber = substr($stdId, -2);
        $student->save();

        return $student;
    }

    private function mark(newAdmission $student, array $scope, Subject $subject, float $cq): void
    {
        Marksheet::create([
            'studentId' => $student->id,
            'classId' => $scope['class']->id,
            'sessionId' => $scope['session']->id,
            'groupId' => $scope['section']->id,
            'examId' => $scope['exam']->id,
            'subjectId' => $subject->id,
            'subjectMarks' => $cq,
            'objectMarks' => null,
            'practicalMarks' => null,
            'totalMarks' => $cq,
            'gradePoint' => 0,
            'laterGrade' => '-',
        ]);
    }

    private function extractSubjectNamesFromSection(string $html, string $heading): array
    {
        $escapedHeading = preg_quote($heading, '/');
        if (!preg_match('/<h3[^>]*>\s*'.$escapedHeading.'\s*<\/h3>\s*<table[^>]*>.*?<tbody>(.*?)<\/tbody>/si', $html, $sectionMatch)) {
            return [];
        }

        if (!preg_match_all('/<tr[^>]*>\s*<td[^>]*>(.*?)<\/td>/si', $sectionMatch[1], $rows)) {
            return [];
        }

        return collect($rows[1])
            ->map(fn ($name) => trim(html_entity_decode(strip_tags((string) $name))))
            ->filter(fn ($name) => $name !== '' && stripos($name, 'No ') !== 0)
            ->values()
            ->all();
    }
}
