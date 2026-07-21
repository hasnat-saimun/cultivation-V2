<?php

namespace Tests\Feature;

use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_writes_occur_only_on_testing_database_and_protected_counts_stay_unchanged(): void
    {
        $defaultConnection = config('database.default');
        $this->assertSame('mysql', $defaultConnection);
        $this->assertSame('cultivation_test', config('database.connections.mysql.database'));

        config()->set('database.connections.protected_backup', [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('PROTECTED_DB_DATABASE', 'cultivationbackup'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ]);

        $before = [
            'cultivation_admins' => DB::connection('protected_backup')->table('cultivation_admins')->count(),
            'subjects' => DB::connection('protected_backup')->table('subjects')->count(),
            'teacher_subjects' => DB::connection('protected_backup')->table('teacher_subjects')->count(),
            'teacher_class_subjects' => DB::connection('protected_backup')->table('teacher_class_subjects')->count(),
        ];

        $subject = new Subject();
        $subject->subjectName = 'Isolation Test Subject';
        $subject->subjectType = 'Theory';
        $subject->save();

        $this->assertDatabaseHas('subjects', ['subjectName' => 'Isolation Test Subject']);

        $after = [
            'cultivation_admins' => DB::connection('protected_backup')->table('cultivation_admins')->count(),
            'subjects' => DB::connection('protected_backup')->table('subjects')->count(),
            'teacher_subjects' => DB::connection('protected_backup')->table('teacher_subjects')->count(),
            'teacher_class_subjects' => DB::connection('protected_backup')->table('teacher_class_subjects')->count(),
        ];

        $this->assertSame($before, $after);
    }
}
