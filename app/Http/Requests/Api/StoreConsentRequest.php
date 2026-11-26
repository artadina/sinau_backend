<?php

namespace App\Http\Requests\Api;

use Closure;

class StoreConsentRequest extends ApiFormRequest
{
    // FR-L02, AB-11, AC-04
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'consent_given' => [
                'required',
                static function (string $attribute, mixed $value, Closure $fail): void {
                    if (! in_array($value, [true, 1, '1'], true)) {
                        $fail('Anda perlu mencentang persetujuan untuk melanjutkan.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'consent_given.required' => 'Anda perlu mencentang persetujuan untuk melanjutkan.',
        ];
    }
}
