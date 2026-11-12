<?php

namespace App\Http\Controllers\Web\Superadmin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Superadmin\SuperadminAccountRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

// FR-SA02–FR-SA04, AC-11–AC-13: manage Admin accounts without touching shared content.
class AdminAccountController extends Controller
{
    // FR-SA03: list active Admin accounts with pagination and optional search.
    public function index(SuperadminAccountRequest $request): View
    {
        $admins = User::query()
            ->where('role', Role::Admin->value)
            ->when($request->validated('search'), function ($query, string $search): void {
                $query->where(function ($matches) use ($search): void {
                    $matches->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('superadmin.admins.index', [
            'title' => 'Kelola Akun Admin',
            'admins' => $admins,
            'search' => $request->validated('search'),
            'temporaryPassword' => $request->session()->pull('created_admin_temporary_password'),
        ]);
    }

    // FR-SA02: render a form that accepts only name and email.
    public function create(SuperadminAccountRequest $request): View
    {
        return view('superadmin.admins.form', [
            'title' => 'Tambah Akun Admin',
            'admin' => null,
        ]);
    }

    // FR-SA02, AC-11: create role and one-time password on the server.
    public function store(SuperadminAccountRequest $request): RedirectResponse
    {
        $temporaryPassword = Str::password(32, true, true, true);

        $admin = User::create([
            ...$request->validated(),
            'password' => $temporaryPassword,
            'role' => Role::Admin->value,
            'is_temp_password' => true,
        ]);

        $request->session()->flash('created_admin_temporary_password', $temporaryPassword);

        return redirect()->route('superadmin.admins.index')
            ->with('status', "Akun Admin {$admin->name} berhasil dibuat.");
    }

    // FR-SA03: show account details without exposing any password.
    public function show(SuperadminAccountRequest $request, User $user): View
    {
        return view('superadmin.admins.show', [
            'title' => 'Detail Akun Admin',
            'admin' => $user,
        ]);
    }

    // FR-SA03: render the account-edit form.
    public function edit(SuperadminAccountRequest $request, User $user): View
    {
        return view('superadmin.admins.form', [
            'title' => 'Ubah Akun Admin',
            'admin' => $user,
        ]);
    }

    // FR-SA03: update only the Admin identity fields.
    public function update(SuperadminAccountRequest $request, User $user): RedirectResponse
    {
        $user->update($request->validated());

        return redirect()->route('superadmin.admins.show', $user)
            ->with('status', 'Data Admin berhasil diperbarui.');
    }

    // FR-SA04, AC-12, EC-18: update role only; keep password state unchanged.
    public function updateRole(SuperadminAccountRequest $request, User $user): RedirectResponse
    {
        $user->update(['role' => $request->validated('role')]);

        return redirect()->route('superadmin.admins.index')
            ->with('status', 'Role akun berhasil diperbarui. Kata sandi tidak berubah.');
    }

    // FR-SA03, AB-12, AC-13: soft-delete the account and retain its shared content.
    public function destroy(SuperadminAccountRequest $request, User $user): RedirectResponse
    {
        $user->delete();

        return redirect()->route('superadmin.admins.index')
            ->with('status', 'Akun Admin dihapus. Konten yang dibuat tetap tersedia.');
    }
}
