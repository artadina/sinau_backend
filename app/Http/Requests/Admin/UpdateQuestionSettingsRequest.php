<?php

namespace App\Http\Requests\Admin;

use App\Models\Module;
use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestionSettingsRequest extends FormRequest
{
    // FR-A05, AC-23, AC-42
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $module = $this->route('module');
        $preCount = $module instanceof Module
            ? $module->testQuestions()->where('type', 'pre')->count()
            : 0;
        $postCount = $module instanceof Module
            ? $module->testQuestions()->where('type', 'post')->count()
            : 0;

        return [
            'pre_question_count' => ['required', 'integer', 'min:1', 'max:'.$preCount],
            'post_question_count' => ['required', 'integer', 'min:1', 'max:'.$postCount],
            'shuffle_questions' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'pre_question_count' => 'Jumlah soal Pre-test',
            'post_question_count' => 'Jumlah soal Post-test',
            'shuffle_questions' => 'Acak urutan soal',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'integer' => ':attribute harus berupa bilangan bulat.',
            'min' => ':attribute minimal 1.',
            'max' => ':attribute tidak boleh melebihi jumlah soal di bank.',
            'boolean' => ':attribute tidak valid.',
        ];
    }
}
