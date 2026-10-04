<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Data contoh LPK Nihon Bridge (sesuai mockup). Semua akun memakai password "sakura2026".
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PeopleSeeder::class,
            LearningSeeder::class,
            ExamSeeder::class,
            AttendanceSeeder::class,
            OperationsSeeder::class,
            CommunicationSeeder::class,
        ]);
    }
}
