<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CultivationAdmin;

class SetUserTypesSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure every admin has a valid userType: 1=Teacher, 2=Cash, 3=General (default)
        CultivationAdmin::query()->whereNull('userType')->update(['userType' => CultivationAdmin::ROLE_GENERAL]);

        // Clamp any out-of-range values to general
        CultivationAdmin::query()->whereNotIn('userType', [
            CultivationAdmin::ROLE_TEACHER,
            CultivationAdmin::ROLE_CASH,
            CultivationAdmin::ROLE_GENERAL,
        ])->update(['userType' => CultivationAdmin::ROLE_GENERAL]);
    }
}
