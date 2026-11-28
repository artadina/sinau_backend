<?php

namespace App\Http\Requests\Api;

// FR-L22, AC-21
class SaveTestAnswersRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'integer'],
            'answers.*.selected_key' => ['sometimes', 'string', 'size:1'],
            'answers.*.selected_keys' => ['sometimes', 'array'],
            'answers.*.selected_keys.*' => ['string', 'size:1'],
        ];
    }
}
