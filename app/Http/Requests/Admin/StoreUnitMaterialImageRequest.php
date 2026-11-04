<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreUnitMaterialImageRequest extends FormRequest
{
    // FR-A16, AC-47: constrain private material images before they reach storage.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'mimetypes:image/jpeg,image/png', 'max:5120'],
        ];
    }

    public function attributes(): array
    {
        return ['image' => 'Gambar materi'];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib dipilih.',
            'file' => ':attribute tidak dapat dibaca.',
            'mimetypes' => ':attribute harus berformat JPG atau PNG.',
            'max' => ':attribute maksimal 5 MB.',
        ];
    }
}
