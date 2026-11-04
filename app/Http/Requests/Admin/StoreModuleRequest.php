<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreModuleRequest extends FormRequest
{
    // FR-A02, AC-42
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'learning_objectives' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'Judul modul',
            'description' => 'Deskripsi',
            'cover_image' => 'Gambar sampul',
            'learning_objectives' => 'Tujuan belajar',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'max' => ':attribute maksimal :max karakter.',
            'image' => ':attribute harus berupa gambar JPG atau PNG.',
            'mimes' => ':attribute harus berupa gambar JPG atau PNG.',
            'max.file' => ':attribute maksimal 2 MB.',
            'max.string' => ':attribute maksimal :max karakter.',
        ];
    }
}
