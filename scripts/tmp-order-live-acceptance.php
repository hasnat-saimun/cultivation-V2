<?php

use App\Http\Controllers\CurriculumSubjectMappingController;
use App\Http\Controllers\MarksheetController;
use App\Models\CurriculumSubjectMapping;
use App\Models\Marksheet;
use App\Models\Subject;
use App\Models\newAdmission;
use App\Services\AcademicSubjectApplicabilityService;
use App\Services\ResultCalculation\ResultCalculationInputBuilder;
use App\Services\ResultCalculation\BoardResultCalculator;
use App\Services\ResultCalculation\TranscriptResultPresenter;
use App\Services\ResultCalculation\TranscriptSubjectOrderingService;
use Illuminate\Http\Request;

$student = newAdmission::findOrFail(66);
$examId = 4;
$scope = [
    'sessionId' => (int) $student->sessName,
    'classId' => (int) $student->className,
    'sectionId' => (int) $student->sectionName,
    'departmentId' => (int) $student->departmentName,
    'mappingType' => 'main',
];

$subjects = Subject::query()->get(['id', 'subjectName', 'subjectType', 'isReligious']);
$idByName = [];
foreach ($subjects as $subject) {
    $idByName[$subject->subjectName] = (int) $subject->id;
}

$requiredNames = [
    'Bangla 1st Paper',
    'Bangla 2nd Paper',
    'English 1st Paper',
    'English 2nd Paper',
    'Math-109',
    'Information and Comminucation Technology- 154',
    'Bangladesh and Global Studies-150',
    'Physics-136',
    'Chemistry-137',
    'Biology-138',
];

$requiredIds = [];
foreach ($requiredNames as $name) {
    if (!isset($idByName[$name])) {
        echo json_encode(['error' => 'missing required subject', 'name' => $name]);
        return;
    }
    $requiredIds[] = $idByName[$name];
}

$religionId = (int) ($student->religiousSubjectId ?? 0);
if ($religionId > 0) {
    $requiredIds[] = $religionId;
}
$requiredIds = array_values(array_unique($requiredIds));

$orderById = [
    $idByName['Bangla 1st Paper'] => 10,
    $idByName['Bangla 2nd Paper'] => 20,
    $idByName['English 1st Paper'] => 30,
    $idByName['English 2nd Paper'] => 40,
    $idByName['Math-109'] => 50,
    $idByName['Information and Comminucation Technology- 154'] => 60,
    $idByName['Bangladesh and Global Studies-150'] => 70,
    $religionId => 80,
    $idByName['Physics-136'] => 90,
    $idByName['Chemistry-137'] => 100,
    $idByName['Biology-138'] => 110,
];

$controller = app(CurriculumSubjectMappingController::class);
$request = Request::create('/result/curriculum-mapping/save', 'POST', $scope + [
    'subjectIds' => $requiredIds,
    'sortOrders' => $orderById,
]);
$request->setLaravelSession(app('session')->driver());
$controller->save($request);

$rows = CurriculumSubjectMapping::query()
    ->where('session_id', (string) $scope['sessionId'])
    ->where('class_id', (string) $scope['classId'])
    ->where('mapping_type', 'main')
    ->where('is_active', 1)
    ->where(function ($query) use ($scope) {
        $query->where('section_id', (string) $scope['sectionId'])
            ->orWhereNull('section_id');
    })
    ->where(function ($query) use ($scope) {
        $query->where('department_id', (string) $scope['departmentId'])
            ->orWhereNull('department_id');
    })
    ->orderBy('sort_order')
    ->orderBy('subject_id')
    ->get(['subject_id', 'sort_order', 'mapping_type', 'section_id', 'department_id', 'normalized_section_scope', 'normalized_department_scope']);

$subjectNames = Subject::query()->whereIn('id', $rows->pluck('subject_id')->all())->pluck('subjectName', 'id');
$dbRows = $rows->map(fn ($row) => [
    'subject_id' => (int) $row->subject_id,
    'subject_name' => (string) ($subjectNames[(int) $row->subject_id] ?? 'UNKNOWN'),
    'sort_order' => (int) $row->sort_order,
    'mapping_type' => (string) $row->mapping_type,
    'section_scope' => (string) $row->normalized_section_scope,
    'department_scope' => (string) $row->normalized_department_scope,
])->values()->all();

$applicability = app(AcademicSubjectApplicabilityService::class);
$builder = app(ResultCalculationInputBuilder::class);
$stage2 = collect($applicability->subjectsForStudent($student))->map(fn ($subject) => [
    'subject_id' => (int) $subject->id,
    'subject_name' => (string) $subject->subjectName,
    'sort_order' => (int) ($subject->applicability_order ?? 0),
])->values()->all();
$builderSubjects = collect($builder->subjectsForStudent($student));
$stage3 = $builderSubjects->map(fn ($subject) => [
    'subject_id' => (int) $subject->id,
    'subject_name' => (string) $subject->subjectName,
    'sort_order' => (int) ($subject->applicability_order ?? 0),
])->values()->all();

$marks = Marksheet::query()->where('studentId', (int) $student->id)->where('examId', $examId)->orderBy('subjectId')->get();
$result = app(BoardResultCalculator::class)->calculate($student, \App\Models\Exam::findOrFail($examId), $marks, $builderSubjects);

$stage4 = [];
foreach ($result->subjectResults as $subjectResult) {
    $source = $builderSubjects->whereIn('id', $subjectResult->sourceSubjectIds)->values();
    $displayName = str_starts_with($subjectResult->subjectId, 'pair:')
        ? (config('subject_pairs.displayNames.'.strtolower(substr($subjectResult->subjectId, 5))) ?? $subjectResult->subjectId)
        : (string) ($source->first()->subjectName ?? $subjectResult->subjectId);

    $stage4[] = [
        'id' => $subjectResult->subjectId,
        'name' => $displayName,
        'sortOrder' => (int) ($source->min('applicability_order') ?? PHP_INT_MAX),
        'isOptional' => (bool) $subjectResult->isOptional,
        'sourceIds' => $subjectResult->sourceSubjectIds,
    ];
}

$ordering = app(TranscriptSubjectOrderingService::class);
$stage5 = $ordering->sortMainRows(array_values(array_filter($stage4, fn ($row) => !$row['isOptional'])));

$presented = app(TranscriptResultPresenter::class)->present($result, $builderSubjects, $marks);
$stage6 = array_map(fn ($row) => [
    'id' => $row['id'],
    'name' => $row['name'],
    'sortOrder' => $row['sortOrder'],
    'sourceIds' => $row['sourceIds'],
], $presented['mainRows']);

$html = app(MarksheetController::class)->generateMarksheet(Request::create('/marksheet/generate', 'GET', [
    'studentId' => $student->id,
    'examId' => $examId,
]))->render();

preg_match('/<h3[^>]*>\s*Main Subject\s*<\/h3>\s*<table[^>]*>.*?<tbody>(.*?)<\/tbody>/si', $html, $mainMatch);
$mainTable = $mainMatch[1] ?? '';
preg_match_all('/<tr[^>]*data-subject-id="([^"]+)"[^>]*>\s*<td>(.*?)<\/td>/si', $mainTable, $rowMatches, PREG_SET_ORDER);
$stage7 = [];
foreach ($rowMatches as $rowMatch) {
    $stage7[] = [
        'subject_id' => $rowMatch[1],
        'subject_name' => trim(html_entity_decode(strip_tags($rowMatch[2]))),
    ];
}

preg_match('/<h3[^>]*>\s*Optional Subject\s*<\/h3>\s*<table[^>]*>.*?<tbody>(.*?)<\/tbody>/si', $html, $optionalMatch);
$optionalTable = $optionalMatch[1] ?? '';
preg_match_all('/<tr[^>]*data-subject-id="([^"]+)"[^>]*>\s*<td>(.*?)<\/td>/si', $optionalTable, $optRowMatches, PREG_SET_ORDER);
$optionalRows = [];
foreach ($optRowMatches as $rowMatch) {
    $optionalRows[] = [
        'subject_id' => $rowMatch[1],
        'subject_name' => trim(html_entity_decode(strip_tags($rowMatch[2]))),
    ];
}

$mainNames = array_map(fn ($row) => $row['subject_name'], $stage7);
$containsBusiness = collect($mainNames)->contains(fn ($name) => str_contains(strtolower($name), 'accounting') || str_contains(strtolower($name), 'finance') || str_contains(strtolower($name), 'entrepreneurship'));
$containsHumanities = collect($mainNames)->contains(fn ($name) => str_contains(strtolower($name), 'history') || str_contains(strtolower($name), 'civics') || str_contains(strtolower($name), 'geography'));

$gradingLayoutUnchanged = str_contains($html, 'transcript-information-grid')
    && str_contains($html, 'student-information')
    && str_contains($html, 'grading-information')
    && str_contains($html, 'grading-table');

echo json_encode([
    'student' => [
        'id' => (int) $student->id,
        'exam_id' => $examId,
        'session' => $student->sessName,
        'class' => $student->className,
        'section' => $student->sectionName,
        'department' => $student->departmentName,
    ],
    'stage1_db_mapping_rows' => $dbRows,
    'stage2_applicability_output' => $stage2,
    'stage3_input_builder_output' => $stage3,
    'stage4_ordering_service_input' => $stage4,
    'stage5_ordering_service_output' => $stage5,
    'stage6_presenter_main_rows' => $stage6,
    'stage7_blade_rendered_main_rows' => $stage7,
    'optional_rows' => $optionalRows,
    'contains_business_subject' => $containsBusiness,
    'contains_humanities_subject' => $containsHumanities,
    'grading_layout_unchanged' => $gradingLayoutUnchanged,
], JSON_PRETTY_PRINT);
