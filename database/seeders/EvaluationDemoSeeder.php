<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\TestAttempt;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EvaluationDemoSeeder extends Seeder
{
    // FR-L17–FR-L23, AB-05: three demo Learner evaluation states.
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Data evaluasi demo tidak boleh dibuat di production.');
        }

        $module = Module::query()->where('status', 'published')->orderBy('id')->first();
        if ($module === null) {
            return;
        }

        $learnerTwo = User::query()->where('email', 'learner2@sinau.test')->first();
        $learnerThree = User::query()->where('email', 'learner3@sinau.test')->first();

        if ($learnerTwo !== null) {
            TestAttempt::query()->firstOrCreate([
                'user_id' => $learnerTwo->id,
                'module_id' => $module->id,
                'type' => 'pre',
            ], [
                'score' => 50,
                'completed_at' => now()->subDay(),
            ]);
        }

        if ($learnerThree !== null) {
            TestAttempt::query()->firstOrCreate([
                'user_id' => $learnerThree->id,
                'module_id' => $module->id,
                'type' => 'pre',
            ], [
                'score' => 75,
                'completed_at' => now()->subDay(),
            ]);
            foreach ($module->units()->where('is_active', true)->get() as $unit) {
                DB::table('learner_unit_progress')->updateOrInsert(
                    ['user_id' => $learnerThree->id, 'unit_id' => $unit->id],
                    ['last_video_pos' => 300, 'is_completed' => true, 'updated_at' => now()],
                );
            }
        }

        // Learner 1 intentionally remains without pre-test.
        unset($learnerOne);
    }
}
