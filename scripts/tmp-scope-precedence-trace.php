<?php

use App\Http\Controllers\MarksheetController;
use App\Models\CurriculumSubjectMapping;
use App\Models\Subject;
use App\Models\newAdmission;
use App\Services\AcademicSubjectApplicabilityService;
use Illuminate\Http\Request;

$student = newAdmission::findOrFail(66);
$examId = 4;

$sessionId = (string) $student->sessName;
$classId = (string) $student->className;
$sectionId = is_numeric($student->sectionName ?? null) ? (int) $student->sectionName : null;
$departmentId = is_numeric($student->departmentName ?? null) ? (int) $student->departmentName : null;

$exactSectionScope = CurriculumSubjectMapping::normalizeSectionScope($sectionId);
$classWideSectionScope = CurriculumSubjectMapping::normalizeSectionScope(null);
$exactDepartmentScope = CurriculumSubjectMapping::normalizeDepartmentScope($departmentId);
$commonDepartmentScope = CurriculumSubjectMapping::normalizeDepartmentScope(null);

$subjects = Subject::query()->get(['id', 'subjectName', 'subjectType', 'isReligious'])->keyBy('id');
$subjectIdByName = [];
foreach ($subjects as $subject) {
    $subjectIdByName[(string) $subject->subjectName] = (int) $subject->id;
}

$commonSubjectName = 'Bangla 1st Paper';
$businessCandidates = ['Accounting-146', 'Finance and Banking-152', 'Business Entrepreneurship-143'];
$humanitiesCandidates = ['History of Bangladesh and World Civilization-153', 'Civics and Citizenship-140', 'Geography and Environment-110'];

$pickExistingName = static function (array $candidates) use ($subjectIdByName): ?string {
    foreach ($candidates as $candidate) {
        if (array_key_exists($candidate, $subjectIdByName)) {
            return $candidate;
        }
    }

    return null;
};

$businessName = $pickExistingName($businessCandidates);
$humanitiesName = $pickExistingName($humanitiesCandidates);

$traceNames = array_values(array_filter([
    'Physics-136',
    'Chemistry-137',
    'Biology-138',
    $commonSubjectName,
    $businessName,
    $humanitiesName,
]));

$traceSubjectIds = [];
foreach ($traceNames as $name) {
    if (isset($subjectIdByName[$name])) {
        $traceSubjectIds[] = $subjectIdByName[$name];
    }
}

$rows = CurriculumSubjectMapping::query()
    ->where('session_id', $sessionId)
    ->where('class_id', $classId)
    ->where('mapping_type', CurriculumSubjectMapping::TYPE_MAIN)
    ->whereIn('subject_id', $traceSubjectIds)
    ->orderBy('subject_id')
    ->orderBy('sort_order')
    ->orderBy('id')
    ->get(['id', 'subject_id', 'section_id', 'department_id', 'is_active', 'sort_order', 'normalized_section_scope', 'normalized_department_scope']);

$globalRows = CurriculumSubjectMapping::query()
    ->where('session_id', $sessionId)
    ->where('class_id', $classId)
    ->where('mapping_type', CurriculumSubjectMapping::TYPE_MAIN)
    ->where('is_active', 1)
    ->get(['id', 'section_id', 'department_id', 'normalized_section_scope', 'normalized_department_scope']);

$sectionScopeOf = static function ($row) {
    if ($row->normalized_section_scope !== null && $row->normalized_section_scope !== '') {
        return (string) $row->normalized_section_scope;
    }

    $value = is_numeric($row->section_id ?? null) ? (int) $row->section_id : null;
    return CurriculumSubjectMapping::normalizeSectionScope($value);
};

$departmentScopeOf = static function ($row) {
    if ($row->normalized_department_scope !== null && $row->normalized_department_scope !== '') {
        return (string) $row->normalized_department_scope;
    }

    $value = is_numeric($row->department_id ?? null) ? (int) $row->department_id : null;
    return CurriculumSubjectMapping::normalizeDepartmentScope($value);
};

$rankOf = static function (string $sectionScope, string $departmentScope) use (
    $exactSectionScope,
    $classWideSectionScope,
    $exactDepartmentScope,
    $commonDepartmentScope
): int {
    $sectionExact = $sectionScope === $exactSectionScope;
    $sectionClassWide = in_array($sectionScope, [$classWideSectionScope, 'section:all'], true);
    if (!$sectionExact && !$sectionClassWide) {
        return 0;
    }

    $departmentExact = $departmentScope === $exactDepartmentScope;
    $departmentCommon = in_array($departmentScope, [$commonDepartmentScope, 'department:all'], true);
    if (!$departmentExact && !$departmentCommon) {
        return 0;
    }

    if ($sectionExact && $departmentExact) {
        return 4;
    }
    if ($sectionExact && $departmentCommon) {
        return 3;
    }
    if ($sectionClassWide && $departmentExact) {
        return 2;
    }
    if ($sectionClassWide && $departmentCommon) {
        return 1;
    }

    return 0;
};

$rankLabel = static function (int $rank): string {
    return match ($rank) {
        4 => '1) exact section + exact department',
        3 => '2) exact section + common department',
        2 => '3) class-wide section + exact department',
        1 => '4) class-wide section + common department',
        default => 'incompatible',
    };
};

$globalHasExactSectionCommon = false;
foreach ($globalRows as $globalRow) {
    $globalSectionScope = $sectionScopeOf($globalRow);
    $globalDepartmentScope = $departmentScopeOf($globalRow);
    if ($globalSectionScope === $exactSectionScope
        && in_array($globalDepartmentScope, [$commonDepartmentScope, 'department:all'], true)) {
        $globalHasExactSectionCommon = true;
        break;
    }
}

$selectedMetaBySubject = [];
$traceRowsBySubject = [];

foreach ($rows as $row) {
    $subjectId = (int) $row->subject_id;
    $subjectName = (string) ($subjects[$subjectId]->subjectName ?? 'UNKNOWN');
    $sectionScope = $sectionScopeOf($row);
    $departmentScope = $departmentScopeOf($row);
    $rank = ((int) $row->is_active === 1) ? $rankOf($sectionScope, $departmentScope) : 0;
    if ($globalHasExactSectionCommon
        && $rank === 1
        && in_array($sectionScope, [$classWideSectionScope, 'section:all'], true)
        && in_array($departmentScope, [$commonDepartmentScope, 'department:all'], true)) {
        $rank = 0;
    }

    $entry = [
        'subject_id' => $subjectId,
        'subject_name' => $subjectName,
        'section_id' => $row->section_id,
        'department_id' => $row->department_id,
        'is_active' => (int) $row->is_active,
        'sort_order' => (int) $row->sort_order,
        'specificity_rank' => $rank,
        'specificity_label' => $rankLabel($rank),
        'row_id' => (int) $row->id,
    ];

    if (!isset($traceRowsBySubject[$subjectId])) {
        $traceRowsBySubject[$subjectId] = [];
    }
    $traceRowsBySubject[$subjectId][] = $entry;

    if ($rank === 0) {
        continue;
    }

    if (!isset($selectedMetaBySubject[$subjectId])) {
        $selectedMetaBySubject[$subjectId] = [
            'rank' => $rank,
            'sort_order' => (int) $row->sort_order,
            'row_id' => (int) $row->id,
        ];
        continue;
    }

    $current = $selectedMetaBySubject[$subjectId];
    $isBetter = false;
    if ($rank > $current['rank']) {
        $isBetter = true;
    } elseif ($rank === $current['rank'] && (int) $row->sort_order < $current['sort_order']) {
        $isBetter = true;
    } elseif ($rank === $current['rank'] && (int) $row->sort_order === $current['sort_order'] && (int) $row->id < $current['row_id']) {
        $isBetter = true;
    }

    if ($isBetter) {
        $selectedMetaBySubject[$subjectId] = [
            'rank' => $rank,
            'sort_order' => (int) $row->sort_order,
            'row_id' => (int) $row->id,
        ];
    }
}

$traceRows = [];
foreach ($traceRowsBySubject as $subjectId => $subjectRows) {
    $selected = $selectedMetaBySubject[$subjectId] ?? null;

    foreach ($subjectRows as $subjectRow) {
        $reason = 'rejected: incompatible scope';
        if ((int) $subjectRow['is_active'] !== 1) {
            $reason = 'rejected: inactive mapping';
        } elseif ((int) $subjectRow['specificity_rank'] > 0) {
            if ($selected !== null
                && (int) $subjectRow['row_id'] === (int) $selected['row_id']) {
                $reason = 'selected: highest precedence for subject';
            } else {
                $reason = 'rejected: lower precedence or later tie-break for same subject';
            }
        }

        $subjectRow['selection'] = $reason;
        $traceRows[] = $subjectRow;
    }
}

usort($traceRows, static function (array $left, array $right): int {
    if ($left['subject_id'] !== $right['subject_id']) {
        return $left['subject_id'] <=> $right['subject_id'];
    }
    if ($left['sort_order'] !== $right['sort_order']) {
        return $left['sort_order'] <=> $right['sort_order'];
    }
    return $left['row_id'] <=> $right['row_id'];
});

$applicability = app(AcademicSubjectApplicabilityService::class)->subjectsForStudent($student);
$finalApplicability = $applicability->map(static function ($subject) {
    return [
        'subject_id' => (int) $subject->id,
        'subject_name' => (string) $subject->subjectName,
        'subject_type' => (string) $subject->subjectType,
        'applicability_order' => (int) ($subject->applicability_order ?? 0),
        'applicability_source' => (string) ($subject->applicability_source ?? ''),
    ];
})->values()->all();

$html = app(MarksheetController::class)->generateMarksheet(Request::create('/marksheet/generate', 'GET', [
    'studentId' => $student->id,
    'examId' => $examId,
]))->render();

preg_match('/<h3[^>]*>\s*Main Subject\s*<\/h3>\s*<table[^>]*>.*?<tbody>(.*?)<\/tbody>/si', $html, $mainMatch);
$mainTable = $mainMatch[1] ?? '';
preg_match_all('/<tr[^>]*data-subject-id="([^"]+)"[^>]*>\s*<td>(.*?)<\/td>/si', $mainTable, $mainRowsMatch, PREG_SET_ORDER);
$renderedMain = [];
foreach ($mainRowsMatch as $rowMatch) {
    $renderedMain[] = [
        'subject_id' => $rowMatch[1],
        'subject_name' => trim(html_entity_decode(strip_tags($rowMatch[2]))),
    ];
}

preg_match('/<h3[^>]*>\s*Optional Subject\s*<\/h3>\s*<table[^>]*>.*?<tbody>(.*?)<\/tbody>/si', $html, $optionalMatch);
$optionalTable = $optionalMatch[1] ?? '';
preg_match_all('/<tr[^>]*data-subject-id="([^"]+)"[^>]*>\s*<td>(.*?)<\/td>/si', $optionalTable, $optionalRowsMatch, PREG_SET_ORDER);
$renderedOptional = [];
foreach ($optionalRowsMatch as $rowMatch) {
    $renderedOptional[] = [
        'subject_id' => $rowMatch[1],
        'subject_name' => trim(html_entity_decode(strip_tags($rowMatch[2]))),
    ];
}

echo json_encode([
    'student_scope' => [
        'student_id' => (int) $student->id,
        'exam_id' => $examId,
        'session_id' => $sessionId,
        'class_id' => $classId,
        'section_id' => $student->sectionName,
        'department_id' => $student->departmentName,
    ],
    'trace_subject_names' => $traceNames,
    'compatible_mapping_rows_trace' => $traceRows,
    'final_applicability_output' => $finalApplicability,
    'rendered_main_rows' => $renderedMain,
    'rendered_optional_rows' => $renderedOptional,
], JSON_PRETTY_PRINT);
