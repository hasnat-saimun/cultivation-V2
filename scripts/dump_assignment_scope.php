<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Subject;
use App\Services\CurriculumSubjectMappingService;
use App\Services\TeacherAssignmentSubjectCatalogService;
use App\Services\TeacherSubjectAssignmentAvailabilityService;
use Illuminate\Support\Facades\DB;

$scope = (object) [
    'sessName' => 2,
    'className' => 5,
    'sectionName' => 7,
    'departmentName' => 2,
];

$className = (string) (DB::table('class_manages')->where('id', 5)->value('className') ?? '');
$groupEnabled = app(App\Services\DepartmentBasedClassDetector::class)->isDepartmentBasedClass($className);
$mappingService = app(CurriculumSubjectMappingService::class);
$catalogService = app(TeacherAssignmentSubjectCatalogService::class);
$availabilityService = app(TeacherSubjectAssignmentAvailabilityService::class);

$expectedRows = DB::table('curriculum_subject_mappings')
    ->where('session_id', '2')
    ->where('class_id', '5')
    ->where('mapping_type', 'main')
    ->where('is_active', 1)
    ->get()
    ->filter(function ($row) {
        $section = App\Models\CurriculumSubjectMapping::normalizeSectionScope(is_numeric($row->section_id) ? (int) $row->section_id : null);
        $department = App\Models\CurriculumSubjectMapping::normalizeDepartmentScope(is_numeric($row->department_id) ? (int) $row->department_id : null);
        $exactSection = App\Models\CurriculumSubjectMapping::normalizeSectionScope(7);
        $classWideSection = App\Models\CurriculumSubjectMapping::normalizeSectionScope(null);
        $exactDept = App\Models\CurriculumSubjectMapping::normalizeDepartmentScope(2);
        $commonDept = App\Models\CurriculumSubjectMapping::normalizeDepartmentScope(null);
        $sectionOk = in_array($section, [$exactSection, $classWideSection, 'section:all'], true);
        $deptOk = in_array($department, [$exactDept, $commonDept, 'department:all'], true);
        return $sectionOk && $deptOk;
    })
    ->map(function ($row) {
        return [
            'mapping_id' => (int) $row->id,
            'subject_id' => (int) $row->subject_id,
            'section_scope' => App\Models\CurriculumSubjectMapping::normalizeSectionScope(is_numeric($row->section_id) ? (int) $row->section_id : null),
            'department_scope' => App\Models\CurriculumSubjectMapping::normalizeDepartmentScope(is_numeric($row->department_id) ? (int) $row->department_id : null),
            'sort_order' => (int) $row->sort_order,
            'active' => (int) $row->is_active,
        ];
    })
    ->groupBy('subject_id')
    ->map(function ($rows, $subjectId) {
        return [
            'subject_id' => (int) $subjectId,
            'rows' => $rows->values()->all(),
        ];
    })
    ->values();

$mappedMain = $mappingService->mappedMainSubjectsForStudent($scope, $groupEnabled)
    ->map(fn ($s) => [
        'id' => (int) $s->id,
        'name' => (string) $s->subjectName,
        'subjectType' => (string) ($s->subjectType ?? ''),
        'isReligious' => (string) ($s->isReligious ?? ''),
        'assign_class' => (string) ($s->assign_class ?? ''),
    ])
    ->values();

$allowedIds = $catalogService->resolveAllowedSubjectIds(2, 5, 7, 2);
$available = $availabilityService->subjectsWithAvailability([
    'session_id' => 2,
    'class_id' => 5,
    'section_id' => 7,
    'group_id' => 2,
], 'all');

$subjectRows = Subject::query()->select(['id', 'subjectName', 'subjectType', 'isReligious', 'assign_class', 'CQ', 'MCQ', 'Practical'])->get()->keyBy('id');

$expectedIds = $expectedRows->pluck('subject_id')->unique()->values()->all();
$missingFromAllowed = array_values(array_diff($expectedIds, $allowedIds));
$extraInAllowed = array_values(array_diff($allowedIds, $expectedIds));

$rendered = $available->map(fn ($row) => [
    'id' => (int) $row['id'],
    'name' => (string) $row['name'],
])->values();

echo json_encode([
    'scope' => [
        'session_id' => 2,
        'class_id' => 5,
        'class_name' => $className,
        'section_id' => 7,
        'group_id' => 2,
        'group_enabled' => $groupEnabled,
    ],
    'expected_count' => count($expectedIds),
    'allowed_count' => count($allowedIds),
    'availability_count' => $available->count(),
    'expected_subjects' => $expectedRows->map(function ($item) use ($subjectRows) {
        $subject = $subjectRows->get((int) $item['subject_id']);
        return [
            'subject_id' => (int) $item['subject_id'],
            'subject_name' => (string) ($subject->subjectName ?? ''),
            'subjectType' => (string) ($subject->subjectType ?? ''),
            'isReligious' => (string) ($subject->isReligious ?? ''),
            'assign_class' => (string) ($subject->assign_class ?? ''),
            'rows' => $item['rows'],
        ];
    })->values(),
    'mapped_main_subjects' => $mappedMain,
    'allowed_ids' => $allowedIds,
    'availability_subjects' => $rendered,
    'missing_from_allowed' => array_map(fn ($id) => [
        'id' => $id,
        'name' => (string) ($subjectRows->get($id)->subjectName ?? ''),
        'subjectType' => (string) ($subjectRows->get($id)->subjectType ?? ''),
        'isReligious' => (string) ($subjectRows->get($id)->isReligious ?? ''),
        'assign_class' => (string) ($subjectRows->get($id)->assign_class ?? ''),
    ], $missingFromAllowed),
    'extra_in_allowed' => array_map(fn ($id) => [
        'id' => $id,
        'name' => (string) ($subjectRows->get($id)->subjectName ?? ''),
        'subjectType' => (string) ($subjectRows->get($id)->subjectType ?? ''),
        'isReligious' => (string) ($subjectRows->get($id)->isReligious ?? ''),
        'assign_class' => (string) ($subjectRows->get($id)->assign_class ?? ''),
    ], $extraInAllowed),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), PHP_EOL;
