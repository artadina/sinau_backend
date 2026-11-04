<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Module;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminModuleManagementTest extends TestCase
{
    use RefreshDatabase;

    // FR-A02, AB-12: every Admin sees shared modules, including those created by another Admin.
    public function test_admin_can_see_modules_created_by_other_admins(): void
    {
        $admin = $this->admin();
        $otherAdmin = $this->admin();
        $module = $this->module($otherAdmin);

        $this->actingAs($admin)
            ->get(route('admin.modules.index'))
            ->assertOk()
            ->assertSee($module->title);
    }

    // FR-A02, AC-42: cover URLs use the active request host and forms identify required fields.
    public function test_module_cover_and_field_labels_use_the_current_request(): void
    {
        $admin = $this->admin();
        $module = $this->module($admin, [
            'cover_image' => 'modules/covers/cover.jpg',
        ]);
        $coverUrl = 'http://127.0.0.1:8000/storage/modules/covers/cover.jpg';

        $this->actingAs($admin)
            ->get('http://127.0.0.1:8000/admin/modules')
            ->assertOk()
            ->assertSee($coverUrl, false);

        $this->get("http://127.0.0.1:8000/admin/modules/{$module->id}/edit")
            ->assertOk()
            ->assertSee($coverUrl, false)
            ->assertSee('Judul modul')
            ->assertSee('Opsional')
            ->assertSee('text-rose-600', false);
    }

    // FR-A02, AC-42: module form validation returns field-specific Indonesian errors.
    public function test_module_title_is_required_with_an_indonesian_message(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.modules.store'), ['title' => ''])
            ->assertSessionHasErrors(['title' => 'Judul modul wajib diisi.']);
    }

    // FR-A02: Admin can update and delete a module that has no protected history.
    public function test_admin_can_update_and_delete_module_without_test_history(): void
    {
        $admin = $this->admin();
        $module = $this->module($admin);

        $this->actingAs($admin)->get(route('admin.modules.create'))->assertOk();
        $this->get(route('admin.modules.edit', $module))->assertOk();

        $this->put(route('admin.modules.update', $module), [
            'title' => 'Modul yang diperbarui',
            'description' => 'Deskripsi singkat.',
            'learning_objectives' => 'Tujuan belajar modul.',
        ])
            ->assertRedirect(route('admin.modules.index'))
            ->assertSessionHas('status', 'Modul berhasil diperbarui.');

        $this->assertDatabaseHas('modules', [
            'id' => $module->id,
            'title' => 'Modul yang diperbarui',
            'description' => 'Deskripsi singkat.',
        ]);

        $this->delete(route('admin.modules.destroy', $module))
            ->assertRedirect(route('admin.modules.index'))
            ->assertSessionHas('status', 'Modul berhasil dihapus.');

        $this->assertDatabaseMissing('modules', ['id' => $module->id]);
    }

    // FR-A03, FR-A04: unit validation, reordering, and learner-style preview are available.
    public function test_admin_can_manage_ordered_units_and_preview_reading_content(): void
    {
        $admin = $this->admin();
        $module = $this->module($admin);

        $this->actingAs($admin)
            ->post(route('admin.modules.units.store', $module), [
                'title' => 'Unit pertama',
                'learning_objective' => 'Memahami materi dasar.',
                'video_url' => 'https://example.test/first.mp4',
                'duration_minutes' => 1,
                'reading_content' => 'Bacaan pratinjau.',
                'sequence_order' => 1,
            ])
            ->assertRedirect(route('admin.modules.units.index', $module));

        $firstUnit = $module->units()->firstOrFail();
        $secondUnit = Unit::query()->create([
            'module_id' => $module->id,
            'title' => 'Unit kedua',
            'learning_objective' => 'Memahami materi lanjutan.',
            'video_url' => 'https://example.test/second.mp4',
            'duration' => 90,
            'reading_content' => 'Bacaan pratinjau.',
            'sequence_order' => 2,
        ]);

        $this->patch(route('admin.modules.units.move', [$module, $secondUnit, 'up']))
            ->assertRedirect(route('admin.modules.units.index', $module));

        $this->assertSame(1, $secondUnit->fresh()->sequence_order);
        $this->assertSame(2, $firstUnit->fresh()->sequence_order);

        $this->get(route('admin.units.preview', $secondUnit))
            ->assertOk()
            ->assertSee('Bacaan pratinjau.');
    }

    // FR-A03, AC-42: unit forms mark mandatory and optional fields.
    public function test_unit_form_identifies_required_and_optional_fields(): void
    {
        $admin = $this->admin();
        $module = $this->module($admin);

        $this->actingAs($admin)
            ->get(route('admin.modules.units.create', $module))
            ->assertOk()
            ->assertSee('Judul unit')
            ->assertSee('Tujuan belajar (satu kalimat)')
            ->assertSee('Tautan video')
            ->assertSee('Durasi (menit)')
            ->assertSee('Urutan unit')
            ->assertSee('Opsional')
            ->assertSee('text-rose-600', false);
    }

    // FR-A03: Admin may omit duration or enter it in minutes while storage remains seconds.
    public function test_unit_duration_is_optional_and_converted_from_minutes_to_seconds(): void
    {
        $admin = $this->admin();
        $module = $this->module($admin);

        $this->actingAs($admin)
            ->post(route('admin.modules.units.store', $module), [
                'title' => 'Unit dengan durasi',
                'learning_objective' => 'Memahami durasi unit.',
                'video_url' => 'https://example.test/video.mp4',
                'duration_minutes' => '8.78',
                'sequence_order' => 1,
            ])
            ->assertRedirect(route('admin.modules.units.index', $module));

        $unit = $module->units()->firstOrFail();
        $this->assertSame(527, $unit->duration);
        $this->assertSame('8,78 menit', $unit->duration_label);

        $this->post(route('admin.modules.units.store', $module), [
            'title' => 'Unit tanpa durasi',
            'learning_objective' => 'Memahami unit tanpa durasi.',
            'video_url' => 'https://example.test/second.mp4',
            'sequence_order' => 2,
        ])
            ->assertRedirect(route('admin.modules.units.index', $module));

        $unitWithoutDuration = $module->units()->where('sequence_order', 2)->firstOrFail();
        $this->assertNull($unitWithoutDuration->duration);
        $this->get(route('admin.units.preview', $unitWithoutDuration))
            ->assertOk()
            ->assertDontSee('Tidak dicantumkan')
            ->assertDontSee('0 menit');

        $this->get(route('admin.modules.units.index', $module))
            ->assertOk()
            ->assertSee('8,78 menit')
            ->assertSee('Tidak dicantumkan');

        $this->get(route('admin.modules.units.edit', [$module, $unit]))
            ->assertOk()
            ->assertSee('value="8.78"', false)
            ->assertSee('Durasi (menit)')
            ->assertSee('Opsional');
    }

    // FR-A03: Admin can update and delete a unit without quiz history.
    public function test_admin_can_update_and_delete_unit_without_quiz_history(): void
    {
        $admin = $this->admin();
        $module = $this->module($admin);
        $unit = $module->units()->create([
            'title' => 'Unit awal',
            'learning_objective' => 'Memahami materi awal.',
            'video_url' => 'https://example.test/initial.mp4',
            'duration' => 60,
            'reading_content' => '',
            'sequence_order' => 1,
        ]);

        $this->actingAs($admin)->get(route('admin.modules.units.create', $module))->assertOk();
        $this->get(route('admin.modules.units.edit', [$module, $unit]))->assertOk();

        $this->put(route('admin.modules.units.update', [$module, $unit]), [
            'title' => 'Unit diperbarui',
            'learning_objective' => 'Memahami materi terbaru.',
            'video_url' => 'https://example.test/updated.mp4',
            'duration_minutes' => 1.5,
            'reading_content' => 'Bacaan terbaru.',
            'sequence_order' => 1,
        ])
            ->assertRedirect(route('admin.modules.units.index', $module));

        $this->assertDatabaseHas('units', [
            'id' => $unit->id,
            'title' => 'Unit diperbarui',
            'reading_content' => '<p>Bacaan terbaru.</p>',
            'duration' => 90,
        ]);

        $this->delete(route('admin.modules.units.destroy', [$module, $unit]))
            ->assertRedirect(route('admin.modules.units.index', $module))
            ->assertSessionHas('status', 'Unit berhasil dihapus.');

        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
    }

    // FR-A03: quiz history prevents physical unit deletion.
    public function test_unit_with_quiz_history_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $learner = User::factory()->create(['role' => Role::Learner->value]);
        $module = $this->module($admin);
        $unit = $module->units()->create([
            'title' => 'Unit berriwayat',
            'learning_objective' => 'Memahami materi unit.',
            'video_url' => 'https://example.test/video.mp4',
            'duration' => 60,
            'reading_content' => '',
            'sequence_order' => 1,
        ]);
        DB::table('unit_quiz_attempts')->insert([
            'user_id' => $learner->id,
            'unit_id' => $unit->id,
            'score' => 80,
            'completed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.modules.units.destroy', [$module, $unit]))
            ->assertRedirect(route('admin.modules.units.index', $module))
            ->assertSessionHas('error', 'Unit tidak dapat dihapus karena sudah memiliki riwayat kuis.');

        $this->assertDatabaseHas('units', ['id' => $unit->id]);
    }

    // FR-A03, AC-42: required unit fields are validated on the server.
    public function test_unit_learning_objective_is_required_with_an_indonesian_message(): void
    {
        $admin = $this->admin();
        $module = $this->module($admin);

        $this->actingAs($admin)
            ->post(route('admin.modules.units.store', $module), [
                'title' => 'Unit materi',
                'video_url' => 'https://example.test/video.mp4',
                'duration' => 30,
                'sequence_order' => 1,
            ])
            ->assertSessionHasErrors(['learning_objective' => 'Tujuan belajar wajib diisi.']);
    }

    // FR-A04, AC-14: learner-facing queries can return only published modules.
    public function test_published_scope_excludes_draft_modules(): void
    {
        $admin = $this->admin();
        $draft = $this->module($admin, ['status' => 'draft']);
        $published = $this->module($admin, ['status' => 'published']);

        $this->assertSame(
            [$published->id],
            Module::published()->pluck('id')->all(),
        );
        $this->assertDatabaseHas('modules', ['id' => $draft->id, 'status' => 'draft']);
    }

    // FR-A04, AC-41: reverting to draft hides a module without deleting learner progress.
    public function test_reverting_module_to_draft_preserves_learner_progress(): void
    {
        $admin = $this->admin();
        $learner = User::factory()->create(['role' => Role::Learner->value]);
        $module = $this->module($admin, ['status' => 'published']);
        $unit = $module->units()->create([
            'title' => 'Unit materi',
            'learning_objective' => 'Memahami materi dasar.',
            'video_url' => 'https://example.test/video.mp4',
            'duration' => 45,
            'reading_content' => '',
            'sequence_order' => 1,
        ]);
        DB::table('learner_unit_progress')->insert([
            'user_id' => $learner->id,
            'unit_id' => $unit->id,
            'last_video_pos' => 20,
            'is_completed' => false,
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->put(route('admin.modules.status', $module), ['status' => 'draft'])
            ->assertRedirect(route('admin.modules.index'))
            ->assertSessionHas('status', 'Modul dikembalikan ke draft. Progres Learner tetap tersimpan.');

        $this->assertSame(0, Module::published()->whereKey($module->id)->count());
        $this->assertDatabaseHas('learner_unit_progress', [
            'user_id' => $learner->id,
            'unit_id' => $unit->id,
            'last_video_pos' => 20,
        ]);
    }

    // FR-A02, EC-22: test history blocks deletion and recommends draft status.
    public function test_module_with_test_history_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $learner = User::factory()->create(['role' => Role::Learner->value]);
        $module = $this->module($admin);
        DB::table('test_attempts')->insert([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'type' => 'pre',
            'score' => 50,
            'completed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.modules.destroy', $module))
            ->assertRedirect(route('admin.modules.index'))
            ->assertSessionHas('error', 'Modul tidak dapat dihapus karena sudah memiliki riwayat tes. Kembalikan modul ke draft agar tidak tampil kepada Learner.');

        $this->assertDatabaseHas('modules', ['id' => $module->id]);
        $this->assertDatabaseHas('test_attempts', ['module_id' => $module->id]);
    }

    // FR-A04: publishing requires one unit in Step 2A; full checks are deferred to Step 2B.
    public function test_module_publish_requires_at_least_one_unit(): void
    {
        $admin = $this->admin();
        $module = $this->module($admin);

        $this->actingAs($admin)
            ->put(route('admin.modules.status', $module), ['status' => 'published'])
            ->assertSessionHasErrors([
                'status' => 'Modul perlu memiliki minimal satu unit sebelum dapat diterbitkan.',
            ]);

        $module->units()->create([
            'title' => 'Unit pertama',
            'learning_objective' => 'Memahami materi dasar.',
            'video_url' => 'https://example.test/video.mp4',
            'duration' => 45,
            'reading_content' => '',
            'sequence_order' => 1,
        ]);

        $this->put(route('admin.modules.status', $module), ['status' => 'published'])
            ->assertRedirect(route('admin.modules.index'));

        $this->assertDatabaseHas('modules', ['id' => $module->id, 'status' => 'published']);
    }

    // FR-S05, AB-13: Learner and Superadmin cannot access Admin module routes.
    public function test_only_admin_can_access_module_routes(): void
    {
        foreach ([Role::Learner, Role::Superadmin] as $role) {
            $user = User::factory()->create(['role' => $role->value]);

            $this->actingAs($user)
                ->get(route('admin.modules.index'))
                ->assertForbidden();
        }
    }

    // FR-A02: module cover images use the public disk.
    public function test_admin_can_store_a_module_cover_image(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.modules.store'), [
                'title' => 'Modul sampul',
                'cover_image' => UploadedFile::fake()->createWithContent(
                    'cover.png',
                    base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASsJTYQAAAAASUVORK5CYII='),
                ),
            ])
            ->assertRedirect();

        $module = Module::query()->where('title', 'Modul sampul')->firstOrFail();
        Storage::disk('public')->assertExists($module->cover_image);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => Role::Admin->value,
            'is_temp_password' => false,
        ]);
    }

    private function module(User $creator, array $attributes = []): Module
    {
        return Module::query()->create(array_merge([
            'title' => 'Modul Sinau',
            'description' => '',
            'learning_objectives' => null,
            'status' => 'draft',
            'created_by' => $creator->id,
        ], $attributes));
    }
}
