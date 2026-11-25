<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContentGovernanceTest extends TestCase
{
    use RefreshDatabase;

    // PS-01–PS-20: automatic standards prevent incomplete content from entering review.
    public function test_incomplete_module_cannot_be_submitted(): void
    {
        $admin = $this->admin();
        $module = $this->module($admin);

        $this->actingAs($admin)
            ->post(route('admin.modules.submit', $module), ['copyright_confirmation' => '1'])
            ->assertSessionHasErrors(['quality_gate']);

        $this->assertDatabaseHas('modules', ['id' => $module->id, 'status' => 'draft']);
        $this->assertDatabaseMissing('module_reviews', ['module_id' => $module->id, 'action' => 'submitted']);
    }

    // FR-S05, FR-SA07, PS-23–PS-30: role and ownership checks are enforced by server routes.
    public function test_module_content_access_is_role_and_owner_restricted(): void
    {
        $owner = $this->admin();
        $otherAdmin = $this->admin();
        $superadmin = $this->superadmin();
        $module = $this->completeModule($owner);
        $unit = $module->units()->firstOrFail();
        $image = '123e4567-e89b-12d3-a456-426614174000.png';
        Storage::fake('local');
        Storage::disk('local')->put("unit-materials/{$unit->id}/{$image}", 'image');

        $this->actingAs($owner)->get(route('admin.modules.show', $module))->assertOk();
        $this->actingAs($otherAdmin)->get(route('admin.modules.show', $module))->assertNotFound();
        $this->put(route('admin.modules.update', $module), ['title' => 'Judul berbeda'])
            ->assertNotFound();
        $this->actingAs($superadmin)->get(route('admin.modules.show', $module))->assertForbidden();
        $this->get(route('superadmin.modules.show', $module))
            ->assertOk()
            ->assertSee('Pratinjau hanya-baca')
            ->assertDontSee(route('admin.modules.edit', $module));
        $this->get(route('superadmin.modules.units.material-images.show', [$module, $unit, $image]))
            ->assertOk();
        $this->put(route('admin.modules.update', $module), ['title' => 'Judul diubah Superadmin'])
            ->assertForbidden();
        $this->actingAs($owner)
            ->post(route('superadmin.modules.approve', $module), [])
            ->assertForbidden();
        $this->actingAs($superadmin)
            ->get('/admin/results')
            ->assertForbidden();
        $this->put("/admin/modules/{$module->id}/units/1", [])->assertForbidden();
    }

    // PS-01–PS-22, PS-29: accepted copyright ownership and valid standards create a review record.
    public function test_valid_module_submission_is_locked_and_notifies_superadmins(): void
    {
        $admin = $this->admin();
        $superadmin = $this->superadmin();
        $module = $this->completeModule($admin);

        $this->actingAs($admin)
            ->post(route('admin.modules.submit', $module), ['copyright_confirmation' => '1'])
            ->assertRedirect(route('admin.modules.show', $module));

        $this->assertDatabaseHas('modules', ['id' => $module->id, 'status' => 'submitted']);
        $this->assertDatabaseHas('module_reviews', [
            'module_id' => $module->id,
            'action' => 'submitted',
            'status_from' => 'draft',
            'status_to' => 'submitted',
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $superadmin->id,
            'type' => \App\Notifications\ModuleGovernanceNotification::class,
        ]);
        $this->post(route('admin.modules.units.store', $module), [])->assertForbidden();
    }

    // PS-23–PS-29: Superadmin approval is auditable and not exposed through Admin routes.
    public function test_superadmin_can_approve_after_manual_checks_and_takedown(): void
    {
        $admin = $this->admin();
        $superadmin = $this->superadmin();
        $module = $this->completeModule($admin);
        $this->submit($admin, $module);

        $this->actingAs($superadmin)
            ->post(route('superadmin.modules.approve', $module), [
                'policy_checks' => [
                    'accurate' => '1',
                    'safe' => '1',
                    'copyright' => '1',
                    'privacy' => '1',
                ],
            ])
            ->assertRedirect(route('superadmin.modules.show', $module));

        $module->refresh();
        $this->assertSame('published', $module->status);
        $this->assertSame($superadmin->id, $module->approved_by);
        $this->assertDatabaseHas('module_reviews', ['module_id' => $module->id, 'action' => 'approved']);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $admin->id]);
        $this->get('/admin/results')->assertForbidden();

        $this->post(route('superadmin.modules.takedown', $module), ['reason' => 'Konten perlu segera ditinjau.'])
            ->assertRedirect();
        $this->assertDatabaseHas('modules', ['id' => $module->id, 'status' => 'archived']);
        $this->assertSame(0, Module::published()->whereKey($module->id)->count());
        $this->assertDatabaseHas('module_reviews', ['module_id' => $module->id, 'action' => 'takedown']);
    }

    // PS-21, PS-29: future approvals stay approved until the active start date.
    public function test_superadmin_approval_schedules_future_publication(): void
    {
        $admin = $this->admin();
        $superadmin = $this->superadmin();
        $module = $this->completeModule($admin, [
            'active_from' => today()->addDay(),
            'active_until' => today()->addDays(31),
        ]);
        $this->submit($admin, $module);

        $this->actingAs($superadmin)
            ->post(route('superadmin.modules.approve', $module), [
                'policy_checks' => [
                    'accurate' => '1',
                    'safe' => '1',
                    'copyright' => '1',
                    'privacy' => '1',
                ],
            ])
            ->assertRedirect();
        $this->assertSame('approved', $module->fresh()->status);

        $this->travelTo(now()->addDay());
        $this->artisan('modules:process-governance-schedule')->assertExitCode(0);
        $this->assertSame('published', $module->fresh()->status);
    }

    // PS-30: published modules accept only the designated minor metadata edits.
    public function test_published_module_allows_only_minor_metadata_changes(): void
    {
        $admin = $this->admin();
        $superadmin = $this->superadmin();
        $module = $this->completeModule($admin, [
            'status' => 'published',
            'approved_by' => $superadmin->id,
            'approved_at' => now(),
        ]);

        $this->actingAs($admin)
            ->put(route('admin.modules.update', $module), [
                'title' => 'Koreksi Judul Governance',
                'description' => str_repeat('Deskripsi yang diperbarui untuk modul pembelajaran. ', 3),
            ])
            ->assertRedirect(route('admin.modules.show', $module));
        $this->assertSame('published', $module->fresh()->status);
        $this->assertDatabaseHas('module_reviews', [
            'module_id' => $module->id,
            'action' => 'minor_update',
        ]);

        $this->put(route('admin.modules.update', $module), [
            'title' => 'Judul Governance',
            'description' => 'Deskripsi valid.',
            'learning_objectives' => 'Konten yang tidak boleh diubah langsung.',
        ])->assertSessionHasErrors('learning_objectives');
    }

    // PS-28, PS-29: revision requests are auditable and return edit access only to the owner.
    public function test_revision_request_unlocks_owner_and_allows_resubmission(): void
    {
        $admin = $this->admin();
        $superadmin = $this->superadmin();
        $module = $this->completeModule($admin);
        $unit = $module->units()->firstOrFail();
        $this->submit($admin, $module);

        $this->actingAs($admin)
            ->get(route('admin.modules.units.create', $module))
            ->assertForbidden();

        $this->actingAs($superadmin)
            ->post(route('superadmin.modules.revision', $module), ['notes' => 'Tambahkan contoh yang lebih jelas.'])
            ->assertRedirect(route('superadmin.modules.show', $module));

        $this->assertDatabaseHas('module_reviews', [
            'module_id' => $module->id,
            'action' => 'revision_requested',
            'notes' => 'Tambahkan contoh yang lebih jelas.',
        ]);
        $this->assertSame('revision_required', $module->fresh()->status);
        $this->actingAs($admin)
            ->get(route('admin.units.quizzes.create', $unit))
            ->assertOk();
        $this->post(route('admin.modules.submit', $module), ['copyright_confirmation' => '1'])
            ->assertRedirect(route('admin.modules.show', $module));
        $this->assertSame('submitted', $module->fresh()->status);
        $this->assertDatabaseHas('module_reviews', ['module_id' => $module->id, 'action' => 'resubmitted']);
    }

    // PS-30: revisions isolate published content and archive the previous version after approval.
    public function test_significant_revision_copies_content_and_replaces_published_version(): void
    {
        $admin = $this->admin();
        $superadmin = $this->superadmin();
        $source = $this->completeModule($admin, [
            'status' => 'published',
            'approved_by' => $superadmin->id,
            'approved_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.modules.revision', $source))
            ->assertRedirect();

        $revision = Module::query()->where('revision_of_id', $source->id)->firstOrFail();
        $this->assertSame('draft', $revision->status);
        $this->assertSame($source->units()->count(), $revision->units()->count());
        $this->assertSame($source->testQuestions()->count(), $revision->testQuestions()->count());
        $this->assertSame('published', $source->fresh()->status);

        $revision->update([
            'active_from' => today(),
            'active_until' => today()->addDays(30),
        ]);
        $this->submit($admin, $revision);
        $this->actingAs($superadmin)
            ->post(route('superadmin.modules.approve', $revision), [
                'policy_checks' => [
                    'accurate' => '1',
                    'safe' => '1',
                    'copyright' => '1',
                    'privacy' => '1',
                ],
            ])
            ->assertRedirect();

        $this->assertSame('published', $revision->fresh()->status);
        $this->assertSame('archived', $source->fresh()->status);
        $this->assertDatabaseHas('module_reviews', [
            'module_id' => $source->id,
            'action' => 'revision_replaced',
        ]);
    }

    // PS-21, PS-22, PS-29: scheduled publication, expiry, rejection, and reminders are idempotent.
    public function test_governance_schedule_publishes_archives_and_sends_reminders_once(): void
    {
        $admin = $this->admin();
        $superadmin = $this->superadmin();
        $scheduled = $this->completeModule($admin, [
            'status' => 'approved',
            'active_from' => today(),
            'active_until' => today()->addDays(14),
            'approved_by' => $superadmin->id,
            'approved_at' => now(),
        ]);
        $expired = $this->completeModule($admin, [
            'status' => 'published',
            'active_from' => today()->subDays(30),
            'active_until' => today()->subDay(),
            'approved_by' => $superadmin->id,
            'approved_at' => now()->subDays(31),
        ]);
        $nearExpiry = $this->completeModule($admin, [
            'status' => 'published',
            'active_from' => today()->subDays(30),
            'active_until' => today()->addDays(3),
            'approved_by' => $superadmin->id,
            'approved_at' => now()->subDays(31),
        ]);
        $extension = $expired->extensionRequests()->create([
            'requested_by' => $admin->id,
            'previous_active_until' => today()->subDay(),
            'requested_active_until' => today()->addDays(14),
            'reason' => 'Meminta waktu tambahan.',
            'status' => 'pending',
        ]);

        $this->artisan('modules:process-governance-schedule')->assertExitCode(0);
        $this->assertSame('published', $scheduled->fresh()->status);
        $this->assertSame('archived', $expired->fresh()->status);
        $this->assertSame('rejected', $extension->fresh()->status);
        $this->assertDatabaseHas('module_reminders', [
            'module_id' => $scheduled->id,
            'days_before' => 14,
            'active_until' => today()->addDays(14)->toDateString(),
        ]);
        $this->assertDatabaseHas('module_reminders', [
            'module_id' => $nearExpiry->id,
            'days_before' => 3,
            'active_until' => today()->addDays(3)->toDateString(),
        ]);

        $this->artisan('modules:process-governance-schedule')->assertExitCode(0);
        $this->assertDatabaseCount('module_reminders', 2);
    }

    // PS-22, PS-29: extension decisions are separate from the published module status.
    public function test_extension_waits_for_superadmin_and_records_the_decision(): void
    {
        $admin = $this->admin();
        $superadmin = $this->superadmin();
        $module = $this->completeModule($admin, [
            'status' => 'published',
            'active_from' => today()->subDays(1),
            'active_until' => today()->addDays(29),
            'approved_by' => $superadmin->id,
            'approved_at' => now(),
        ]);
        $newEnd = today()->addDays(60)->toDateString();

        $this->actingAs($admin)->post(route('admin.modules.extensions.store', $module), [
            'active_until' => $newEnd,
            'reason' => 'Materi masih dibutuhkan oleh peserta.',
        ])->assertRedirect(route('admin.modules.show', $module));
        $this->assertSame(today()->addDays(29)->toDateString(), $module->fresh()->active_until->toDateString());

        $extension = $module->extensionRequests()->firstOrFail();
        $this->actingAs($superadmin)
            ->post(route('superadmin.extensions.decision', $extension), ['decision' => 'approve'])
            ->assertRedirect(route('superadmin.modules.index'));

        $this->assertSame($newEnd, $module->fresh()->active_until->toDateString());
        $this->assertDatabaseHas('module_extension_requests', ['id' => $extension->id, 'status' => 'approved']);
        $this->assertDatabaseHas('module_reviews', ['module_id' => $module->id, 'action' => 'extension_approved']);

        $rejectedEnd = today()->addDays(90)->toDateString();
        $this->actingAs($admin)->post(route('admin.modules.extensions.store', $module), [
            'active_until' => $rejectedEnd,
            'reason' => 'Permintaan perpanjangan kedua.',
        ])->assertRedirect();
        $rejected = $module->extensionRequests()->where('status', 'pending')->firstOrFail();
        $this->actingAs($superadmin)
            ->post(route('superadmin.extensions.decision', $rejected), [
                'decision' => 'reject',
                'notes' => 'Periode tambahan tidak diperlukan.',
            ])
            ->assertRedirect(route('superadmin.modules.index'));
        $this->assertSame($newEnd, $module->fresh()->active_until->toDateString());
        $this->assertDatabaseHas('module_extension_requests', [
            'id' => $rejected->id,
            'status' => 'rejected',
            'decision_notes' => 'Periode tambahan tidak diperlukan.',
        ]);
    }

    // PS-27: the SLA warning begins after two weekdays and excludes weekends.
    public function test_review_sla_counts_weekdays_and_marks_near_deadline(): void
    {
        $monday = \Illuminate\Support\Carbon::parse('2026-11-23 09:00:00');
        $service = app(\App\Services\ModuleGovernanceService::class);

        $this->assertFalse($service->isNearSla($monday, $monday->copy()->addDay()));
        $this->assertTrue($service->isNearSla($monday, $monday->copy()->addDays(2)));
        $this->assertFalse(
            $service->isPastSla(\Illuminate\Support\Carbon::parse('2026-11-27 09:00:00'), \Illuminate\Support\Carbon::parse('2026-11-30 09:00:00')),
        );
        $this->assertTrue($service->isPastSla($monday, $monday->copy()->addDays(4)));
    }

    private function submit(User $admin, Module $module): void
    {
        $this->actingAs($admin)->post(route('admin.modules.submit', $module), [
            'copyright_confirmation' => '1',
        ])->assertRedirect();
    }

    private function completeModule(User $admin, array $attributes = []): Module
    {
        Storage::fake('public');
        Storage::disk('public')->put('modules/covers/governance.jpg', 'cover');
        $module = Module::query()->create(array_merge([
            'title' => 'Modul Governance Sinau',
            'description' => str_repeat('Deskripsi modul untuk pembelajaran UMKM. ', 4),
            'learning_objectives' => "Memahami materi dasar\nMenerapkan materi pada usaha",
            'category' => 'Supply Chain',
            'target_audience' => 'Pemilik dan staf UMKM',
            'difficulty' => 'Dasar',
            'cover_image' => 'modules/covers/governance.jpg',
            'status' => 'draft',
            'active_from' => today(),
            'active_until' => today()->addDays(30),
            'pre_question_count' => 10,
            'post_question_count' => 10,
            'shuffle_questions' => true,
            'created_by' => $admin->id,
        ], $attributes));

        $reading = '<p>'.str_repeat('materi pembelajaran ', 150).'</p>';
        for ($unitIndex = 1; $unitIndex <= 3; $unitIndex++) {
            $unit = $module->units()->create([
                'title' => "Unit pembelajaran {$unitIndex}",
                'learning_objective' => 'Memahami materi pembelajaran.',
                'video_url' => "https://example.test/video-{$unitIndex}.mp4",
                'duration' => 300,
                'reading_content' => $reading,
                'sequence_order' => $unitIndex,
            ]);

            for ($questionIndex = 1; $questionIndex <= 3; $questionIndex++) {
                $unit->quizzes()->create([
                    'question_text' => "Kuis unit {$unitIndex} soal {$questionIndex}",
                    'options' => ['A' => 'Pilihan satu', 'B' => 'Pilihan dua', 'C' => 'Pilihan tiga'],
                    'correct_key' => 'A',
                    'explanation' => 'Pembahasan kuis.',
                ]);
            }
        }

        foreach (['pre', 'post'] as $type) {
            for ($questionIndex = 1; $questionIndex <= 10; $questionIndex++) {
                $module->testQuestions()->create([
                    'type' => $type,
                    'question_text' => "Soal {$type} nomor {$questionIndex}",
                    'options' => ['A' => 'Pilihan satu', 'B' => 'Pilihan dua', 'C' => 'Pilihan tiga'],
                    'correct_key' => 'A',
                    'explanation' => 'Pembahasan tes.',
                ]);
            }
        }

        return $module;
    }

    private function module(User $admin): Module
    {
        return Module::query()->create([
            'title' => 'Modul Draft',
            'description' => '',
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => Role::Admin->value, 'is_temp_password' => false]);
    }

    private function superadmin(): User
    {
        return User::factory()->create(['role' => Role::Superadmin->value, 'is_temp_password' => false]);
    }
}
