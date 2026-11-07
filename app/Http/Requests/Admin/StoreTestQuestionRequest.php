<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class StoreTestQuestionRequest extends QuestionFormRequest
{
    // FR-A05, AB-12
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'type' => ['required', 'string', Rule::in([$this->route('type')])],
        ]);
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), ['type' => 'Jenis tes']);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'type.required' => 'Jenis tes wajib dipilih.',
            'type.in' => 'Jenis tes tidak valid.',
        ]);
    }
}
