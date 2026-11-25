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
        $format = $this->input('question_format', 'multiple_choice');
        $options = $this->input('options');

        if ($format === 'true_false') {
            $options = ['A' => 'Benar', 'B' => 'Salah'];
        } elseif (is_array($options) && array_key_exists('D', $options)
            && trim((string) ($options['D'] ?? '')) === '') {
            unset($options['D']);
        }

        $correctKeys = $this->input('correct_keys');
        if (! is_array($correctKeys) && $this->filled('correct_key')) {
            $correctKeys = [$this->input('correct_key')];
        }

        $this->merge([
            'question_format' => $format,
            'options' => $options,
            'correct_keys' => $correctKeys,
        ]);
    }

    public function rules(): array
    {
        $format = $this->input('question_format', 'multiple_choice');
        $options = $this->input('options', []);
        $availableKeys = is_array($options) ? array_keys($options) : [];

        return [
            'question_text' => ['required', 'string'],
            'question_format' => ['required', 'string', Rule::in(['multiple_choice', 'checkbox', 'true_false'])],
            'options' => [
                'required',
                'array:A,B,C,D',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_array($value)) {
                        return;
                    }

                    $keys = array_keys($value);
                    $format = $this->input('question_format', 'multiple_choice');
                    $expected = $format === 'true_false'
                        ? ['A', 'B']
                        : array_slice(['A', 'B', 'C', 'D'], 0, count($keys));

                    if ($keys !== $expected) {
                        $fail($format === 'true_false'
                            ? 'Soal benar/salah harus memakai pilihan Benar dan Salah.'
                            : 'Pilihan jawaban harus berurutan dari A dan tidak boleh ada yang kosong.');
                    }
                },
            ],
            'options.A' => ['required', 'string', 'max:255'],
            'options.B' => ['required', 'string', 'max:255'],
            'options.C' => ['required_unless:question_format,true_false', 'string', 'max:255'],
            'options.D' => ['nullable', 'string', 'max:255'],
            'correct_keys' => [
                'required',
                'array',
                'min:1',
                'max:4',
                function (string $attribute, mixed $value, \Closure $fail) use ($format): void {
                    if (in_array($format, ['multiple_choice', 'true_false'], true)
                        && is_array($value)
                        && count($value) !== 1) {
                        $fail('Pilih tepat satu kunci jawaban untuk jenis soal ini.');
                    }
                },
            ],
            'correct_keys.*' => ['required', 'string', 'size:1', 'distinct', Rule::in($availableKeys)],
            'explanation' => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'question_text' => 'Teks soal',
            'question_format' => 'Format soal',
            'options' => 'Pilihan jawaban',
            'options.A' => 'Pilihan A',
            'options.B' => 'Pilihan B',
            'options.C' => 'Pilihan C',
            'options.D' => 'Pilihan D',
            'correct_keys' => 'Kunci jawaban',
            'correct_keys.*' => 'Kunci jawaban',
            'explanation' => 'Pembahasan',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'array' => ':attribute tidak valid.',
            'min' => 'Pilih minimal satu kunci jawaban.',
            'max' => ':attribute maksimal :max pilihan.',
            'correct_keys.max' => 'Kunci jawaban maksimal :max pilihan.',
            'string' => ':attribute harus berupa teks.',
            'size' => ':attribute harus satu huruf.',
            'in' => 'Kunci jawaban harus sesuai dengan pilihan yang tersedia.',
            'distinct' => 'Kunci jawaban tidak boleh dipilih lebih dari sekali.',
            'question_format.in' => 'Format soal tidak valid.',
            'options.C.required_unless' => 'Pilihan C wajib diisi untuk format pilihan ganda dan checkbox.',
            'options.*.max' => ':attribute maksimal 255 karakter.',
        ];
    }
}
