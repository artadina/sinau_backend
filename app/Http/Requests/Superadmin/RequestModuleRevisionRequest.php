<?php

namespace App\Http\Requests\Superadmin;

use Illuminate\Foundation\Http\FormRequest;

class RequestModuleRevisionRequest extends FormRequest
{
    // PS-28: revision requests must include actionable notes.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['notes' => ['required', 'string', 'max:2000']];
    }

    public function attributes(): array
    {
        return ['notes' => 'Catatan revisi'];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
        ];
    }
}
