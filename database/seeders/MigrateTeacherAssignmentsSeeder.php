<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CultivationAdmin;

class MigrateTeacherAssignmentsSeeder extends Seeder
{
    public function run(): void
    {
        $teachers = CultivationAdmin::where('userType', CultivationAdmin::ROLE_TEACHER)->get();
        foreach($teachers as $t){
            // Parse legacy fields
            $classStr = (string)($t->getAttributes()['accessClass'] ?? '');
            $subStr   = (string)($t->getAttributes()['accessSubject'] ?? '');
            $classIds = array_filter(array_map('intval', array_map('trim', $classStr ? explode(',', $classStr) : [])));
            $subIds   = array_filter(array_map('intval', array_map('trim', $subStr ? explode(',', $subStr) : [])));

            if(!empty($classIds)){
                $t->classes()->syncWithoutDetaching($classIds);
            }
            if(!empty($subIds)){
                $t->subjects()->syncWithoutDetaching($subIds);
            }
        }
    }
}
