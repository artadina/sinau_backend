<?php

namespace App\Http\Requests\Superadmin;

use Illuminate\Foundation\Http\FormRequest;

class ApproveModuleRequest extends FormRequest
{
    // PS-23–PS-26: approval requires an explicit policy review checklist.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'policy_checks' => ['required', 'array'],
            'policy_checks.accurate' => ['accepted'],
            'policy_checks.safe' => ['accepted'],
            'policy_checks.copyright' => ['accepted'],
            'policy_checks.privacy' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'policy_checks.required' => 'Checklist kebijakan wajib diperiksa.',
            'policy_checks.*.accepted' => 'Semua standar kebijakan isi harus dipenuhi sebelum modul disetujui.',
        ];
    }
}
