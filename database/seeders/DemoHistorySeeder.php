<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Module;
use App\Models\TestAttempt;
use App\Models\TestQuestion;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DemoHistorySeeder extends Seeder
{
    // FR-A06, FR-A08, FR-A10, AC-38, AC-39, AC-40
    public function run(): void
    {
        $admin = User::query()->where('role', Role::Admin->value)->firstOrFail();
        $learners = collect(range(1, 7))->map(function (int $number): User {
            return User::query()->firstOrCreate(
                ['email' => "riwayat{$number}@sinau.test"],
                [
                    'name' => "Learner Riwayat {$number}",
                    'business_name' => "Usaha Contoh {$number}",
                    'password' => 'LearnerDemo!123',
                    'role' => Role::Learner->value,
                    'is_temp_password' => false,
                    'consent_given' => true,
                    'created_at' => now()->subDays(30 - $number),
                    'updated_at' => now()->subDays(30 - $number),
                ],
            );
        });

        $modules = collect([
            ['title' => 'Dasar Pengelolaan Usaha', 'unit_count' => 5],
            ['title' => 'Strategi Pemasaran', 'unit_count' => 3],
        ])->map(function (array $definition) use ($admin): Module {
            $module = Module::query()->firstOrCreate(
                ['title' => $definition['title']],
                [
                    'description' => 'Modul contoh untuk pengujian rekap.',
                    'learning_objectives' => 'Memahami materi dan hasil belajar.',
                    'status' => 'published',
                    'created_by' => $admin->id,
                    'pre_question_count' => 4,
                    'post_question_count' => 4,
                    'shuffle_questions' => true,
                ],
            );

            for ($sequence = 1; $sequence <= $definition['unit_count']; $sequence++) {
                Unit::query()->firstOrCreate(
                    ['module_id' => $module->id, 'sequence_order' => $sequence],
                    [
                        'title' => "Unit {$sequence}",
                        'learning_objective' => 'Memahami materi unit.',
                        'video_url' => 'https://example.test/video.mp4',
                        'reading_content' => '<p>Materi contoh.</p>',
                        'duration' => 300,
                        'is_active' => true,
                    ],
                );
            }

            foreach (['pre', 'post'] as $type) {
                for ($number = 1; $number <= 4; $number++) {
                    TestQuestion::query()->firstOrCreate(
                        [
                            'module_id' => $module->id,
                            'type' => $type,
                            'question_text' => "Soal {$type} {$number} {$module->id}",
                        ],
                        [
                            'options' => ['A' => 'Pilihan A', 'B' => 'Pilihan B', 'C' => 'Pilihan C'],
                            'correct_key' => 'A',
                            'explanation' => 'Pembahasan contoh.',
                        ],
                    );
                }
            }

            return $module;
        });

        $mainModule = $modules->first();
        $secondModule = $modules->last();

        $attempts = [
            [0, $mainModule, 'pre', 50, 20],
            [0, $mainModule, 'post', 75, 12],
            [1, $mainModule, 'pre', 25, 18],
            [1, $mainModule, 'post', 50, 10],
            [1, $mainModule, 'post', 100, 4],
            [2, $mainModule, 'pre', 75, 16],
            [4, $mainModule, 'pre', 50, 14],
            [4, $secondModule, 'pre', 25, 13],
            [4, $secondModule, 'post', 75, 8],
            [5, $secondModule, 'pre', 100, 11],
            [5, $secondModule, 'post', 50, 6],
        ];

        foreach ($attempts as [$learnerIndex, $module, $type, $score, $daysAgo]) {
            $completedAt = Carbon::now()->subDays($daysAgo)->startOfHour();
            $attempt = TestAttempt::query()->firstOrCreate(
                [
                    'user_id' => $learners[$learnerIndex]->id,
                    'module_id' => $module->id,
                    'type' => $type,
                    'score' => $score,
                ],
                ['completed_at' => $completedAt],
            );

            $questions = TestQuestion::query()
                ->where('module_id', $module->id)
                ->where('type', $type)
                ->orderBy('id')
                ->get();
            $correctCount = (int) round(($score / 100) * $questions->count());

            foreach ($questions as $index => $question) {
                $isCorrect = $index < $correctCount;
                DB::table('test_attempt_answers')->updateOrInsert(
                    ['attempt_id' => $attempt->id, 'question_id' => $question->id],
                    [
                        'selected_key' => $isCorrect ? $question->correct_key : 'B',
                        'is_correct' => $isCorrect,
                    ],
                );
            }
        }

        foreach ([
            [0, $mainModule, [1, 2, 3]],
            [1, $mainModule, [1, 2]],
            [2, $mainModule, [1]],
            [4, $secondModule, [1, 2]],
        ] as [$learnerIndex, $module, $completedSequences]) {
            $units = $module->units()->whereIn('sequence_order', $completedSequences)->get();

            foreach ($units as $unit) {
                DB::table('learner_unit_progress')->updateOrInsert(
                    ['user_id' => $learners[$learnerIndex]->id, 'unit_id' => $unit->id],
                    [
                        'last_video_pos' => 120,
                        'is_completed' => true,
                        'updated_at' => now(),
                    ],
                );
            }
        }
    }
}
