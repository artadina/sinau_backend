<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkStoreTestQuestionsRequest extends FormRequest
{
    // FR-A05, AC-42: validate every row before any test question is inserted.
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $questions = $this->input('questions');

        if (! is_array($questions)) {
            return;
        }

        foreach ($questions as &$question) {
            if (! is_array($question) || ! is_array($question['options'] ?? null)) {
                continue;
            }

            if (array_key_exists('D', $question['options'])
                && trim((string) ($question['options']['D'] ?? '')) === '') {
                unset($question['options']['D']);
            }
        }

        $this->merge(['questions' => $questions]);
    }

    public function rules(): array
    {
        $rules = [
            'source' => ['required', 'string', Rule::in(['manual', 'csv'])],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*' => ['required', 'array:question_text,options,correct_key,explanation'],
            'questions.*.question_text' => ['required', 'string'],
            'questions.*.options' => [
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
            'questions.*.options.A' => ['required', 'string', 'max:255'],
            'questions.*.options.B' => ['required', 'string', 'max:255'],
            'questions.*.options.C' => ['required', 'string', 'max:255'],
            'questions.*.options.D' => ['nullable', 'string', 'max:255'],
            'questions.*.correct_key' => ['required', 'string', 'size:1'],
            'questions.*.explanation' => ['required', 'string'],
        ];

        foreach ($this->input('questions', []) as $index => $question) {
            $keys = array_keys($question['options'] ?? []);
            $rules["questions.{$index}.correct_key"][] = Rule::in($keys);
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'source' => 'Sumber input',
            'questions.*.question_text' => 'Teks soal',
            'questions.*.options' => 'Pilihan jawaban',
            'questions.*.options.A' => 'Pilihan A',
            'questions.*.options.B' => 'Pilihan B',
            'questions.*.options.C' => 'Pilihan C',
            'questions.*.options.D' => 'Pilihan D',
            'questions.*.correct_key' => 'Kunci jawaban',
            'questions.*.explanation' => 'Pembahasan',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'source.in' => ':attribute tidak valid.',
            'array' => ':attribute tidak valid.',
            'min' => ':attribute harus berjumlah minimal 3.',
            'max' => ':attribute maksimal :max pilihan.',
            'string' => ':attribute harus berupa teks.',
            'size' => ':attribute harus satu huruf.',
            'in' => 'Kunci jawaban harus sesuai dengan pilihan yang tersedia.',
            'questions.min' => 'Tambahkan minimal satu soal sebelum menyimpan.',
            'questions.*.options.*.max' => ':attribute maksimal 255 karakter.',
        ];
    }
}
