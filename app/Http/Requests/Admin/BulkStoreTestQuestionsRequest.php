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
            if (! is_array($question)) {
                continue;
            }

            $question['question_format'] ??= 'multiple_choice';
            if ($question['question_format'] === 'true_false') {
                $question['options'] = ['A' => 'Benar', 'B' => 'Salah'];
            } elseif (is_array($question['options'] ?? null)
                && array_key_exists('D', $question['options'])
                && trim((string) ($question['options']['D'] ?? '')) === '') {
                unset($question['options']['D']);
            }

            if (! is_array($question['correct_keys'] ?? null) && isset($question['correct_key'])) {
                $question['correct_keys'] = [$question['correct_key']];
            }
        }

        $this->merge(['questions' => $questions]);
    }

    public function rules(): array
    {
        $rules = [
            'source' => ['required', 'string', Rule::in(['manual', 'csv'])],
            'questions' => ['required', 'array', 'list', 'min:1'],
            'questions.*' => ['required', 'array:question_text,question_format,options,correct_key,correct_keys,explanation'],
            'questions.*.question_text' => ['required', 'string'],
            'questions.*.question_format' => ['required', 'string', Rule::in(['multiple_choice', 'checkbox', 'true_false'])],
            'questions.*.options' => [
                'required',
                'array:A,B,C,D',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_array($value)) {
                        return;
                    }

                    $keys = array_keys($value);
                    if (! preg_match('/^questions\.(\d+)\.options$/', $attribute, $matches)) {
                        $fail('Daftar soal tidak valid.');

                        return;
                    }

                    $format = $this->input('questions.'.$matches[1].'.question_format', 'multiple_choice');
                    if ($format === 'true_false') {
                        if ($keys !== ['A', 'B'] || $value !== ['A' => 'Benar', 'B' => 'Salah']) {
                            $fail('Soal benar/salah harus memakai pilihan Benar dan Salah.');
                        }

                        return;
                    }

                    $expected = array_slice(['A', 'B', 'C', 'D'], 0, count($keys));

                    if ($keys !== $expected) {
                        $fail('Pilihan jawaban harus berurutan dari A dan tidak boleh ada yang kosong.');
                    }
                },
            ],
            'questions.*.options.A' => ['required', 'string', 'max:255'],
            'questions.*.options.B' => ['required', 'string', 'max:255'],
            'questions.*.options.C' => [
                'nullable',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! preg_match('/^questions\.(\d+)\.options\.C$/', $attribute, $matches)) {
                        $fail('Daftar soal tidak valid.');

                        return;
                    }

                    $format = $this->input('questions.'.$matches[1].'.question_format', 'multiple_choice');
                    if ($format !== 'true_false' && trim((string) $value) === '') {
                        $fail('Pilihan C wajib diisi untuk format pilihan ganda dan checkbox.');
                    }
                },
            ],
            'questions.*.options.D' => ['nullable', 'string', 'max:255'],
            'questions.*.correct_keys' => [
                'required',
                'array',
                'min:1',
                'max:4',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_array($value) && count(array_unique($value)) !== count($value)) {
                        $fail('Kunci jawaban tidak boleh dipilih lebih dari sekali.');
                    }
                },
            ],
            'questions.*.correct_keys.*' => ['required', 'string', 'size:1'],
            'questions.*.explanation' => ['required', 'string'],
        ];

        foreach ($this->input('questions', []) as $index => $question) {
            if (! is_array($question)) {
                continue;
            }

            $options = $question['options'] ?? [];
            $keys = is_array($options) ? array_keys($options) : [];
            $rules["questions.{$index}.correct_keys.*"][] = Rule::in($keys);
            if (in_array($question['question_format'] ?? null, ['multiple_choice', 'true_false'], true)) {
                $rules["questions.{$index}.correct_keys"][] = 'size:1';
            }
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'source' => 'Sumber input',
            'questions.*.question_text' => 'Teks soal',
            'questions.*.question_format' => 'Format soal',
            'questions.*.options' => 'Pilihan jawaban',
            'questions.*.options.A' => 'Pilihan A',
            'questions.*.options.B' => 'Pilihan B',
            'questions.*.options.C' => 'Pilihan C',
            'questions.*.options.D' => 'Pilihan D',
            'questions.*.correct_keys' => 'Kunci jawaban',
            'questions.*.correct_keys.*' => 'Kunci jawaban',
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
            'questions.*.correct_keys.max' => 'Kunci jawaban maksimal :max pilihan.',
            'string' => ':attribute harus berupa teks.',
            'size' => ':attribute harus satu huruf.',
            'questions.*.correct_keys.size' => 'Pilih tepat satu kunci jawaban untuk format ini.',
            'questions.*.correct_keys.min' => 'Pilih minimal satu kunci jawaban.',
            'in' => 'Kunci jawaban harus sesuai dengan pilihan yang tersedia.',
            'questions.*.question_format.in' => 'Format soal tidak valid.',
            'questions.*.options.C.required_unless' => 'Pilihan C wajib diisi untuk format pilihan ganda dan checkbox.',
            'questions.min' => 'Tambahkan minimal satu soal sebelum menyimpan.',
            'questions.*.options.*.max' => ':attribute maksimal 255 karakter.',
        ];
    }
}
