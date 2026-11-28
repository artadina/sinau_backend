<?php

namespace App\Http\Requests\Api;

// FR-L29, FR-L30
class SubmitUnitQuizRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answers' => ['required', 'array'],
            'answers.*.question_id' => ['required', 'integer'],
            'answers.*.selected_key' => ['sometimes', 'string', 'size:1'],
            'answers.*.selected_keys' => ['sometimes', 'array'],
            'answers.*.selected_keys.*' => ['string', 'size:1'],
        ];
    }
}
