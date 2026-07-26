<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Department;
use App\Models\Subject;
use App\Models\classManage as ClassModel;
use App\Models\sectionManage as SectionModel;
use App\Models\sessionManage;
use Illuminate\Support\Facades\DB;

$rows = DB::table('teacher_class_subjects')->get(['id', 'teacher_id', 'class_id', 'section_id', 'group_id', 'subject_id', 'gender_scope', 'session_id']);
$classNames = ClassModel::query()->pluck('className', 'id');
$sectionNames = SectionModel::query()->pluck('section', 'id');
$groupNames = Department::query()->pluck('departmentName', 'id');
$subjectNames = Subject::query()->pluck('subjectName', 'id');
$sessionNames = sessionManage::query()->pluck('session', 'id');

$collisions = [];
foreach ($rows as $row) {
    $sessionName = $row->session_id ? ($sessionNames[(int) $row->session_id] ?? ('Session '.$row->session_id)) : 'Legacy Session';
    $className = $classNames[(int) $row->class_id] ?? ('Class '.$row->class_id);
    $sectionName = $row->section_id ? ($sectionNames[(int) $row->section_id] ?? ('Section '.$row->section_id)) : null;
    $groupName = $row->group_id ? ($groupNames[(int) $row->group_id] ?? ('Group '.$row->group_id)) : null;
    $gender = strtolower(trim((string) ($row->gender_scope ?: 'all')));

    $label = $sessionName.' / '.$className;
    if ($sectionName) {
        $label .= ' / '.$sectionName;
    }
    if ($groupName) {
        $label .= ' / '.$groupName;
    }
    $label .= ' / Gender: '.$gender;

    $subjectName = $row->subject_id ? ($subjectNames[(int) $row->subject_id] ?? ('Subject '.$row->subject_id)) : null;
    if (!$subjectName) {
        continue;
    }

    $key = (int) $row->teacher_id.'|'.$label.'|'.$subjectName;
    if (!isset($collisions[$key])) {
        $collisions[$key] = [
            'teacher_id' => (int) $row->teacher_id,
            'label' => $label,
            'subject_name' => $subjectName,
            'subject_ids' => [],
            'row_ids' => [],
        ];
    }

    $collisions[$key]['subject_ids'][(int) $row->subject_id] = true;
    $collisions[$key]['row_ids'][(int) $row->id] = true;
}

$out = [];
foreach ($collisions as $collision) {
    $subjectIds = array_keys($collision['subject_ids']);
    if (count($subjectIds) > 1) {
        $collision['subject_ids'] = $subjectIds;
        $collision['row_ids'] = array_keys($collision['row_ids']);
        $out[] = $collision;
    }
}

echo json_encode([
    'collision_count' => count($out),
    'collisions' => array_slice($out, 0, 25),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), PHP_EOL;
