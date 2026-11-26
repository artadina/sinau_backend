<?php

namespace App\Http\Requests\Api;

class DeleteLearnerAccountRequest extends ApiFormRequest
{
    // FR-L06, AC-43
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['password' => ['required', 'current_password:sanctum']];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'Password wajib diisi.',
            'password.current_password' => 'Password tidak sesuai.',
        ];
    }
}
