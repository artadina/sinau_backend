<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    // FR-A01, FR-A12, FR-SA01: non-production accounts for the staged panel foundation.
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Seeder akun dan data demo tidak boleh dijalankan di production.');
        }

        User::firstOrCreate(
            ['email' => 'superadmin@sinau.test'],
            [
                'name' => 'Superadmin Sinau',
                'password' => 'Password!123',
                'role' => Role::Superadmin->value,
                'is_temp_password' => false,
                'consent_given' => false,
            ],
        );

        User::firstOrCreate(
            ['email' => 'admin@sinau.test'],
            [
                'name' => 'Admin Sinau',
                'password' => 'AdminSementara!123',
                'role' => Role::Admin->value,
                'is_temp_password' => true,
                'consent_given' => false,
            ],
        );

        User::firstOrCreate(
            ['email' => 'admin2@sinau.test'],
            [
                'name' => 'Admin Demo 2',
                'password' => 'AdminSementara!234',
                'role' => Role::Admin->value,
                'is_temp_password' => true,
                'consent_given' => false,
            ],
        );

        foreach ([
            ['email' => 'learner1@sinau.test', 'name' => 'Learner Demo 1'],
            ['email' => 'learner2@sinau.test', 'name' => 'Learner Demo 2'],
            ['email' => 'learner3@sinau.test', 'name' => 'Learner Demo 3'],
            ['email' => 'learner4@sinau.test', 'name' => 'Learner Demo 4'],
        ] as $learner) {
            $consentGiven = $learner['email'] !== 'learner4@sinau.test';
            User::firstOrCreate(
                ['email' => $learner['email']],
                [
                    'name' => $learner['name'],
                    'password' => 'LearnerDemo!123',
                    'role' => Role::Learner->value,
                    'is_temp_password' => false,
                    'consent_given' => $consentGiven,
                    'consent_at' => $consentGiven ? now() : null,
                ],
            );
        }

        // FR-A06, FR-A08, FR-A10, FR-A13, FR-A15: non-production panel examples.
        $this->call(DemoHistorySeeder::class);
        $this->call(DemoForumSeeder::class);
        if (app()->environment('local')) {
            $this->call(ContentGovernanceSeeder::class);
        }
    }
}
