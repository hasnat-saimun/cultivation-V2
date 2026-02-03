<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CultivationAdmin;

$teachers = CultivationAdmin::where('userType', 1)->get();
if ($teachers->isEmpty()) {
    echo "No teacher users found.\n";
    exit(0);
}
foreach ($teachers as $t) {
    $classes = $t->classes()->pluck('class_id')->toArray();
    $subjects = $t->subjects()->pluck('subject_id')->toArray();
    $sections = $t->sections()->pluck('section_id')->toArray();
    echo "User ID: {$t->id} - {$t->adminName}\n";
    echo "  Classes: " . (empty($classes) ? '(none)' : implode(',', $classes)) . "\n";
    echo "  Subjects: " . (empty($subjects) ? '(none)' : implode(',', $subjects)) . "\n";
    echo "  Sections: " . (empty($sections) ? '(none)' : implode(',', $sections)) . "\n";
    echo str_repeat('-', 40) . "\n";
}
