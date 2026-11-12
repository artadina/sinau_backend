<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\ForumPost;
use App\Models\ForumReport;
use App\Models\Module;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoForumSeeder extends Seeder
{
    // FR-A13, FR-A15, AC-33, AC-34: repeatable local-only forum and moderation examples.
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@sinau.test'],
            [
                'name' => 'Admin Sinau',
                'password' => 'AdminSementara!123',
                'role' => Role::Admin->value,
                'is_temp_password' => false,
                'consent_given' => false,
            ],
        );
        $learners = collect([
            ['email' => 'learner1@sinau.test', 'name' => 'Learner Demo 1'],
            ['email' => 'learner2@sinau.test', 'name' => 'Learner Demo 2'],
        ])->map(fn (array $learner): User => User::query()->firstOrCreate(
            ['email' => $learner['email']],
            [
                'name' => $learner['name'],
                'password' => 'LearnerDemo!123',
                'role' => Role::Learner->value,
                'is_temp_password' => false,
                'consent_given' => true,
            ],
        ));

        $modules = collect([
            ['title' => 'Dasar Pengelolaan Usaha', 'unit' => 'Menyusun Catatan Usaha'],
            ['title' => 'Strategi Pemasaran', 'unit' => 'Mengenali Target Pasar'],
        ])->map(function (array $sample) use ($admin): array {
            $module = Module::query()->firstOrCreate(
                ['title' => $sample['title']],
                [
                    'description' => 'Modul contoh untuk diskusi pembelajaran.',
                    'status' => 'published',
                    'created_by' => $admin->id,
                ],
            );
            $unit = Unit::query()->firstOrCreate(
                ['module_id' => $module->id, 'sequence_order' => 1],
                [
                    'title' => $sample['unit'],
                    'learning_objective' => 'Memahami materi unit.',
                    'video_url' => 'https://example.test/video.mp4',
                    'reading_content' => '<p>Materi pengantar.</p>',
                    'duration' => 300,
                    'is_active' => true,
                ],
            );

            return ['module' => $module, 'unit' => $unit];
        });

        $createPost = function (Module $module, ?Unit $unit, User $author, ?ForumPost $parent, string $content): ForumPost {
            return ForumPost::query()->firstOrCreate(
                [
                    'module_id' => $module->id,
                    'unit_id' => $unit?->id,
                    'user_id' => $author->id,
                    'parent_id' => $parent?->id,
                    'content' => $content,
                ],
            );
        };

        $firstTopic = $createPost(
            $modules[0]['module'],
            $modules[0]['unit'],
            $learners[0],
            null,
            'Bagaimana cara mencatat pemasukan yang berubah-ubah setiap hari?',
        );
        $firstReply = $createPost(
            $modules[0]['module'],
            null,
            $learners[1],
            $firstTopic,
            'Saya mulai dengan mencatat setiap transaksi pada hari yang sama.',
        );
        $createPost(
            $modules[0]['module'],
            null,
            $admin,
            $firstTopic,
            'Catatan harian bisa dikelompokkan menurut jenis pemasukan agar mudah ditinjau.',
        );
        $secondTopic = $createPost(
            $modules[0]['module'],
            null,
            $learners[1],
            null,
            'Apakah biaya pengiriman sebaiknya dicatat terpisah dari biaya bahan?',
        );
        $createPost(
            $modules[0]['module'],
            null,
            $learners[0],
            $secondTopic,
            'Memisahkan kategori biaya membantu melihat pengeluaran dengan lebih jelas.',
        );

        $marketingTopic = $createPost(
            $modules[1]['module'],
            $modules[1]['unit'],
            $learners[0],
            null,
            'Apa yang perlu dipelajari sebelum menentukan target pelanggan?',
        );
        $createPost(
            $modules[1]['module'],
            null,
            $learners[1],
            $marketingTopic,
            'Saya membandingkan kebutuhan pelanggan dan pola pembelian mereka.',
        );
        $createPost(
            $modules[1]['module'],
            null,
            $learners[1],
            null,
            'Bagaimana mengevaluasi apakah pesan pemasaran mudah dipahami?',
        );

        ForumReport::query()->firstOrCreate(
            ['post_id' => $firstReply->id, 'reported_by' => $learners[0]->id],
            [
                'reason' => 'Mohon ditinjau karena balasan kurang sesuai dengan topik.',
                'status' => 'pending',
            ],
        );
        ForumReport::query()->firstOrCreate(
            ['post_id' => $secondTopic->id, 'reported_by' => $learners[0]->id],
            [
                'reason' => 'Saya ingin memastikan topik ini tetap sesuai dengan materi modul.',
                'status' => 'pending',
            ],
        );
    }
}
