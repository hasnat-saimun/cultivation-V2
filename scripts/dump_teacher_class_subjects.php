<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::table('teacher_class_subjects')->orderBy('teacher_id')->get();
if($rows->isEmpty()){
    echo "No entries in teacher_class_subjects\n";
    exit(0);
}
$current = null;
foreach($rows as $r){
    if($current !== $r->teacher_id){
        $current = $r->teacher_id;
        $user = \App\Models\CultivationAdmin::find($current);
        echo "User ID: {$current} - " . ($user ? $user->adminName : '(unknown)') . "\n";
    }
    $cls = \App\Models\classManage::find($r->class_id);
    $sec = $r->section_id ? \App\Models\sectionManage::find($r->section_id) : null;
    $sub = $r->subject_id ? \App\Models\Subject::find($r->subject_id) : null;
    echo "  - Class: " . ($cls ? $cls->className : $r->class_id) . ", Section: " . ($sec ? $sec->section : ($r->section_id===null? 'No Section' : 'All Sections')) . ", Subject: " . ($sub ? $sub->subjectName : 'No Subject') . "\n";
}
