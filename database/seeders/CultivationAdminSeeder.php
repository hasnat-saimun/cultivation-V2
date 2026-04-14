<?php

namespace Database\Seeders;

use App\Models\CultivationAdmin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CultivationAdminSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $adminExists = DB::table('cultivation_admins')->where('adminUser', 'admin')->exists();

        if ($adminExists) {
            return;
        }

        DB::table('cultivation_admins')->insert([
            'adminName' => 'System Admin',
            'adminUser' => 'admin',
            'userType' => (string) CultivationAdmin::ROLE_GENERAL,
            'loginPassword' => Hash::make('admin123'),
            'adminMobile' => '01700000001',
            'adminMail' => 'admin@cultivation.local',
            'primary_class_id' => null,
            'primary_section_id' => null,
            'avatar' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
