<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class QuestionFormRequest extends FormRequest
{
    // FR-A05, FR-A11, AC-42
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $options = $this->input('options');

        if (! is_array($options)) {
            return;
        }

        if (array_key_exists('D', $options) && trim((string) ($options['D'] ?? '')) === '') {
            unset($options['D']);
        }

        $this->merge(['options' => $options]);
    }

    public function rules(): array
    {
        $options = $this->input('options', []);
        $availableKeys = is_array($options) ? array_keys($options) : [];

        return [
            'question_text' => ['required', 'string'],
            'options' => [
                'required',
                'array:A,B,C,D',
                'min:3',
                'max:4',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_array($value)) {
                        return;
                    }

                    $keys = array_keys($value);
                    $expected = array_slice(['A', 'B', 'C', 'D'], 0, count($keys));

                    if ($keys !== $expected) {
                        $fail('Pilihan jawaban harus berurutan dari A dan tidak boleh ada yang kosong.');
                    }
                },
            ],
            'options.A' => ['required', 'string', 'max:255'],
            'options.B' => ['required', 'string', 'max:255'],
            'options.C' => ['required', 'string', 'max:255'],
            'options.D' => ['nullable', 'string', 'max:255'],
            'correct_key' => ['required', 'string', 'size:1', Rule::in($availableKeys)],
            'explanation' => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'question_text' => 'Teks soal',
            'options' => 'Pilihan jawaban',
            'options.A' => 'Pilihan A',
            'options.B' => 'Pilihan B',
            'options.C' => 'Pilihan C',
            'options.D' => 'Pilihan D',
            'correct_key' => 'Kunci jawaban',
            'explanation' => 'Pembahasan',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'array' => ':attribute tidak valid.',
            'min' => 'Pilihan jawaban harus berjumlah minimal 3.',
            'max' => ':attribute maksimal :max pilihan.',
            'string' => ':attribute harus berupa teks.',
            'size' => ':attribute harus satu huruf.',
            'in' => 'Kunci jawaban harus sesuai dengan pilihan yang tersedia.',
            'options.*.max' => ':attribute maksimal 255 karakter.',
        ];
    }
}
