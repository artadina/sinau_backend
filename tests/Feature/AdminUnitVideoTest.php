<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUnitVideoTest extends TestCase
{
    use RefreshDatabase;

    // FR-S07: supported YouTube URL forms are converted to a safe embed URL in the Admin preview.
    public function test_youtube_links_play_in_the_admin_unit_preview(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin->value,
            'is_temp_password' => false,
        ]);
        $module = Module::query()->create([
            'title' => 'Modul Video',
            'description' => '',
            'learning_objectives' => null,
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);

        foreach ([
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://youtu.be/dQw4w9WgXcQ',
            'https://m.youtube.com/shorts/dQw4w9WgXcQ',
            'https://youtube.com/embed/dQw4w9WgXcQ',
            'https://youtube.com/live/dQw4w9WgXcQ',
        ] as $index => $videoUrl) {
            $unit = $module->units()->create([
                'title' => 'Unit video '.($index + 1),
                'learning_objective' => 'Memahami materi video.',
                'video_url' => $videoUrl,
                'duration' => 60,
                'reading_content' => '',
                'sequence_order' => $index + 1,
            ]);

            $this->actingAs($admin)
                ->get(route('admin.units.preview', $unit))
                ->assertOk()
                ->assertSee('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', false)
                ->assertSee('allowfullscreen', false)
                ->assertDontSee('<video controls', false);
        }
    }

    // FR-S07: legacy direct video URLs still use the native player.
    public function test_direct_video_urls_keep_the_native_video_player(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin->value,
            'is_temp_password' => false,
        ]);
        $module = Module::query()->create([
            'title' => 'Modul Video',
            'description' => '',
            'learning_objectives' => null,
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);
        $unit = $module->units()->create([
            'title' => 'Unit video langsung',
            'learning_objective' => 'Memahami materi video.',
            'video_url' => 'https://cdn.example.test/video.mp4',
            'duration' => 60,
            'reading_content' => '',
            'sequence_order' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.units.preview', $unit))
            ->assertOk()
            ->assertSee('<video controls', false)
            ->assertSee('https://cdn.example.test/video.mp4', false)
            ->assertDontSee('<iframe', false);
    }

    // FR-S07, AC-42: malformed or insecure YouTube URLs are rejected by server-side validation.
    public function test_invalid_youtube_urls_are_rejected_when_saving_a_unit(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin->value,
            'is_temp_password' => false,
        ]);
        $module = Module::query()->create([
            'title' => 'Modul Video',
            'description' => '',
            'learning_objectives' => null,
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);

        foreach ([
            'https://www.youtube.com/watch?v=bad',
            'http://youtu.be/dQw4w9WgXcQ',
            'https://youtube.com/playlist?list=PL1234567890',
        ] as $videoUrl) {
            $this->actingAs($admin)
                ->from(route('admin.modules.units.create', $module))
                ->post(route('admin.modules.units.store', $module), [
                    'title' => 'Unit video',
                    'learning_objective' => 'Memahami materi video.',
                    'video_url' => $videoUrl,
                    'duration' => 60,
                    'sequence_order' => 1,
                ])
                ->assertSessionHasErrors([
                    'video_url' => 'Tautan YouTube tidak valid atau formatnya belum didukung.',
                ]);
        }
    }
}
