<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // FR-A01, FR-A12, FR-SA01: development accounts for the staged panel foundation.
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'superadmin@sinau.test'],
            [
                'name' => 'Superadmin Sinau',
                'password' => 'Password!123',
                'role' => 'superadmin',
                'is_temp_password' => false,
                'consent_given' => false,
            ],
        );

        User::firstOrCreate(
            ['email' => 'admin@sinau.test'],
            [
                'name' => 'Admin Sinau',
                'password' => 'AdminSementara!123',
                'role' => 'admin',
                'is_temp_password' => true,
                'consent_given' => false,
            ],
        );

        foreach ([
            ['email' => 'learner1@sinau.test', 'name' => 'Learner Demo 1'],
            ['email' => 'learner2@sinau.test', 'name' => 'Learner Demo 2'],
        ] as $learner) {
            User::firstOrCreate(
                ['email' => $learner['email']],
                [
                    'name' => $learner['name'],
                    'password' => 'LearnerDemo!123',
                    'role' => 'learner',
                    'is_temp_password' => false,
                    'consent_given' => true,
                ],
            );
        }
    }
}
