<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Module;
use App\Models\ModuleReview;
use App\Models\User;
use App\Notifications\ModuleGovernanceNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ContentGovernanceSeeder extends Seeder
{
    // PS-01–PS-29: local panel demo data exercises a valid submitted review.
    public function run(): void
    {
        $admin = User::query()->where('role', Role::Admin->value)->firstOrFail();
        $superadmins = User::query()->where('role', Role::Superadmin->value)->get();
        $coverPath = 'modules/covers/governance-demo.png';
        Storage::disk('public')->put(
            $coverPath,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASsJTYQAAAAASsJTYQAAAAASUVORK5CYII='),
        );

        $module = Module::query()->firstOrCreate(
            ['title' => 'Contoh Alur Content Governance'],
            [
                'description' => str_repeat('Contoh deskripsi modul untuk menguji alur Content Governance. ', 3),
                'learning_objectives' => "Memahami alur review\nMenerapkan standar publikasi",
                'category' => 'Pengelolaan Usaha',
                'target_audience' => 'Pemilik dan staf UMKM',
                'difficulty' => 'Dasar',
                'cover_image' => $coverPath,
                'status' => 'submitted',
                'created_by' => $admin->id,
                'pre_question_count' => 10,
                'post_question_count' => 10,
                'shuffle_questions' => true,
                'active_from' => today()->addDays(7),
                'active_until' => today()->addDays(37),
                'submitted_at' => now(),
            ],
        );

        for ($sequence = 1; $sequence <= 3; $sequence++) {
            $unit = $module->units()->firstOrCreate(
                ['sequence_order' => $sequence],
                [
                    'title' => "Unit contoh {$sequence}",
                    'learning_objective' => 'Memahami praktik usaha.',
                    'video_url' => "https://example.test/video-{$sequence}.mp4",
                    'duration' => 300,
                    'reading_content' => '<p>'.str_repeat('materi usaha ', 150).'</p>',
                ],
            );

            for ($number = 1; $number <= 3; $number++) {
                $unit->quizzes()->firstOrCreate(
                    ['question_text' => "Kuis unit {$sequence} soal {$number}"],
                    [
                        'options' => ['A' => 'Pilihan A', 'B' => 'Pilihan B', 'C' => 'Pilihan C'],
                        'correct_key' => 'A',
                        'explanation' => 'Pembahasan contoh.',
                    ],
                );
            }
        }

        foreach (['pre', 'post'] as $type) {
            for ($number = 1; $number <= 10; $number++) {
                $module->testQuestions()->firstOrCreate(
                    ['type' => $type, 'question_text' => "Soal {$type} contoh {$number}"],
                    [
                        'options' => ['A' => 'Pilihan A', 'B' => 'Pilihan B', 'C' => 'Pilihan C'],
                        'correct_key' => 'A',
                        'explanation' => 'Pembahasan contoh.',
                    ],
                );
            }
        }

        ModuleReview::query()->firstOrCreate(
            ['module_id' => $module->id, 'action' => 'submitted'],
            [
                'actor_id' => $admin->id,
                'status_from' => 'draft',
                'status_to' => 'submitted',
                'metadata' => ['copyright_confirmation' => true],
                'created_at' => $module->submitted_at,
            ],
        );

        foreach ($superadmins as $superadmin) {
            $alreadyNotified = $superadmin->notifications()
                ->where('type', ModuleGovernanceNotification::class)
                ->where('data->module_id', $module->id)
                ->exists();
            if (! $alreadyNotified) {
                $superadmin->notify(new ModuleGovernanceNotification(
                    'module_submitted',
                    "Modul '{$module->title}' menunggu review.",
                    $module->id,
                ));
            }
        }
    }
}
