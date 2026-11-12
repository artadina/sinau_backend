<?php

namespace App\Http\Controllers\Web\Superadmin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Superadmin\SuperadminAccountRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

// FR-SA05, FR-SA06, AB-09: account-only access; never load learner scores or test history.
class LearnerAccountController extends Controller
{
    // FR-SA05: list/search/paginate account fields only, never score data.
    public function index(SuperadminAccountRequest $request): View
    {
        $learners = User::query()
            ->where('role', Role::Learner->value)
            ->when($request->validated('search'), function ($query, string $search): void {
                $query->where(function ($matches) use ($search): void {
                    $matches->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('superadmin.learners.index', [
            'title' => 'Kelola Akun Learner',
            'learners' => $learners,
            'search' => $request->validated('search'),
        ]);
    }

    // FR-SA05, AB-09: show account data only.
    public function show(SuperadminAccountRequest $request, User $user): View
    {
        return view('superadmin.learners.show', [
            'title' => 'Detail Akun Learner',
            'learner' => $user,
        ]);
    }

    // FR-SA05: render the editable account fields.
    public function edit(SuperadminAccountRequest $request, User $user): View
    {
        return view('superadmin.learners.form', [
            'title' => 'Ubah Akun Learner',
            'learner' => $user,
        ]);
    }

    // FR-SA05: update the requested Learner identity fields.
    public function update(SuperadminAccountRequest $request, User $user): RedirectResponse
    {
        $user->update($request->validated());

        return redirect()->route('superadmin.learners.show', $user)
            ->with('status', 'Data Learner berhasil diperbarui.');
    }

    // FR-SA06, AC-12, EC-18: change only role and preserve password state.
    public function updateRole(SuperadminAccountRequest $request, User $user): RedirectResponse
    {
        $user->update(['role' => $request->validated('role')]);

        return redirect()->route('superadmin.learners.index')
            ->with('status', 'Role akun berhasil diperbarui. Kata sandi tidak berubah.');
    }

    // FR-SA05, keputusan 6: anonymize identity and forum text before soft deletion atomically.
    public function destroy(SuperadminAccountRequest $request, User $user): RedirectResponse
    {
        DB::transaction(function () use ($user): void {
            $user->forumPosts()->update([
                'content' => 'Konten dihapus karena akun penulis telah dihapus.',
            ]);

            $user->forceFill([
                'name' => 'Pengguna dihapus',
                'email' => 'deleted-'.$user->id.'-'.Str::random(24).'@deleted.invalid',
                'business_name' => null,
                'password' => Str::password(64, true, true, true),
                'is_temp_password' => false,
                'consent_given' => false,
                'consent_at' => null,
                'email_verified_at' => null,
                'remember_token' => null,
            ])->save();

            $user->delete();
        });

        return redirect()->route('superadmin.learners.index')
            ->with('status', 'Akun Learner dan identitas konten forumnya telah dianonimkan.');
    }
}
