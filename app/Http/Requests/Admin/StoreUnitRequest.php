<?php

namespace App\Http\Requests\Admin;

use App\Models\Module;
use App\Models\Unit;
use App\Services\VideoUrlParser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitRequest extends FormRequest
{
    // FR-A03, FR-A04, AC-42
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $routeModule = $this->route('module');
        $moduleId = $routeModule instanceof Module ? $routeModule->id : $routeModule;
        $unit = $this->route('unit');

        return [
            'title' => ['required', 'string', 'max:150'],
            'learning_objective' => ['required', 'string', 'max:255'],
            'video_url' => [
                'required',
                'url',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_string($value) && app(VideoUrlParser::class)->isYoutubeHost($value)
                        && app(VideoUrlParser::class)->youtubeEmbedUrl($value) === null) {
                        $fail('Tautan YouTube tidak valid atau formatnya belum didukung.');
                    }
                },
            ],
            'duration_minutes' => ['nullable', 'numeric', 'gt:0', 'max:35791394.12', 'decimal:0,2'],
            'reading_content' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_string($value) && preg_match_all('/<img\b/i', $value) > 10) {
                        $fail('Materi singkat maksimal berisi 10 gambar.');
                    }
                },
            ],
            'sequence_order' => [
                'required',
                'integer',
                Rule::unique('units', 'sequence_order')
                    ->where('module_id', $moduleId)
                    ->ignore($unit instanceof Unit ? $unit->id : null),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'Judul unit',
            'learning_objective' => 'Tujuan belajar',
            'video_url' => 'Tautan video',
            'duration_minutes' => 'Durasi (menit)',
            'reading_content' => 'Materi singkat',
            'sequence_order' => 'Urutan unit',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'max' => ':attribute maksimal :max karakter.',
            'url' => ':attribute harus berupa URL yang valid.',
            'video_url.url' => ':attribute harus berupa URL yang valid.',
            'integer' => ':attribute harus berupa bilangan bulat.',
            'gt' => ':attribute harus lebih dari 0.',
            'decimal' => ':attribute dapat menggunakan maksimal 2 angka di belakang koma.',
            'unique' => ':attribute sudah digunakan dalam modul ini.',
            'max.file' => ':attribute maksimal 2 MB.',
            'max.string' => ':attribute maksimal :max karakter.',
        ];
    }
}
