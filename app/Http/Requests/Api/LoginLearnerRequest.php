<?php

namespace App\Http\Requests\Api;

class LoginLearnerRequest extends ApiFormRequest
{
    // FR-L03, FR-S02, AB-02, EC-01, AC-05, AC-07
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Masukkan format email yang benar.',
            'password.required' => 'Password wajib diisi.',
            'password.string' => 'Password harus berupa teks.',
        ];
    }
}
