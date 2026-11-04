<?php

namespace App\Services;

use App\Models\Module;
use Illuminate\Validation\ValidationException;

class ModulePublishValidator
{
    // FR-A04: Step 2A checks for one unit; Step 2B can extend this validator.
    public function validate(Module $module): void
    {
        if (! $module->units()->exists()) {
            throw ValidationException::withMessages([
                'status' => 'Modul perlu memiliki minimal satu unit sebelum dapat diterbitkan.',
            ]);
        }
    }
}
