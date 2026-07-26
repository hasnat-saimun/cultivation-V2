<?php

use App\Models\CurriculumSubjectMapping;
use App\Models\Exam;
use App\Models\Marksheet;
use App\Models\Subject;
use App\Models\newAdmission;
use App\Services\AcademicSubjectApplicabilityService;
use App\Services\DepartmentBasedClassDetector;
use App\Services\CurriculumSubjectMappingService;
use App\Services\ResultCalculation\BoardResultCalculator;
use App\Services\ResultCalculation\ResultCalculationInputBuilder;
use App\Services\ResultCalculation\TranscriptResultPresenter;
use App\Services\ResultCalculation\TranscriptSubjectOrderingService;
use App\Http\Controllers\MarksheetController;
use Illuminate\Http\Request;

$studentId = 66;
$examId = 4;
$student = newAdmission::find($studentId);
$exam = Exam::find($examId);

if (!$student || !$exam) {
    echo json_encode(['error' => 'student or exam not found']);
    return;
}

$scopeRows = CurriculumSubjectMapping::query()
    ->where('session_id', (string) $student->sessName)
    ->where('class_id', (string) $student->className)
    ->where('is_active', 1)
    ->orderBy('sort_order')
    ->orderBy('subject_id')
    ->get(['subject_id', 'mapping_type', 'sort_order', 'section_id', 'department_id', 'normalized_section_scope', 'normalized_department_scope']);

$subjectNames = Subject::query()
    ->whereIn('id', $scopeRows->pluck('subject_id')->all())
    ->pluck('subjectName', 'id');

$dbRows = $scopeRows->map(fn ($row) => [
    'subject_id' => (int) $row->subject_id,
    'subject_name' => (string) ($subjectNames[(int) $row->subject_id] ?? 'UNKNOWN'),
    'sort_order' => (int) $row->sort_order,
    'mapping_type' => (string) $row->mapping_type,
    'section_scope' => (string) $row->normalized_section_scope,
    'department_scope' => (string) $row->normalized_department_scope,
])->values()->all();

$detector = app(DepartmentBasedClassDetector::class);
$mappingService = app(CurriculumSubjectMappingService::class);
$applicability = app(AcademicSubjectApplicabilityService::class);
$builder = app(ResultCalculationInputBuilder::class);

$departmentBased = $detector->isDepartmentBasedClass((string) optional(App\Models\classManage::find((int) $student->className))->className);
$mapped = $mappingService->mappedMainSubjectsForStudent($student, $departmentBased);

$mappedRows = $mapped->map(fn ($subject) => [
    'subject_id' => (int) $subject->id,
    'subject_name' => (string) $subject->subjectName,
    'sort_order' => (int) ($subject->applicability_order ?? 0),
])->values()->all();

$appRows = collect($applicability->subjectsForStudent($student))->map(fn ($subject) => [
    'subject_id' => (int) $subject->id,
    'subject_name' => (string) $subject->subjectName,
    'sort_order' => (int) ($subject->applicability_order ?? 0),
])->values()->all();

$builderSubjects = collect($builder->subjectsForStudent($student));
$builderRows = $builderSubjects->map(fn ($subject) => [
    'subject_id' => (int) $subject->id,
    'subject_name' => (string) $subject->subjectName,
    'sort_order' => (int) ($subject->applicability_order ?? 0),
])->values()->all();

$marks = Marksheet::query()
    ->where('studentId', (int) $student->id)
    ->where('examId', (int) $exam->id)
    ->orderBy('subjectId')
    ->get();

$result = app(BoardResultCalculator::class)->calculate($student, $exam, $marks, $builderSubjects);

$orderingInput = [];
foreach ($result->subjectResults as $subjectResult) {
    $source = $builderSubjects->whereIn('id', $subjectResult->sourceSubjectIds)->values();
    $displayName = str_starts_with($subjectResult->subjectId, 'pair:')
        ? (config('subject_pairs.displayNames.'.strtolower(substr($subjectResult->subjectId, 5))) ?? $subjectResult->subjectId)
        : (string) ($source->first()->subjectName ?? $subjectResult->subjectId);

    $orderingInput[] = [
        'id' => $subjectResult->subjectId,
        'name' => $displayName,
        'sortOrder' => (int) ($source->min('applicability_order') ?? PHP_INT_MAX),
        'isOptional' => (bool) $subjectResult->isOptional,
        'sourceIds' => $subjectResult->sourceSubjectIds,
    ];
}

$ordering = app(TranscriptSubjectOrderingService::class);
$orderingOutput = $ordering->sortMainRows(array_values(array_filter($orderingInput, fn ($row) => !$row['isOptional'])));

$presented = app(TranscriptResultPresenter::class)->present($result, $builderSubjects, $marks);

$html = app(MarksheetController::class)->generateMarksheet(Request::create('/marksheet/generate', 'GET', [
    'studentId' => $student->id,
    'examId' => $exam->id,
]))->render();

preg_match('/<h3[^>]*>\s*Main Subject\s*<\/h3>\s*<table[^>]*>.*?<tbody>(.*?)<\/tbody>/si', $html, $mainMatch);
$mainTable = $mainMatch[1] ?? '';
preg_match_all('/<tr[^>]*data-subject-id="([^"]+)"[^>]*>\s*<td>(.*?)<\/td>/si', $mainTable, $rowMatches, PREG_SET_ORDER);
$bladeRows = [];
foreach ($rowMatches as $rowMatch) {
    $bladeRows[] = [
        'subject_id' => $rowMatch[1],
        'subject_name' => trim(html_entity_decode(strip_tags($rowMatch[2]))),
    ];
}

echo json_encode([
    'student' => [
        'id' => (int) $student->id,
        'exam_id' => (int) $exam->id,
        'session' => $student->sessName,
        'class' => $student->className,
        'section' => $student->sectionName,
        'department' => $student->departmentName,
    ],
    'stage1_db_mapping_rows' => $dbRows,
    'stage2_mapping_service_output' => $mappedRows,
    'stage2_applicability_output' => $appRows,
    'stage3_input_builder_output' => $builderRows,
    'stage4_ordering_service_input' => $orderingInput,
    'stage5_ordering_service_output' => $orderingOutput,
    'stage6_presenter_main_rows' => array_map(fn ($row) => [
        'id' => $row['id'],
        'name' => $row['name'],
        'sortOrder' => $row['sortOrder'],
        'sourceIds' => $row['sourceIds'],
    ], $presented['mainRows']),
    'stage7_blade_rendered_main_rows' => $bladeRows,
], JSON_PRETTY_PRINT);
