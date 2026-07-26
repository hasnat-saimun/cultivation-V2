<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Subject;
use App\Services\TeacherAssignmentSubjectCatalogService;
use App\Services\TeacherSubjectAssignmentAvailabilityService;
use Illuminate\Support\Facades\DB;

function subjectMatchesClass(?string $assignClass, int $classId): bool {
    $assignClass = trim((string) $assignClass);
    if ($assignClass === '' || $assignClass === '0') {
        return true;
    }
    if (ctype_digit($assignClass)) {
        return (int) $assignClass === $classId;
    }
    preg_match_all('/\d+/', $assignClass, $matches);
    $ids = array_map('intval', $matches[0] ?? []);
    return empty($ids) || in_array($classId, $ids, true);
}

$subjectCatalog = app(TeacherAssignmentSubjectCatalogService::class);
$availability = app(TeacherSubjectAssignmentAvailabilityService::class);

$scopes = DB::table('curriculum_subject_mappings as m')
    ->select('m.session_id', 'm.class_id', 'm.section_id', 'm.department_id')
    ->where('m.mapping_type', 'main')
    ->where('m.is_active', 1)
    ->distinct()
    ->orderBy('m.session_id')
    ->orderBy('m.class_id')
    ->orderBy('m.section_id')
    ->orderBy('m.department_id')
    ->get();

$subjectRows = Subject::query()->select(['id', 'subjectName', 'subjectType', 'isReligious', 'assign_class', 'CQ', 'MCQ', 'Practical'])->get()->keyBy('id');

$report = [];
foreach ($scopes as $scope) {
    $sessionId = is_numeric($scope->session_id) ? (int) $scope->session_id : null;
    $classId = (int) $scope->class_id;
    $sectionId = is_numeric($scope->section_id) ? (int) $scope->section_id : null;
    $groupId = is_numeric($scope->department_id) ? (int) $scope->department_id : null;

    $expected = DB::table('curriculum_subject_mappings')
        ->where('session_id', (string) $sessionId)
        ->where('class_id', (string) $classId)
        ->where('mapping_type', 'main')
        ->where('is_active', 1)
        ->get()
        ->filter(function ($row) use ($sectionId, $groupId) {
            $section = App\Models\CurriculumSubjectMapping::normalizeSectionScope(is_numeric($row->section_id) ? (int) $row->section_id : null);
            $department = App\Models\CurriculumSubjectMapping::normalizeDepartmentScope(is_numeric($row->department_id) ? (int) $row->department_id : null);
            $exactSection = App\Models\CurriculumSubjectMapping::normalizeSectionScope($sectionId);
            $classWideSection = App\Models\CurriculumSubjectMapping::normalizeSectionScope(null);
            $exactDept = App\Models\CurriculumSubjectMapping::normalizeDepartmentScope($groupId);
            $commonDept = App\Models\CurriculumSubjectMapping::normalizeDepartmentScope(null);
            $sectionOk = in_array($section, [$exactSection, $classWideSection, 'section:all'], true);
            $deptOk = $groupId !== null
                ? in_array($department, [$exactDept, $commonDept, 'department:all'], true)
                : in_array($department, [$commonDept, 'department:all'], true);
            return $sectionOk && $deptOk;
        })
        ->pluck('subject_id')
        ->map(fn ($id) => (int) $id)
        ->unique()
        ->values()
        ->all();

    $allowed = $subjectCatalog->resolveAllowedSubjectIds($sessionId, $classId, $sectionId, $groupId);
    $missing = array_values(array_diff($expected, $allowed));
    $extra = array_values(array_diff($allowed, $expected));

    if ($missing !== [] || $extra !== []) {
        $report[] = [
            'scope' => [
                'session_id' => $sessionId,
                'class_id' => $classId,
                'section_id' => $sectionId,
                'group_id' => $groupId,
            ],
            'expected_count' => count($expected),
            'allowed_count' => count($allowed),
            'missing' => array_map(function ($id) use ($subjectRows) {
                $subject = $subjectRows->get($id);
                return [
                    'id' => $id,
                    'name' => (string) ($subject->subjectName ?? ''),
                    'subjectType' => (string) ($subject->subjectType ?? ''),
                    'isReligious' => (string) ($subject->isReligious ?? ''),
                    'assign_class' => (string) ($subject->assign_class ?? ''),
                    'cq' => $subject->CQ ?? null,
                    'mcq' => $subject->MCQ ?? null,
                    'practical' => $subject->Practical ?? null,
                ];
            }, $missing),
            'extra' => array_map(function ($id) use ($subjectRows) {
                $subject = $subjectRows->get($id);
                return [
                    'id' => $id,
                    'name' => (string) ($subject->subjectName ?? ''),
                    'subjectType' => (string) ($subject->subjectType ?? ''),
                    'isReligious' => (string) ($subject->isReligious ?? ''),
                    'assign_class' => (string) ($subject->assign_class ?? ''),
                ];
            }, $extra),
        ];
    }
}

echo json_encode([
    'mismatch_count' => count($report),
    'report' => $report,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), PHP_EOL;
