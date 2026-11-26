<?php

namespace App\Http\Requests\Api;

class UpdateLearnerProfileRequest extends ApiFormRequest
{
    // FR-L05, AB-16
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'business_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'email' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'name.string' => 'Nama harus berupa teks.',
            'name.max' => 'Nama maksimal 100 karakter.',
            'business_name.string' => 'Nama usaha harus berupa teks.',
            'business_name.max' => 'Nama usaha maksimal 150 karakter.',
            'email.prohibited' => 'Email tidak dapat diubah melalui fitur ini.',
        ];
    }
}
