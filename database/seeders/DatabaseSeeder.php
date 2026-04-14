<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ServerConfigSeeder::class,
            AcademicLookupSeeder::class,
            DesignationSeeder::class,
            SubjectSeeder::class,
            ExamSeeder::class,
            CultivationAdminSeeder::class,
            SetUserTypesSeeder::class,
        ]);

        User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => 'password']
        );
    }
}
