<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcademicLookupSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $classNames = [
            'Class 6',
            'Class 7',
            'Class 8',
            'Class 9',
            'Class 10',
        ];

        foreach ($classNames as $className) {
            DB::table('class_manages')->updateOrInsert(
                ['className' => $className],
                ['updated_at' => $now, 'created_at' => $now]
            );

            DB::table('classes')->updateOrInsert(
                ['className' => $className],
                [
                    'alias' => strtolower(str_replace(' ', '_', $className)),
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        foreach (['A', 'B', 'C'] as $sectionName) {
            DB::table('section_manages')->updateOrInsert(
                ['section' => $sectionName],
                ['updated_at' => $now, 'created_at' => $now]
            );
        }

        foreach (['2025', '2026'] as $sessionName) {
            DB::table('session_manages')->updateOrInsert(
                ['session' => $sessionName],
                ['updated_at' => $now, 'created_at' => $now]
            );
        }
    }
}
