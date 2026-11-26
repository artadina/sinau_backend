<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Module;
use App\Models\TestAttempt;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DemoHistorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LearnerMaterialApiTest extends TestCase
{
    use RefreshDatabase;

    // FR-L08, AB-08, AC-14: list only active published modules with active-unit aggregates.
    public function test_module_list_excludes_unavailable_modules_and_uses_only_active_units(): void
    {
        $learner = $this->learner();
        $published = $this->module();
        $first = $this->unit($published, 1, duration: 300);
        $this->unit($published, 2, duration: null);
        $this->unit($published, 3, active: false, duration: 500);
        $this->module(status: 'draft');
        $this->module(activeFrom: today()->subDays(10), activeUntil: today()->subDay());

        DB::table('learner_unit_progress')->insert([
            'user_id' => $learner->id,
            'unit_id' => $first->id,
            'last_video_pos' => 60,
            'is_completed' => true,
            'updated_at' => now(),
        ]);

        $this->withToken($this->token($learner))->getJson('/api/modules')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $published->id)
            ->assertJsonPath('data.0.active_unit_count', 2)
            ->assertJsonPath('data.0.estimated_duration', 300)
            ->assertJsonPath('data.0.progress_percentage', 50);
    }

    // AB-11, FR-S02: all material endpoints require consent and a Learner token.
    public function test_material_api_requires_consent_and_rejects_non_learner_tokens(): void
    {
        $learnerWithoutConsent = $this->learner(consentGiven: false);
        $this->withToken($this->token($learnerWithoutConsent))->getJson('/api/modules')
            ->assertForbidden()
            ->assertJsonPath('code', 'consent_required')
            ->assertJsonPath('message', 'Anda perlu menyetujui penggunaan data sebelum mulai belajar.');

        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $this->withToken($this->token($admin))->getJson('/api/modules')->assertForbidden();
    }

    // FR-L10, FR-S03, AC-15, FR-S07: save and retrieve progress without exposing a YouTube URL.
    public function test_video_id_is_safe_and_saved_position_is_returned_for_the_owner(): void
    {
        $learner = $this->learner();
        $module = $this->module();
        $unit = $this->unit($module, 1, videoUrl: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ');
        $image = '123e4567-e89b-12d3-a456-426614174000.png';
        $unit->update([
            'reading_content' => "<p><img src=\"/admin/units/{$unit->id}/material/images/{$image}\" alt=\"Gambar\"></p>",
        ]);
        $this->completePreTest($learner, $module);
        $token = $this->token($learner);

        $this->withToken($token)->putJson("/api/units/{$unit->id}/progress", [
            'last_video_pos' => 75,
        ])->assertOk()
            ->assertJsonPath('data.last_video_pos', 75)
            ->assertJsonPath('data.is_completed', false);

        $this->withToken($token)->getJson("/api/units/{$unit->id}")
            ->assertOk()
            ->assertJsonPath('data.video.type', 'youtube')
            ->assertJsonPath('data.video.video_id', 'dQw4w9WgXcQ')
            ->assertJsonMissingPath('data.video.url')
            ->assertJsonPath('data.last_video_pos', 75)
            ->assertJsonPath('data.is_completed', false)
            ->assertJsonPath(
                'data.reading_content',
                "<p><img src=\"/api/units/{$unit->id}/material/images/{$image}\" alt=\"Gambar\" /></p>",
            )
            ->assertDontSee('youtube.com/watch');
    }

    // FR-L09, AB-03, AC-14: hide drafts and report per-user unit and test states.
    public function test_module_detail_hides_drafts_and_returns_owner_specific_statuses(): void
    {
        $learner = $this->learner();
        $draft = $this->module(status: 'draft');
        $this->unit($draft, 1);
        $this->withToken($this->token($learner))->getJson("/api/modules/{$draft->id}")
            ->assertNotFound()
            ->assertJsonPath('message', 'Data yang diminta tidak ditemukan atau belum dapat diakses.');

        $module = $this->module();
        $completed = $this->unit($module, 1);
        $inProgress = $this->unit($module, 2);
        $notStarted = $this->unit($module, 3);
        $this->completePreTest($learner, $module);
        DB::table('learner_unit_progress')->insert([
            [
                'user_id' => $learner->id,
                'unit_id' => $completed->id,
                'last_video_pos' => 30,
                'is_completed' => true,
                'updated_at' => now(),
            ],
            [
                'user_id' => $learner->id,
                'unit_id' => $inProgress->id,
                'last_video_pos' => 30,
                'is_completed' => false,
                'updated_at' => now(),
            ],
        ]);
        $this->completePreTest($learner, $module, 'post');

        $this->withToken($this->token($learner))->getJson("/api/modules/{$module->id}")
            ->assertOk()
            ->assertJsonPath('data.progress_percentage', 33)
            ->assertJsonPath('data.pre_test.status', 'selesai')
            ->assertJsonPath('data.post_test.status', 'terkunci')
            ->assertJsonPath('data.units.0.status', 'selesai')
            ->assertJsonPath('data.units.1.status', 'sedang')
            ->assertJsonPath('data.units.2.status', 'belum');

        DB::table('learner_unit_progress')
            ->where('user_id', $learner->id)
            ->where('unit_id', $inProgress->id)
            ->update(['is_completed' => true, 'updated_at' => now()]);
        DB::table('learner_unit_progress')->insert([
            'user_id' => $learner->id,
            'unit_id' => $notStarted->id,
            'last_video_pos' => 0,
            'is_completed' => true,
            'updated_at' => now(),
        ]);
        $this->withToken($this->token($learner))->getJson("/api/modules/{$module->id}")
            ->assertOk()
            ->assertJsonPath('data.progress_percentage', 100)
            ->assertJsonPath('data.post_test.status', 'selesai');

        $this->assertNotSame($notStarted->id, $inProgress->id);
    }

    // FR-L10, AC-16: file videos are returned as file URLs, unlike YouTube IDs.
    public function test_direct_video_is_returned_as_a_file_url(): void
    {
        $learner = $this->learner();
        $module = $this->module();
        $unit = $this->unit($module, 1, videoUrl: 'https://cdn.example.test/video.mp4');
        $this->completePreTest($learner, $module);

        $this->withToken($this->token($learner))->getJson("/api/units/{$unit->id}")
            ->assertOk()
            ->assertJsonPath('data.video.type', 'file')
            ->assertJsonPath('data.video.url', 'https://cdn.example.test/video.mp4');
    }

    // EC-03, AC-19: draft, inactive, and pre-test-locked units are rejected with clear codes.
    public function test_unit_access_rejects_draft_inactive_and_pretest_locked_units(): void
    {
        $learner = $this->learner();
        $draft = $this->module(status: 'draft');
        $draftUnit = $this->unit($draft, 1);
        $published = $this->module();
        $inactive = $this->unit($published, 1, active: false);
        $locked = $this->unit($published, 2);

        $this->withToken($this->token($learner))->getJson("/api/units/{$draftUnit->id}")
            ->assertForbidden()
            ->assertJsonPath('code', 'module_unavailable');
        $this->withToken($this->token($learner))->getJson("/api/units/{$locked->id}")
            ->assertForbidden()
            ->assertJsonPath('code', 'pretest_required')
            ->assertJsonPath('message', 'Selesaikan pre-test modul terlebih dahulu untuk membuka materi.');

        $this->completePreTest($learner, $published);
        $this->withToken($this->token($learner))->getJson("/api/units/{$inactive->id}")
            ->assertForbidden()
            ->assertJsonPath('code', 'unit_inactive');
    }

    // FR-L13, FR-L14, AC-17, AC-18: completion is idempotent and home resumes the latest unit.
    public function test_completion_updates_progress_home_and_allows_reopening_completed_unit(): void
    {
        $learner = $this->learner();
        $module = $this->module();
        $first = $this->unit($module, 1, duration: 300);
        $second = $this->unit($module, 2, duration: 300);
        $this->completePreTest($learner, $module);
        $token = $this->token($learner);

        $this->withToken($token)->postJson("/api/units/{$first->id}/complete")
            ->assertOk()
            ->assertJsonPath('data.is_completed', true)
            ->assertJsonPath('data.module_progress_percentage', 50)
            ->assertJsonPath('data.next_unit.id', $second->id);

        $this->withToken($token)->getJson("/api/units/{$first->id}")
            ->assertOk()
            ->assertJsonPath('data.is_completed', true);

        $this->withToken($token)->putJson("/api/units/{$first->id}/progress", [
            'last_video_pos' => 75,
        ])->assertOk()
            ->assertJsonPath('data.last_video_pos', 75)
            ->assertJsonPath('data.is_completed', true);
        $this->withToken($token)->getJson('/api/home')
            ->assertOk()
            ->assertJsonPath('data.continue_learning.unit_id', $first->id)
            ->assertJsonPath('data.continue_learning.remaining_duration', 225);

        $this->withToken($token)->postJson("/api/units/{$second->id}/complete")
            ->assertOk()
            ->assertJsonPath('data.module_progress_percentage', 100)
            ->assertJsonPath('data.next_unit', null);
        $this->withToken($token)->postJson("/api/units/{$second->id}/complete")
            ->assertOk()
            ->assertJsonPath('data.is_completed', true);

        $this->assertDatabaseCount('learner_unit_progress', 2);
        $this->withToken($token)->getJson('/api/home')
            ->assertOk()
            ->assertJsonPath('data.continue_learning.unit_id', $second->id);
    }

    // AB-09, FR-S03: progress and continuation never leak from another Learner.
    public function test_learner_cannot_read_another_learners_progress(): void
    {
        $learnerA = $this->learner();
        $learnerB = $this->learner();
        $module = $this->module();
        $unit = $this->unit($module, 1);
        $this->completePreTest($learnerA, $module);
        $this->completePreTest($learnerB, $module);
        DB::table('learner_unit_progress')->insert([
            'user_id' => $learnerB->id,
            'unit_id' => $unit->id,
            'last_video_pos' => 120,
            'is_completed' => true,
            'updated_at' => now(),
        ]);

        $this->withToken($this->token($learnerA))->getJson("/api/units/{$unit->id}")
            ->assertOk()
            ->assertJsonPath('data.last_video_pos', null)
            ->assertJsonPath('data.is_completed', false);
        $this->withToken($this->token($learnerA))->getJson("/api/modules/{$module->id}")
            ->assertOk()
            ->assertJsonPath('data.progress_percentage', 0)
            ->assertJsonPath('data.units.0.status', 'belum');
        $this->withToken($this->token($learnerA))->getJson('/api/home')
            ->assertOk()
            ->assertJsonPath('data.continue_learning', null);
    }

    // FR-L14, AC-17: home ignores newer progress for drafts and inactive units.
    public function test_home_ignores_draft_modules_and_inactive_units(): void
    {
        $learner = $this->learner();
        $published = $this->module();
        $visibleUnit = $this->unit($published, 1, duration: null);
        $inactiveUnit = $this->unit($published, 2, active: false);
        $draft = $this->module(status: 'draft');
        $draftUnit = $this->unit($draft, 1);
        DB::table('learner_unit_progress')->insert([
            [
                'user_id' => $learner->id,
                'unit_id' => $visibleUnit->id,
                'last_video_pos' => 15,
                'is_completed' => false,
                'updated_at' => now(),
            ],
            [
                'user_id' => $learner->id,
                'unit_id' => $inactiveUnit->id,
                'last_video_pos' => 30,
                'is_completed' => false,
                'updated_at' => now()->addSecond(),
            ],
            [
                'user_id' => $learner->id,
                'unit_id' => $draftUnit->id,
                'last_video_pos' => 45,
                'is_completed' => false,
                'updated_at' => now()->addSeconds(2),
            ],
        ]);

        $this->withToken($this->token($learner))->getJson('/api/home')
            ->assertOk()
            ->assertJsonPath('data.continue_learning.unit_id', $visibleUnit->id)
            ->assertJsonPath('data.continue_learning.remaining_duration', null);
    }

    // AB-08, AB-11: private material images require consent and are limited to published modules.
    public function test_material_image_requires_consent(): void
    {
        Storage::fake('local');
        $withoutConsent = $this->learner(consentGiven: false);
        $draft = $this->module(status: 'draft');
        $draftUnit = $this->unit($draft, 1);
        $image = '123e4567-e89b-12d3-a456-426614174000.png';
        Storage::disk('local')->put("unit-materials/{$draftUnit->id}/{$image}", 'image');
        $draftUnit->update([
            'reading_content' => "<p>Materi</p><img src=\"/admin/units/{$draftUnit->id}/material/images/{$image}\" alt=\"Gambar\">",
        ]);
        $url = "/api/units/{$draftUnit->id}/material/images/{$image}";

        $this->withToken($this->token($withoutConsent))->get($url)
            ->assertForbidden()
            ->assertJsonPath('code', 'consent_required');
    }

    // AB-08: Learners cannot fetch private images belonging to draft modules.
    public function test_material_image_cannot_be_fetched_from_draft(): void
    {
        Storage::fake('local');
        $learner = $this->learner();
        $draft = $this->module(status: 'draft');
        $draftUnit = $this->unit($draft, 1);
        $image = '123e4567-e89b-12d3-a456-426614174000.png';
        Storage::disk('local')->put("unit-materials/{$draftUnit->id}/{$image}", 'image');
        $draftUnit->update([
            'reading_content' => "<p>Materi</p><img src=\"/admin/units/{$draftUnit->id}/material/images/{$image}\" alt=\"Gambar\">",
        ]);
        $url = "/api/units/{$draftUnit->id}/material/images/{$image}";

        $this->withToken($this->token($learner))->get($url)->assertNotFound();
    }

    // AB-08: published-module images stay protected and can be served to a consented Learner.
    public function test_material_image_is_available_for_published_module(): void
    {
        Storage::fake('local');
        $learner = $this->learner();
        $published = $this->module();
        $publishedUnit = $this->unit($published, 1);
        $image = '123e4567-e89b-12d3-a456-426614174000.png';
        Storage::disk('local')->put("unit-materials/{$publishedUnit->id}/{$image}", 'image');
        $publishedUnit->update([
            'reading_content' => "<p>Materi</p><img src=\"/admin/units/{$publishedUnit->id}/material/images/{$image}\" alt=\"Gambar\">",
        ]);

        $this->withToken($this->token($learner))
            ->get("/api/units/{$publishedUnit->id}/material/images/{$image}")
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    // AC-17, AC-19: demo data covers partial progress after pre-test and a Learner without pre-test.
    public function test_demo_seeder_provides_completed_and_missing_pretest_scenarios(): void
    {
        User::factory()->create(['role' => Role::Admin->value]);
        $this->seed(DemoHistorySeeder::class);

        $completedLearner = User::query()->where('email', 'riwayat1@sinau.test')->firstOrFail();
        $module = Module::query()->where('title', 'Dasar Pengelolaan Usaha')->firstOrFail();
        $this->assertTrue(TestAttempt::query()
            ->where('user_id', $completedLearner->id)
            ->where('module_id', $module->id)
            ->where('type', 'pre')
            ->whereNotNull('completed_at')
            ->exists());
        $this->assertSame(3, DB::table('learner_unit_progress')
            ->join('units', 'units.id', '=', 'learner_unit_progress.unit_id')
            ->where('learner_unit_progress.user_id', $completedLearner->id)
            ->where('units.module_id', $module->id)
            ->where('learner_unit_progress.is_completed', true)
            ->count());

        $learnerWithoutPreTest = User::query()->where('email', 'riwayat4@sinau.test')->firstOrFail();
        $this->assertFalse(TestAttempt::query()
            ->where('user_id', $learnerWithoutPreTest->id)
            ->where('type', 'pre')
            ->exists());
    }

    private function learner(bool $consentGiven = true): User
    {
        return User::factory()->create([
            'role' => Role::Learner->value,
            'consent_given' => $consentGiven,
            'consent_at' => $consentGiven ? now() : null,
        ]);
    }

    private function token(User $user): string
    {
        return $user->createToken('learner-test')->plainTextToken;
    }

    private function module(
        string $status = 'published',
        ?\Illuminate\Support\Carbon $activeFrom = null,
        ?\Illuminate\Support\Carbon $activeUntil = null,
    ): Module {
        $admin = User::factory()->create(['role' => Role::Admin->value]);

        return Module::query()->create([
            'title' => 'Modul '.uniqid(),
            'description' => 'Deskripsi modul.',
            'learning_objectives' => 'Memahami materi.',
            'status' => $status,
            'created_by' => $admin->id,
            'active_from' => $activeFrom ?? today()->subDay(),
            'active_until' => $activeUntil ?? today()->addDays(30),
        ]);
    }

    private function unit(
        Module $module,
        int $sequence,
        bool $active = true,
        ?int $duration = 300,
        string $videoUrl = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    ): Unit {
        return Unit::query()->create([
            'module_id' => $module->id,
            'title' => "Unit {$sequence}",
            'learning_objective' => 'Memahami tujuan unit.',
            'video_url' => $videoUrl,
            'reading_content' => '<p>Materi unit.</p>',
            'sequence_order' => $sequence,
            'duration' => $duration,
            'is_active' => $active,
        ]);
    }

    private function completePreTest(User $learner, Module $module, string $type = 'pre'): void
    {
        TestAttempt::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'type' => $type,
            'score' => 50,
            'completed_at' => now(),
        ]);
    }
}
