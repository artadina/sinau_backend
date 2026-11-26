<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\ForumPost;
use App\Models\Module;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LearnerAccountApiTest extends TestCase
{
    use RefreshDatabase;

    // FR-L01, FR-S01, AC-02: registration ignores a client-supplied role and requires consent afterward.
    public function test_registration_always_creates_learner_with_token_and_consent_required(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Rani',
            'email' => 'rani@example.test',
            'password' => 'Rahasia123',
            'password_confirmation' => 'Rahasia123',
            'business_name' => 'Usaha Rani',
            'role' => 'admin',
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Pendaftaran berhasil.')
            ->assertJsonPath('data.user.role', Role::Learner->value)
            ->assertJsonPath('data.user.email', 'rani@example.test')
            ->assertJsonPath('data.user.consent_given', false)
            ->assertJsonPath('data.consent_required', true)
            ->assertJsonStructure(['data' => ['token']])
            ->assertJsonMissingPath('data.user.password')
            ->assertJsonMissingPath('data.user.remember_token');

        $this->assertDatabaseHas('users', [
            'email' => 'rani@example.test',
            'role' => Role::Learner->value,
            'consent_given' => false,
        ]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => User::query()->where('email', 'rani@example.test')->value('id'),
        ]);
    }

    // FR-L01, AB-16, AC-03: duplicate email errors are Indonesian and attached to email.
    public function test_registration_rejects_duplicate_email_on_email_field(): void
    {
        User::factory()->create(['email' => 'same@example.test']);

        $this->postJson('/api/register', [
            'name' => 'Nama',
            'email' => 'same@example.test',
            'password' => 'Rahasia123',
            'password_confirmation' => 'Rahasia123',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Data yang dikirim belum lengkap atau belum valid.')
            ->assertJsonPath('errors.email.0', 'Email sudah digunakan oleh akun lain.');
    }

    // FR-L02, AB-11, AC-04: acceptance is recorded by the server, not trusted from a timestamp.
    public function test_consent_records_boolean_and_server_timestamp(): void
    {
        $learner = User::factory()->create([
            'role' => Role::Learner->value,
            'consent_given' => false,
            'consent_at' => null,
        ]);
        $token = $learner->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/consent', ['consent_given' => true])
            ->assertOk()
            ->assertJsonPath('data.consent_given', true)
            ->assertJsonPath('data.consent_required', false)
            ->assertJsonPath('message', 'Persetujuan penggunaan data berhasil disimpan.');

        $learner->refresh();
        $this->assertTrue($learner->consent_given);
        $this->assertNotNull($learner->consent_at);
    }

    // FR-L03, EC-01, AC-05: wrong or unknown credentials receive the same generic response.
    public function test_invalid_login_uses_same_generic_message(): void
    {
        $learner = User::factory()->create([
            'email' => 'learner@example.test',
            'password' => 'Rahasia123',
            'role' => Role::Learner->value,
        ]);

        $unknown = $this->postJson('/api/login', [
            'email' => 'unknown@example.test',
            'password' => 'wrong-password',
        ]);
        $wrongPassword = $this->postJson('/api/login', [
            'email' => $learner->email,
            'password' => 'wrong-password',
        ]);

        $unknown->assertUnauthorized()->assertExactJson(['message' => 'Email atau password tidak sesuai.']);
        $wrongPassword->assertUnauthorized()->assertExactJson(['message' => 'Email atau password tidak sesuai.']);
    }

    // FR-L04, AC-06: broker responses do not enumerate accounts; its one-time token resets Learner password.
    public function test_forgot_password_is_generic_and_reset_token_allows_new_password_login(): void
    {
        Notification::fake();
        $learner = User::factory()->create([
            'email' => 'reset@example.test',
            'password' => 'PasswordLama123',
            'role' => Role::Learner->value,
        ]);

        $known = $this->postJson('/api/forgot-password', ['email' => $learner->email]);
        $unknown = $this->postJson('/api/forgot-password', ['email' => 'missing@example.test']);

        $known->assertOk()->assertExactJson([
            'message' => 'Jika email terdaftar pada Sinau, tautan reset password telah dikirim.',
        ]);
        $unknown->assertOk()->assertExactJson([
            'message' => 'Jika email terdaftar pada Sinau, tautan reset password telah dikirim.',
        ]);

        $resetToken = null;
        Notification::assertSentTo($learner, ResetPassword::class, function (ResetPassword $notification) use (&$resetToken): bool {
            $resetToken = $notification->token;

            return true;
        });
        $this->assertNotNull($resetToken);

        $this->get(route('password.reset', [
            'token' => $resetToken,
            'email' => $learner->email,
        ]))->assertOk()->assertViewIs('auth.reset-password');

        $this->post('/reset-password', [
            'token' => $resetToken,
            'email' => $learner->email,
            'password' => 'PasswordBaru123',
            'password_confirmation' => 'PasswordBaru123',
        ])->assertRedirect(route('login'));

        $this->get(route('login'))
            ->assertOk()
            ->assertSeeText('Password berhasil diubah, silakan kembali ke aplikasi Sinau.');

        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $learner->email]);
        $this->post('/reset-password', [
            'token' => $resetToken,
            'email' => $learner->email,
            'password' => 'PasswordLain123',
            'password_confirmation' => 'PasswordLain123',
        ])->assertSessionHasErrors('email');
        $this->postJson('/api/login', [
            'email' => $learner->email,
            'password' => 'PasswordBaru123',
        ])->assertOk()
            ->assertJsonPath('message', 'Login berhasil.')
            ->assertJsonMissingPath('data.user.password')
            ->assertJsonMissingPath('data.user.remember_token');
    }

    // FR-S02, AC-07: Admin and Superadmin credentials receive the same 401 without a token.
    public function test_admin_and_superadmin_cannot_login_to_learner_api(): void
    {
        foreach ([Role::Admin, Role::Superadmin] as $role) {
            $account = User::factory()->create([
                'role' => $role->value,
                'password' => 'PasswordValid123',
            ]);

            $this->postJson('/api/login', [
                'email' => $account->email,
                'password' => 'PasswordValid123',
            ])->assertUnauthorized()
                ->assertExactJson(['message' => 'Email atau password tidak sesuai.']);
        }

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    // FR-L03: logout revokes the supplied bearer token.
    public function test_logout_revokes_the_current_token(): void
    {
        $learner = User::factory()->create(['role' => Role::Learner->value]);
        $token = $learner->createToken('test')->plainTextToken;
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withToken($token)->postJson('/api/logout')
            ->assertOk()
            ->assertExactJson(['message' => 'Anda berhasil keluar.']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
        Auth::forgetGuards();
        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
    }

    // FR-L05, AB-16: password updates require a matching current password.
    public function test_password_update_requires_current_password_and_revokes_other_tokens(): void
    {
        $learner = User::factory()->create([
            'role' => Role::Learner->value,
            'password' => 'PasswordLama123',
        ]);
        $currentToken = $learner->createToken('current')->plainTextToken;
        $learner->createToken('other-device');

        $this->withToken($currentToken)->putJson('/api/profile/password', [
            'current_password' => 'bukan-password',
            'password' => 'PasswordBaru123',
            'password_confirmation' => 'PasswordBaru123',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.current_password.0', 'Password saat ini tidak sesuai.');

        $this->withToken($currentToken)->putJson('/api/profile/password', [
            'current_password' => 'PasswordLama123',
            'password' => 'PasswordBaru123',
            'password_confirmation' => 'PasswordBaru123',
        ])->assertOk()
            ->assertJsonMissingPath('password')
            ->assertJsonMissingPath('remember_token');

        $this->assertSame(1, $learner->tokens()->count());
        $this->assertTrue(Hash::check('PasswordBaru123', $learner->fresh()->password));
    }

    // FR-L06, keputusan 6, AC-43: anonymize identity and forum content, soft-delete, and revoke tokens atomically.
    public function test_account_deletion_anonymizes_data_revokes_tokens_and_disables_login(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);
        $learner = User::factory()->create([
            'name' => 'Nama Pribadi',
            'email' => 'pribadi@example.test',
            'business_name' => 'Usaha Pribadi',
            'password' => 'PasswordLama123',
            'role' => Role::Learner->value,
            'consent_given' => true,
            'consent_at' => now(),
        ]);
        $module = Module::query()->create([
            'title' => 'Modul Akun',
            'description' => 'Untuk tes hapus akun.',
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);
        $post = ForumPost::query()->create([
            'module_id' => $module->id,
            'user_id' => $learner->id,
            'content' => 'Konten pribadi.',
        ]);
        $token = $learner->createToken('test')->plainTextToken;

        $this->withToken($token)->deleteJson('/api/account', [
            'password' => 'PasswordLama123',
        ])->assertOk()
            ->assertExactJson(['message' => 'Akun dan data personal Anda berhasil dihapus.']);

        $deleted = User::withTrashed()->findOrFail($learner->id);
        $this->assertNotNull($deleted->deleted_at);
        $this->assertSame('Pengguna dihapus', $deleted->name);
        $this->assertStringNotContainsString('pribadi@example.test', $deleted->email);
        $this->assertNull($deleted->business_name);
        $this->assertFalse(Hash::check('PasswordLama123', $deleted->password));
        $this->assertFalse($deleted->consent_given);
        $this->assertNull($deleted->consent_at);
        $this->assertDatabaseHas('forum_posts', [
            'id' => $post->id,
            'content' => 'Konten dihapus karena akun penulis telah dihapus.',
        ]);
        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->postJson('/api/login', [
            'email' => 'pribadi@example.test',
            'password' => 'PasswordLama123',
        ])->assertUnauthorized()
            ->assertExactJson(['message' => 'Email atau password tidak sesuai.']);

        User::factory()->create(['email' => 'pribadi@example.test']);
        $this->assertDatabaseHas('users', ['email' => 'pribadi@example.test']);
    }

    // FR-S05, FR-SA07, AC-07: Learner tokens cannot access either web panel.
    public function test_learner_cannot_access_admin_or_superadmin_routes(): void
    {
        $learner = User::factory()->create(['role' => Role::Learner->value]);
        $token = $learner->createToken('test')->plainTextToken;

        $this->withToken($token)->get('/admin/dashboard')->assertRedirect(route('login'));
        $this->get('/superadmin/admins')->assertRedirect(route('login'));

        $this->actingAs($learner)->get('/admin/dashboard')->assertForbidden();
        $this->get('/superadmin/admins')->assertForbidden();
    }

    // FR-L03, FR-L05, AB-16: all API account resources exclude credentials.
    public function test_profile_and_me_responses_never_include_password_or_remember_token(): void
    {
        $learner = User::factory()->create(['role' => Role::Learner->value]);
        $token = $learner->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/me')
            ->assertOk()
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.remember_token');

        $this->withToken($token)->putJson('/api/profile', [
            'name' => 'Nama Baru',
            'business_name' => 'Usaha Baru',
        ])->assertOk()
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.remember_token');
    }

    // FR-L01, FR-L03, FR-L04: public account endpoints use their configured Laravel limits.
    public function test_register_login_and_forgot_password_routes_are_throttled(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/register', [])->assertUnprocessable();
        }
        $this->postJson('/api/register', [])->assertTooManyRequests()
            ->assertJsonPath('message', 'Terlalu banyak percobaan. Silakan coba lagi beberapa saat.');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/login', [
                'email' => 'missing@example.test',
                'password' => 'not-a-password',
            ])->assertUnauthorized();
        }
        $this->postJson('/api/login', [
            'email' => 'missing@example.test',
            'password' => 'not-a-password',
        ])->assertTooManyRequests()
            ->assertJsonPath('message', 'Terlalu banyak percobaan login. Silakan coba lagi beberapa saat.');

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->postJson('/api/forgot-password', ['email' => 'missing@example.test'])->assertOk();
        }
        $this->postJson('/api/forgot-password', ['email' => 'missing@example.test'])->assertTooManyRequests()
            ->assertJsonPath('message', 'Terlalu banyak permintaan reset password. Silakan coba lagi beberapa saat.');
    }
}
