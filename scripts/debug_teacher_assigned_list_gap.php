<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CultivationAdmin;
use App\Models\Department;
use App\Models\Subject;
use App\Models\classManage as ClassModel;
use App\Models\sectionManage as SectionModel;
use App\Models\sessionManage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$teachers = CultivationAdmin::query()->where('userType', CultivationAdmin::ROLE_TEACHER)->get(['id', 'adminName']);
$teacherIds = $teachers->pluck('id')->map(fn ($id) => (int) $id)->all();

if ($teacherIds === []) {
    echo json_encode(['error' => 'No teachers found'], JSON_PRETTY_PRINT), PHP_EOL;
    exit(0);
}

$columns = ['id', 'teacher_id', 'class_id', 'section_id', 'group_id', 'subject_id', 'gender_scope', 'created_at', 'updated_at'];
if (Schema::hasColumn('teacher_class_subjects', 'session_id')) {
    $columns[] = 'session_id';
}
if (Schema::hasColumn('teacher_class_subjects', 'status')) {
    $columns[] = 'status';
}
if (Schema::hasColumn('teacher_class_subjects', 'active')) {
    $columns[] = 'active';
}

$rows = DB::table('teacher_class_subjects')->whereIn('teacher_id', $teacherIds)->get($columns);

$classNames = ClassModel::query()->pluck('className', 'id');
$sectionNames = SectionModel::query()->pluck('section', 'id');
$groupNames = Department::query()->pluck('departmentName', 'id');
$subjectNames = Subject::query()->pluck('subjectName', 'id');
$sessionNames = sessionManage::query()->pluck('session', 'id');

$results = [];
foreach ($teachers as $teacher) {
    $teacherRows = $rows->where('teacher_id', (int) $teacher->id)->values();
    if ($teacherRows->isEmpty()) {
        continue;
    }

    $assignmentBuckets = [];
    foreach ($teacherRows as $row) {
        $rowSessionId = property_exists($row, 'session_id') ? $row->session_id : null;
        $sessionName = $rowSessionId ? ($sessionNames[(int) $rowSessionId] ?? ('Session '.$rowSessionId)) : 'Legacy Session';
        $className = $classNames[(int) $row->class_id] ?? ('Class '.$row->class_id);
        $sectionName = $row->section_id ? ($sectionNames[(int) $row->section_id] ?? ('Section '.$row->section_id)) : null;
        $groupName = $row->group_id ? ($groupNames[(int) $row->group_id] ?? ('Group '.$row->group_id)) : null;
        $label = $sessionName.' / '.$className;
        if ($sectionName) {
            $label .= ' / '.$sectionName;
        }
        if ($groupName) {
            $label .= ' / '.$groupName;
        }
        $label .= ' / Gender: '.($row->gender_scope ?: 'all');

        $subjectName = $row->subject_id ? ($subjectNames[(int) $row->subject_id] ?? null) : null;
        if (!$subjectName) {
            continue;
        }

        $assignmentBuckets[$label][] = $subjectName;
    }

    $renderedCount = 0;
    foreach ($assignmentBuckets as $subjects) {
        $renderedCount += count(array_values(array_unique(array_filter($subjects))));
    }

    $dbDistinctSubjectIds = $teacherRows->pluck('subject_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
    $dbCount = $dbDistinctSubjectIds->count();

    if ($renderedCount < $dbCount) {
        $renderedSubjectNames = collect($assignmentBuckets)->flatten(1)->unique()->values();
        $missing = $dbDistinctSubjectIds->filter(function (int $subjectId) use ($subjectNames, $renderedSubjectNames) {
            $name = $subjectNames[$subjectId] ?? null;
            if ($name === null) {
                return true;
            }
            return !$renderedSubjectNames->contains($name);
        })->values()->all();

        $results[] = [
            'teacher_id' => (int) $teacher->id,
            'teacher_name' => $teacher->adminName,
            'db_row_count' => $teacherRows->count(),
            'db_distinct_subject_count' => $dbCount,
            'rendered_subject_count' => $renderedCount,
            'missing_subject_ids' => $missing,
            'missing_subject_names' => array_map(fn ($id) => $subjectNames[$id] ?? null, $missing),
        ];
    }
}

echo json_encode(['gaps' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), PHP_EOL;
