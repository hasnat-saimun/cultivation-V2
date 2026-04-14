<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExamSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $latestSession = DB::table('session_manages')->orderByDesc('id')->value('session') ?? date('Y');
        $defaultClassId = DB::table('class_manages')->orderBy('id')->value('id');

        if (!$defaultClassId) {
            return;
        }

        $exams = [
            ['examName' => '1st Term Exam', 'monthOffset' => 2],
            ['examName' => 'Mid Term Exam', 'monthOffset' => 5],
            ['examName' => 'Final Exam', 'monthOffset' => 10],
        ];

        foreach ($exams as $exam) {
            $examDate = now()->startOfYear()->addMonths($exam['monthOffset'])->format('Y-m-d');
            $closeDate = now()->startOfYear()->addMonths($exam['monthOffset'])->addDays(7)->format('Y-m-d');

            DB::table('exams')->updateOrInsert(
                ['examName' => $exam['examName'], 'className' => (string) $defaultClassId],
                [
                    'alias' => strtolower(str_replace(' ', '_', $exam['examName'])) . '_' . $latestSession,
                    'examDate' => $examDate,
                    'closeDate' => $closeDate,
                    'baseMark' => '100',
                    'passingSystem' => '1',
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }
}
