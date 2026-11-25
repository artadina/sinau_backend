<?php

namespace App\Http\Requests\Admin;

class UpdateModuleRequest extends StoreModuleRequest
{
    // FR-A02, AC-42: edits use the same field rules as module creation.
    public function rules(): array
    {
        $module = $this->route('module');

        if ($module?->status !== 'published') {
            return parent::rules();
        }

        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'learning_objectives' => ['prohibited'],
            'category' => ['prohibited'],
            'target_audience' => ['prohibited'],
            'difficulty' => ['prohibited'],
            'active_from' => ['prohibited'],
            'active_until' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'prohibited' => 'Modul terbit hanya dapat mengubah judul, deskripsi, atau gambar sampul.',
        ]);
    }
}
