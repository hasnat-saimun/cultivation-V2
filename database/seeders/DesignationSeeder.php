<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DesignationSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $designations = [
            ['name' => 'Assistant Teacher', 'type' => 'teacher', 'sort_order' => 1],
            ['name' => 'Senior Teacher', 'type' => 'teacher', 'sort_order' => 2],
            ['name' => 'Head Teacher', 'type' => 'teacher', 'sort_order' => 3],
            ['name' => 'Office Assistant', 'type' => 'staff', 'sort_order' => 1],
            ['name' => 'Accountant', 'type' => 'staff', 'sort_order' => 2],
            ['name' => 'Committee Member', 'type' => 'committee', 'sort_order' => 1],
            ['name' => 'Secretary', 'type' => 'committee', 'sort_order' => 2],
            ['name' => 'President', 'type' => 'committee', 'sort_order' => 3],
        ];

        foreach ($designations as $designation) {
            DB::table('designations')->updateOrInsert(
                ['name' => $designation['name'], 'type' => $designation['type']],
                [
                    'is_active' => true,
                    'sort_order' => $designation['sort_order'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }
}
