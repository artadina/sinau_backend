<?php

namespace App\Http\Requests\Superadmin;

use Illuminate\Foundation\Http\FormRequest;

class TakedownModuleRequest extends FormRequest
{
    // PS-23, PS-29: takedown requires an auditable reason.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'max:2000']];
    }

    public function attributes(): array
    {
        return ['reason' => 'Alasan takedown'];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
        ];
    }
}
