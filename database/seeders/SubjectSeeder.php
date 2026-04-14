<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $classIds = DB::table('class_manages')->orderBy('id')->pluck('id')->all();
        $assignClass = !empty($classIds) ? implode(',', $classIds) : null;

        $subjects = [
            ['subjectName' => 'Bangla', 'subjectType' => 'Compulsory', 'passingSystem' => '1', 'CQ' => '70', 'MCQ' => '30', 'Practical' => '0', 'isReligious' => false],
            ['subjectName' => 'English', 'subjectType' => 'Compulsory', 'passingSystem' => '1', 'CQ' => '70', 'MCQ' => '30', 'Practical' => '0', 'isReligious' => false],
            ['subjectName' => 'Mathematics', 'subjectType' => 'Compulsory', 'passingSystem' => '1', 'CQ' => '100', 'MCQ' => '0', 'Practical' => '0', 'isReligious' => false],
            ['subjectName' => 'General Science', 'subjectType' => 'Compulsory', 'passingSystem' => '1', 'CQ' => '70', 'MCQ' => '30', 'Practical' => '0', 'isReligious' => false],
            ['subjectName' => 'ICT', 'subjectType' => 'Compulsory', 'passingSystem' => '1', 'CQ' => '50', 'MCQ' => '25', 'Practical' => '25', 'isReligious' => false],
            ['subjectName' => 'Religious Studies', 'subjectType' => 'Compulsory', 'passingSystem' => '1', 'CQ' => '70', 'MCQ' => '30', 'Practical' => '0', 'isReligious' => true],
        ];

        foreach ($subjects as $subject) {
            DB::table('subjects')->updateOrInsert(
                ['subjectName' => $subject['subjectName']],
                [
                    'alias' => strtolower(str_replace(' ', '_', $subject['subjectName'])),
                    'subjectType' => $subject['subjectType'],
                    'passingSystem' => $subject['passingSystem'],
                    'assign_class' => $assignClass,
                    'CQ' => $subject['CQ'],
                    'MCQ' => $subject['MCQ'],
                    'Practical' => $subject['Practical'],
                    'isReligious' => $subject['isReligious'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        $religiousSubjectId = DB::table('subjects')->where('subjectName', 'Religious Studies')->value('id');

        if ($religiousSubjectId && !empty($classIds)) {
            foreach ($classIds as $classId) {
                DB::table('religious_subject_defaults')->updateOrInsert(
                    ['classId' => $classId],
                    ['subjectId' => $religiousSubjectId, 'updated_at' => $now, 'created_at' => $now]
                );
            }
        }
    }
}
