<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AdminFoundationTest extends TestCase
{
    use RefreshDatabase;

    // AC-01
    public function test_guest_is_redirected_to_login_from_admin_panel(): void
    {
        $this->get('/admin/dashboard')
            ->assertRedirect(route('login'));
    }

    // AC-05
    public function test_invalid_login_uses_a_generic_indonesian_error(): void
    {
        $this->post(route('login.store'), [
            'email' => 'admin@sinau.test',
            'password' => 'wrong-password',
        ])
            ->assertRedirect()
            ->assertSessionHasErrors([
                'email' => 'Email atau kata sandi tidak sesuai.',
            ]);
    }

    // AC-07
    public function test_learner_cannot_log_in_to_the_web_panel(): void
    {
        $learner = User::factory()->create([
            'role' => 'learner',
            'password' => 'LearnerDemo!123',
        ]);

        $this->post(route('login.store'), [
            'email' => $learner->email,
            'password' => 'LearnerDemo!123',
        ])->assertForbidden();

        $this->assertGuest();
        $this->actingAs($learner)
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    // AC-08
    public function test_temporary_admin_password_must_be_changed_before_panel_access(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => 'temporary-pass',
            'is_temp_password' => true,
        ]);

        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'temporary-pass',
        ])->assertRedirect(route('password.temporary.edit'));

        $this->get('/admin/dashboard')
            ->assertRedirect(route('password.temporary.edit'));

        $this->put(route('password.temporary.update'), [
            'password' => 'new-secure-pass',
            'password_confirmation' => 'different-pass',
        ])->assertSessionHasErrors([
            'password' => 'Konfirmasi kata sandi belum sama.',
        ]);

        $this->put(route('password.temporary.update'), [
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors([
            'password' => 'Kata sandi baru minimal 8 karakter.',
        ]);

        $this->put(route('password.temporary.update'), [
            'password' => 'new-secure-pass',
            'password_confirmation' => 'new-secure-pass',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'is_temp_password' => false,
        ]);
        $this->assertTrue(Hash::check('new-secure-pass', $admin->fresh()->password));
        $this->get('/admin/dashboard')->assertOk();
    }

    // AC-09
    public function test_admin_is_forbidden_from_superadmin_routes(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_temp_password' => false,
        ]);

        $this->actingAs($admin)
            ->get('/superadmin')
            ->assertForbidden();
    }

    // AC-10
    public function test_superadmin_is_forbidden_from_admin_routes(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'is_temp_password' => false,
        ]);

        $this->actingAs($superadmin)
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    // FR-A01: logout invalidates the authenticated session.
    public function test_admin_can_log_out(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_temp_password' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    // FR-A01, NFR-05: reset links are sent to Admin accounts and can set a new password.
    public function test_admin_can_reset_password_from_email_link(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_temp_password' => false,
        ]);
        Notification::fake();

        $this->post(route('password.email'), ['email' => $admin->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        $token = null;
        Notification::assertSentTo(
            $admin,
            ResetPassword::class,
            function (ResetPassword $notification) use (&$token): bool {
                $token = $notification->token;

                return true;
            },
        );

        $this->get(route('password.reset', ['token' => $token, 'email' => $admin->email]))
            ->assertOk()
            ->assertSee('Atur kata sandi baru');

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $admin->email,
            'password' => 'updated-password',
            'password_confirmation' => 'updated-password',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('updated-password', $admin->fresh()->password));
    }

    // FR-SA01, FR-A01, FR-A12: the development seeder creates all specified role accounts.
    public function test_development_seeder_creates_demo_accounts_and_temporary_admin_password(): void
    {
        $this->seed();

        $this->assertDatabaseHas('users', [
            'email' => 'superadmin@sinau.test',
            'role' => 'superadmin',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'admin@sinau.test',
            'role' => 'admin',
            'is_temp_password' => true,
        ]);
        $this->assertDatabaseCount('users', 4);
    }
}
