<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreForumReplyRequest extends FormRequest
{
    // FR-A13, AB-16
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['content' => ['required', 'string', 'max:1000']];
    }

    public function attributes(): array
    {
        return ['content' => 'Balasan'];
    }

    public function messages(): array
    {
        return [
            'content.required' => ':attribute wajib diisi.',
            'content.string' => ':attribute harus berupa teks.',
            'content.max' => ':attribute maksimal :max karakter.',
        ];
    }
}
