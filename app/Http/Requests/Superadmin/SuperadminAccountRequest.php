<?php

namespace App\Http\Requests\Superadmin;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// FR-SA02–FR-SA07, AB-16: validate every Superadmin account operation server-side.
class SuperadminAccountRequest extends FormRequest
{
    // FR-SA07, FR-S01: the current account cannot be mutated through a managed-account route.
    public function authorize(): bool
    {
        if ($this->user()?->role !== Role::Superadmin->value) {
            return false;
        }

        $target = $this->route('user');

        return ! ($target instanceof User && $target->is($this->user()));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    // FR-SA02–FR-SA06, AB-16: validate by matched route, including role allow-list.
    public function rules(): array
    {
        $routeName = (string) $this->route()?->getName();

        if (in_array($routeName, ['superadmin.admins.index', 'superadmin.learners.index'], true)) {
            return ['search' => ['nullable', 'string', 'max:255']];
        }

        if ($routeName === 'superadmin.admins.store') {
            return $this->identityRules();
        }

        if ($routeName === 'superadmin.admins.update') {
            return $this->identityRules($this->route('user'));
        }

        if ($routeName === 'superadmin.learners.update') {
            return $this->identityRules($this->route('user')) + [
                'business_name' => ['nullable', 'string', 'max:150'],
            ];
        }

        if (in_array($routeName, ['superadmin.admins.role', 'superadmin.learners.role'], true)) {
            return [
                'role' => ['required', 'string', Rule::in([
                    Role::Learner->value,
                    Role::Admin->value,
                ])],
            ];
        }

        return [];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    // FR-SA02, FR-SA03, FR-SA05: shared name and unique-email constraints.
    private function identityRules(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    // AB-16: show friendly Indonesian field messages.
    public function messages(): array
    {
        return [
            'search.string' => 'Pencarian harus berupa teks.',
            'search.max' => 'Pencarian maksimal 255 karakter.',
            'name.required' => 'Nama wajib diisi.',
            'name.string' => 'Nama harus berupa teks.',
            'name.max' => 'Nama maksimal 100 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.string' => 'Email harus berupa teks.',
            'email.email' => 'Format email belum valid.',
            'email.max' => 'Email maksimal 255 karakter.',
            'email.unique' => 'Email sudah digunakan. Gunakan email lain.',
            'business_name.string' => 'Nama usaha harus berupa teks.',
            'business_name.max' => 'Nama usaha maksimal 150 karakter.',
            'role.required' => 'Role wajib dipilih.',
            'role.string' => 'Role yang dipilih tidak valid.',
            'role.in' => 'Role hanya dapat diubah menjadi Admin atau Learner.',
        ];
    }
}
