<?php

namespace App\Http\Requests\Superadmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecideModuleExtensionRequest extends FormRequest
{
    // PS-22, PS-29: Superadmin approves or rejects a pending extension with a reason if rejected.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'notes' => [
                Rule::requiredIf($this->input('decision') === 'reject'),
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function attributes(): array
    {
        return ['notes' => 'Catatan keputusan'];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'decision.in' => 'Keputusan perpanjangan tidak valid.',
        ];
    }
}
