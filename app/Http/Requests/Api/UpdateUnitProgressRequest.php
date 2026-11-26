<?php

namespace App\Http\Requests\Api;

class UpdateUnitProgressRequest extends ApiFormRequest
{
    // FR-S03, AC-15: persist only a non-negative integer video position.
    public function rules(): array
    {
        return [
            'last_video_pos' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'last_video_pos.required' => 'Posisi video wajib diisi.',
            'last_video_pos.integer' => 'Posisi video harus berupa bilangan bulat.',
            'last_video_pos.min' => 'Posisi video tidak boleh kurang dari 0 detik.',
        ];
    }
}
