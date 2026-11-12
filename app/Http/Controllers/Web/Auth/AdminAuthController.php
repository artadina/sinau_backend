<?php

namespace App\Http\Controllers\Web\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetAdminPasswordRequest;
use App\Http\Requests\Auth\SendAdminPasswordResetLinkRequest;
use App\Http\Requests\Auth\StoreAdminLoginRequest;
use App\Http\Requests\Auth\UpdateTemporaryPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    // FR-A01, AC-07: panel login permits Admin and Superadmin accounts only.
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectForRole(Auth::user()->role);
        }

        return view('auth.login');
    }

    // FR-A01, EC-01, AC-05, AC-07, AC-08
    public function store(StoreAdminLoginRequest $request): RedirectResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Email atau kata sandi tidak sesuai.']);
        }

        $request->session()->regenerate();
        $user = $request->user();

        if (! in_array($user->role, [Role::Admin->value, Role::Superadmin->value], true)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403);
        }

        return $this->redirectForRole($user->role);
    }

    // FR-A01
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Anda berhasil keluar.');
    }

    // FR-A01
    public function forgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    // FR-A01, NFR-05: send reset links only to Admin accounts; response does not reveal account existence.
    public function sendResetLink(SendAdminPasswordResetLinkRequest $request): RedirectResponse
    {
        $email = $request->validated('email');
        $isAdmin = User::query()->where('email', $email)->where('role', Role::Admin->value)->exists();

        if ($isAdmin) {
            $status = Password::sendResetLink(['email' => $email]);

            if ($status === Password::RESET_THROTTLED) {
                return back()->withErrors([
                    'email' => 'Tautan reset baru saja diminta. Silakan coba beberapa saat lagi.',
                ]);
            }
        }

        return back()->with('status', 'Jika email terdaftar sebagai Admin, tautan reset akan dikirim melalui email.');
    }

    // FR-A01
    public function resetPassword(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    // FR-A01, NFR-05, AB-16
    public function updateResetPassword(ResetAdminPasswordRequest $request): RedirectResponse
    {
        $credentials = $request->validated();
        $isAdmin = User::query()
            ->where('email', $credentials['email'])
            ->where('role', Role::Admin->value)
            ->exists();

        if (! $isAdmin) {
            return back()->withErrors(['email' => 'Tautan reset tidak valid atau sudah kedaluwarsa.']);
        }

        $status = Password::reset(
            $credentials,
            function (User $user, string $password): void {
                $user->forceFill(['password' => $password])->save();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => 'Tautan reset tidak valid atau sudah kedaluwarsa.']);
        }

        return redirect()->route('login')->with('status', 'Kata sandi berhasil diperbarui. Silakan masuk.');
    }

    // FR-A12, FR-S06, AC-08
    public function editTemporaryPassword(): View
    {
        return view('auth.temporary-password');
    }

    // FR-A12, FR-S06, AB-16, AC-08
    public function updateTemporaryPassword(UpdateTemporaryPasswordRequest $request): RedirectResponse
    {
        $request->user()->forceFill([
            'password' => $request->validated('password'),
            'is_temp_password' => false,
        ])->save();

        $request->session()->regenerate();

        return redirect()->route('admin.dashboard')->with('status', 'Kata sandi berhasil diperbarui.');
    }

    private function redirectForRole(string $role): RedirectResponse
    {
        return match ($role) {
            Role::Admin->value => Auth::user()->is_temp_password
                ? redirect()->route('password.temporary.edit')
                : redirect()->route('admin.dashboard'),
            Role::Superadmin->value => redirect()->route('superadmin.admins.index'),
            default => abort(403),
        };
    }
}
