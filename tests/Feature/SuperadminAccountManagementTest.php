<?php

namespace Tests\Feature;

use App\Models\ForumPost;
use App\Models\Module;
use App\Models\TestQuestion;
use App\Models\TestAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SuperadminAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        return User::factory()->create([
            'role' => 'superadmin',
            'is_temp_password' => false,
        ]);
    }

    // FR-SA01: the shared panel login sends Superadmin to account management.
    public function test_superadmin_login_redirects_to_admin_account_management(): void
    {
        $superadmin = User::factory()->create([
            'email' => 'sa@example.test',
            'password' => 'a-valid-password',
            'role' => 'superadmin',
            'is_temp_password' => false,
        ]);

        $this->post(route('login.store'), [
            'email' => $superadmin->email,
            'password' => 'a-valid-password',
        ])->assertRedirect(route('superadmin.admins.index'));

        $this->get(route('superadmin.admins.index'))
            ->assertOk()
            ->assertSee('Kelola Akun Learner')
            ->assertDontSee('Menu Admin')
            ->assertDontSee('Kelola Modul');
    }

    // FR-SA02, AC-11, AB-16: system-generated password is shown once and client role input is ignored.
    public function test_admin_is_created_with_one_time_temporary_password_and_fixed_role(): void
    {
        Log::spy();
        $this->actingAs($this->superadmin())
            ->post(route('superadmin.admins.store'), [
                'name' => 'Admin Baru',
                'email' => 'admin.baru@example.test',
                'role' => 'superadmin',
                'password' => 'chosen-by-client',
            ])
            ->assertRedirect(route('superadmin.admins.index'));

        $admin = User::query()->where('email', 'admin.baru@example.test')->firstOrFail();
        $this->assertSame('admin', $admin->role);
        $this->assertTrue($admin->is_temp_password);
        $this->assertNotSame('chosen-by-client', $admin->password);
        $this->assertTrue(Hash::check('chosen-by-client', $admin->password) === false);

        $firstPage = $this->get(route('superadmin.admins.index'))->assertOk();
        $this->assertMatchesRegularExpression(
            '/<p class="mt-3 select-all rounded-lg border border-amber-200 bg-white px-4 py-3 font-mono text-lg">([^<]+)<\/p>/',
            $firstPage->getContent(),
        );
        preg_match(
            '/<p class="mt-3 select-all rounded-lg border border-amber-200 bg-white px-4 py-3 font-mono text-lg">([^<]+)<\/p>/',
            $firstPage->getContent(),
            $matches,
        );
        $temporaryPassword = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
        $this->assertGreaterThanOrEqual(24, strlen($temporaryPassword));
        $this->assertTrue(Hash::check($temporaryPassword, $admin->password));

        $this->get(route('superadmin.admins.index'))
            ->assertOk()
            ->assertDontSeeText($temporaryPassword);
        Log::shouldNotHaveReceived('info');
        Log::shouldNotHaveReceived('warning');
        Log::shouldNotHaveReceived('error');
    }

    // EC-02, AB-16: duplicate email is reported at the email field.
    public function test_duplicate_account_email_is_rejected_with_an_indonesian_field_error(): void
    {
        $this->actingAs($this->superadmin());
        User::factory()->create(['email' => 'existing@example.test']);

        $this->post(route('superadmin.admins.store'), [
            'name' => 'Admin Baru',
            'email' => 'existing@example.test',
        ])->assertSessionHasErrors([
            'email' => 'Email sudah digunakan. Gunakan email lain.',
        ]);
    }

    // PRD 7.2, AB-16: validate required name length and email format on the server.
    public function test_account_fields_enforce_name_length_and_email_format(): void
    {
        $this->actingAs($this->superadmin())
            ->post(route('superadmin.admins.store'), [
                'name' => str_repeat('N', 101),
                'email' => 'not-an-email',
            ])
            ->assertSessionHasErrors([
                'name' => 'Nama maksimal 100 karakter.',
                'email' => 'Format email belum valid.',
            ]);
    }

    // FR-SA03, FR-SA05: update permitted identity fields without modifying account role/password.
    public function test_admin_and_learner_identity_fields_can_be_updated(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'password' => 'admin-password']);
        $learner = User::factory()->create(['role' => 'learner', 'password' => 'learner-password']);
        $adminPassword = $admin->password;
        $learnerPassword = $learner->password;

        $this->actingAs($this->superadmin())
            ->put(route('superadmin.admins.update', $admin), [
                'name' => 'Nama Admin Diperbarui',
                'email' => 'admin.updated@example.test',
            ])->assertRedirect(route('superadmin.admins.show', $admin));

        $this->put(route('superadmin.learners.update', $learner), [
            'name' => 'Nama Learner Diperbarui',
            'email' => 'learner.updated@example.test',
            'business_name' => 'Usaha Baru',
        ])->assertRedirect(route('superadmin.learners.show', $learner));

        $this->assertSame('admin', $admin->fresh()->role);
        $this->assertSame($adminPassword, $admin->fresh()->password);
        $this->assertSame('learner', $learner->fresh()->role);
        $this->assertSame('Usaha Baru', $learner->fresh()->business_name);
        $this->assertSame($learnerPassword, $learner->fresh()->password);
    }

    // AC-12, EC-18: role changes preserve the existing password and temporary-password flag.
    public function test_role_changes_use_dedicated_routes_and_preserve_password_state(): void
    {
        $superadmin = $this->superadmin();
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => 'AdminAwal!123',
            'is_temp_password' => true,
        ]);
        $adminPasswordHash = $admin->password;

        $this->actingAs($superadmin)
            ->put(route('superadmin.admins.role', $admin), ['role' => 'learner'])
            ->assertRedirect(route('superadmin.admins.index'));

        $admin->refresh();
        $this->assertSame('learner', $admin->role);
        $this->assertSame($adminPasswordHash, $admin->password);
        $this->assertTrue($admin->is_temp_password);

        $learner = User::factory()->create([
            'role' => 'learner',
            'password' => 'LearnerAwal!123',
        ]);
        $learnerPasswordHash = $learner->password;

        $this->put(route('superadmin.learners.role', $learner), ['role' => 'admin'])
            ->assertRedirect(route('superadmin.learners.index'));

        $this->assertSame('admin', $learner->fresh()->role);
        $this->assertSame($learnerPasswordHash, $learner->fresh()->password);
        $this->actingAs($admin);
        $this->get('/admin/dashboard')->assertForbidden();
        $this->get(route('superadmin.admins.index'))->assertForbidden();

        $this->actingAs($learner->fresh());
        $this->get('/admin/dashboard')->assertOk();
        $this->get(route('superadmin.admins.index'))->assertForbidden();
    }

    // FR-SA03, FR-SA05: route binding only resolves active users of the expected role.
    public function test_account_routes_return_not_found_for_other_roles_and_self_accounts(): void
    {
        $superadmin = $this->superadmin();
        $admin = User::factory()->create(['role' => 'admin']);
        $learner = User::factory()->create(['role' => 'learner']);

        $this->actingAs($superadmin)
            ->get(route('superadmin.admins.show', $learner))
            ->assertNotFound();
        $this->get(route('superadmin.learners.show', $admin))
            ->assertNotFound();
        $this->get(route('superadmin.admins.show', $superadmin))
            ->assertNotFound();
        $this->put(route('superadmin.admins.role', $superadmin), ['role' => 'admin'])
            ->assertNotFound();
        $this->delete(route('superadmin.learners.destroy', $superadmin))
            ->assertNotFound();
        $this->assertSame('superadmin', $superadmin->fresh()->role);
    }

    // FR-SA03, FR-SA05: all account forms and detail pages are available to Superadmin.
    public function test_admin_and_learner_account_views_render(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $learner = User::factory()->create(['role' => 'learner']);

        $this->actingAs($this->superadmin());
        $this->get(route('superadmin.admins.create'))->assertOk();
        $this->get(route('superadmin.admins.show', $admin))->assertOk();
        $this->get(route('superadmin.admins.edit', $admin))->assertOk();
        $this->get(route('superadmin.learners.show', $learner))->assertOk();
        $this->get(route('superadmin.learners.edit', $learner))->assertOk();
    }

    // FR-SA05: search Learners by name or email and paginate account-only results.
    public function test_learner_list_search_matches_name_and_email(): void
    {
        $this->actingAs($this->superadmin());
        User::factory()->create([
            'name' => 'Target Learner',
            'email' => 'target@example.test',
            'role' => 'learner',
        ]);
        User::factory()->create([
            'name' => 'Learner Lain',
            'email' => 'other@example.test',
            'role' => 'learner',
        ]);

        $this->get(route('superadmin.learners.index', ['search' => 'Target']))
            ->assertOk()
            ->assertSee('target@example.test')
            ->assertDontSee('other@example.test');
        $this->get(route('superadmin.learners.index', ['search' => 'other@example']))
            ->assertOk()
            ->assertSee('Learner Lain')
            ->assertDontSee('Target Learner');
    }

    // FR-S01: crafted role input cannot create or promote an account to Superadmin.
    public function test_role_change_rejects_superadmin_even_when_request_is_manipulated(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);

        $this->actingAs($this->superadmin())
            ->put(route('superadmin.learners.role', $learner), ['role' => 'superadmin'])
            ->assertSessionHasErrors([
                'role' => 'Role hanya dapat diubah menjadi Admin atau Learner.',
            ]);

        $this->assertSame('learner', $learner->fresh()->role);
    }

    // AB-12, EC-17, AC-13: deleting an Admin soft-deletes the account but preserves shared content.
    public function test_soft_deleting_admin_preserves_created_module_and_questions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $module = Module::create([
            'title' => 'Modul Tetap Ada',
            'description' => 'Konten bersama',
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);
        $question = TestQuestion::create([
            'module_id' => $module->id,
            'type' => 'pre',
            'question_text' => 'Pertanyaan tetap ada?',
            'options' => ['A' => 'Ya', 'B' => 'Tidak', 'C' => 'Mungkin'],
            'correct_key' => 'A',
            'explanation' => 'Konten bank soal tetap ada.',
        ]);

        $this->actingAs($this->superadmin())
            ->delete(route('superadmin.admins.destroy', $admin))
            ->assertRedirect(route('superadmin.admins.index'));

        $this->assertSoftDeleted('users', ['id' => $admin->id]);
        $this->assertDatabaseHas('modules', ['id' => $module->id, 'created_by' => $admin->id]);
        $this->assertDatabaseHas('test_questions', ['id' => $question->id]);
        $this->assertSame(
            $admin->name.' (akun dihapus)',
            $module->fresh()->creator->display_name,
        );
    }

    // Keputusan 6, FR-SA05, AB-09: anonymize learner identity and forum text atomically; never expose scores.
    public function test_learner_deletion_anonymizes_identity_and_forum_without_exposing_scores(): void
    {
        $superadmin = $this->superadmin();
        $admin = User::factory()->create(['role' => 'admin']);
        $learner = User::factory()->create([
            'name' => 'Nama Rahasia Learner',
            'email' => 'rahasia@example.test',
            'business_name' => 'Usaha Rahasia',
            'role' => 'learner',
        ]);
        $module = Module::create([
            'title' => 'Modul Skor',
            'description' => 'Modul tes',
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);
        $post = ForumPost::create([
            'module_id' => $module->id,
            'user_id' => $learner->id,
            'content' => 'Isi forum dengan Nama Rahasia Learner dan rahasia@example.test',
        ]);
        TestAttempt::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'type' => 'pre',
            'score' => 98.75,
            'completed_at' => now(),
        ]);

        $this->actingAs($superadmin)
            ->get(route('superadmin.learners.index'))
            ->assertOk()
            ->assertDontSeeText('98.75');
        $this->get(route('superadmin.learners.show', $learner))
            ->assertOk()
            ->assertDontSeeText('98.75');

        $this->actingAs($superadmin)
            ->delete(route('superadmin.learners.destroy', $learner))
            ->assertRedirect(route('superadmin.learners.index'));

        $deletedLearner = User::withTrashed()->findOrFail($learner->id);
        $this->assertNotNull($deletedLearner->deleted_at);
        $this->assertSame('Pengguna dihapus', $deletedLearner->name);
        $this->assertStringNotContainsString('rahasia@example.test', $deletedLearner->email);
        $this->assertStringNotContainsString('Usaha Rahasia', (string) $deletedLearner->business_name);
        $this->assertDatabaseHas('forum_posts', [
            'id' => $post->id,
            'content' => 'Konten dihapus karena akun penulis telah dihapus.',
        ]);
        $this->assertDatabaseHas('test_attempts', [
            'user_id' => $learner->id,
            'score' => 98.75,
        ]);

        $this->get(route('superadmin.learners.index'))
            ->assertOk()
            ->assertDontSeeText('98.75')
            ->assertDontSeeText('Nama Rahasia Learner')
            ->assertDontSeeText('rahasia@example.test');
        $this->get(route('superadmin.learners.show', $learner))
            ->assertNotFound();
        $this->get('/superadmin/results')->assertNotFound();
    }
}
