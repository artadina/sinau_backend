<?php

namespace App\Services;

use App\Models\Module;
use Illuminate\Validation\ValidationException;

class ModulePublishValidator
{
    // FR-A04, FR-A05, FR-A11: check all publication prerequisites in one server-side validator.
    public function validate(Module $module): void
    {
        $units = $module->units()->withCount('quizzes')->get();
        $errors = [];

        if ($units->isEmpty()) {
            $errors[] = 'Modul perlu memiliki minimal satu unit sebelum dapat diterbitkan.';
        }

        foreach ($units as $unit) {
            $quizCount = $unit->quizzes_count;

            if ($quizCount < 3 || $quizCount > 5) {
                $errors[] = $quizCount < 3
                    ? "Unit '{$unit->title}' baru punya {$quizCount} soal kuis, perlu 3 sampai 5."
                    : "Unit '{$unit->title}' memiliki {$quizCount} soal kuis; jumlah yang diizinkan 3 sampai 5.";
            }
        }

        foreach (['pre', 'post'] as $type) {
            $requiredCount = $type === 'pre' ? $module->pre_question_count : $module->post_question_count;
            $availableCount = $module->testQuestions()->where('type', $type)->count();
            $label = $type === 'pre' ? 'Pre-test' : 'Post-test';

            if ($requiredCount === null || $requiredCount < 1) {
                $errors[] = "Jumlah soal {$label} belum diatur; tentukan minimal 1 soal.";
            } elseif ($availableCount < $requiredCount) {
                $errors[] = "Bank soal {$label} baru memiliki {$availableCount} soal, sedangkan pengaturan membutuhkan {$requiredCount}.";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'status' => implode(' ', $errors),
            ]);
        }
    }
}
