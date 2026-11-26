<?php

namespace App\Http\Requests\Api;

class ForgotLearnerPasswordRequest extends ApiFormRequest
{
    // FR-L04, BR-L03, AC-06
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['email' => ['required', 'email']];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Masukkan format email yang benar.',
        ];
    }
}
