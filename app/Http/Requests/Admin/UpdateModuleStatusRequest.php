<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateModuleStatusRequest extends FormRequest
{
    // FR-A04
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(['draft', 'published'])],
        ];
    }

    public function attributes(): array
    {
        return ['status' => 'status modul'];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib dipilih.',
            'in' => ':attribute tidak valid.',
        ];
    }
}
