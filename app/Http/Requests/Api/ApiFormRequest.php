<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class ApiFormRequest extends FormRequest
{
    // AB-16: keep all API field validation errors localized and field-addressable.
    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Data yang dikirim belum lengkap atau belum valid.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
