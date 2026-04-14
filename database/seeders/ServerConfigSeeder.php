<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServerConfigSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('server_configs')->updateOrInsert(
            ['id' => 1],
            [
                'instituteName' => 'Cultivation High School',
                'address' => 'Dhaka, Bangladesh',
                'officeMobile' => '01700000000',
                'officeEmail' => 'info@cultivation.local',
                'studentIdPrefix' => 'STD',
                'teacherIdPrefix' => 'TCH',
                'staffIdPrefix' => 'STF',
                'sm_on_off' => '0',
                'sms_type' => null,
                'sms_body_present' => 'Dear Guardian, your child attended school today.',
                'sms_body_absent' => 'Dear Guardian, your child is absent today. Please contact the school office.',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }
}
