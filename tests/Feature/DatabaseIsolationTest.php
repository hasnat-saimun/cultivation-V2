<?php

namespace Tests\Feature;

use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DatabaseIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_writes_occur_only_on_testing_database_and_protected_counts_stay_unchanged(): void
    {
        $defaultConnection = config('database.default');
        $this->assertSame('mysql', $defaultConnection);
        $this->assertSame('cultivation_test', config('database.connections.mysql.database'));

        $temporaryDatabase = 'cultivation_isolation_test_'.Str::lower(Str::random(12));
        $this->assertMatchesRegularExpression('/^cultivation_isolation_test_[a-z0-9]{12}$/', $temporaryDatabase);
        $adminConnection = array_replace(config('database.connections.mysql'), ['database' => null]);
        config()->set('database.connections.isolation_admin', $adminConnection);
        DB::purge('isolation_admin');
        DB::connection('isolation_admin')->statement(
            "CREATE DATABASE `{$temporaryDatabase}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );

        $protectedConnection = array_replace(config('database.connections.mysql'), ['database' => $temporaryDatabase]);
        config()->set('database.connections.protected_backup', $protectedConnection);
        DB::purge('protected_backup');

        $testingDatabase = (string) config('database.connections.mysql.database');
        foreach (['cultivation_admins', 'subjects', 'teacher_subjects', 'teacher_class_subjects'] as $table) {
            DB::connection('isolation_admin')->statement(
                "CREATE TABLE `{$temporaryDatabase}`.`{$table}` LIKE `{$testingDatabase}`.`{$table}`"
            );
        }

        try {
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
        } finally {
            DB::disconnect('protected_backup');
            DB::connection('isolation_admin')->statement("DROP DATABASE `{$temporaryDatabase}`");
            DB::purge('protected_backup');
            DB::purge('isolation_admin');
        }
    }
}
