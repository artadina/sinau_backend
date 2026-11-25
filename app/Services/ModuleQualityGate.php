<?php

namespace App\Services;

use App\Models\Module;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ModuleQualityGate
{
    // PS-01–PS-20: evaluate the automatic publication checklist from persisted content.
    public function inspect(Module $module): array
    {
        $units = $module->units()->with('quizzes')->get();
        $testQuestions = $module->testQuestions()->get();
        $checks = [];

        $familyIds = app(ModuleVersionFamily::class)->ids($module);
        $titleExists = Module::query()
            ->whereRaw('LOWER(title) = ?', [mb_strtolower((string) $module->title)])
            ->whereNotIn('id', $familyIds)
            ->exists();
        $checks['PS-01'] = $this->check(
            mb_strlen((string) $module->title) >= 10 && mb_strlen((string) $module->title) <= 150 && ! $titleExists,
            $titleExists
                ? 'Judul sudah digunakan modul lain.'
                : 'Judul harus 10–150 karakter dan unik.',
        );
        $checks['PS-02'] = $this->check(
            mb_strlen(trim(strip_tags((string) $module->description))) >= 100,
            'Deskripsi minimal 100 karakter.',
        );

        $objectives = collect(preg_split('/\R/u', (string) $module->learning_objectives))
            ->map(fn (?string $objective): string => trim((string) $objective))
            ->filter();
        $checks['PS-03'] = $this->check(
            $objectives->count() >= 2 && $objectives->count() <= 5,
            'Tujuan belajar harus berisi 2 sampai 5 poin, satu poin per baris.',
        );
        $checks['PS-04'] = $this->check(
            filled($module->category),
            'Kategori/topik wajib dipilih atau diisi.',
        );
        $checks['PS-05'] = $this->check(
            filled($module->target_audience)
                && in_array($module->difficulty, ['Dasar', 'Menengah'], true),
            'Target peserta wajib diisi dan tingkat kesulitan harus Dasar atau Menengah.',
        );
        $checks['PS-06'] = $this->check(
            filled($module->cover_image) && Storage::disk('public')->exists($module->cover_image),
            'Gambar sampul wajib diunggah.',
        );
        $checks['PS-07'] = $this->check(
            $units->count() >= 3 && $units->count() <= 10,
            'Modul harus memiliki 3 sampai 10 unit.',
        );

        $unitStructureValid = $units->isNotEmpty()
            && $units->values()->every(fn ($unit, int $index): bool => $unit->sequence_order === $index + 1
                && filled($unit->title)
                && filled($unit->video_url)
                && filled(strip_tags((string) $unit->reading_content)));
        $checks['PS-08'] = $this->check(
            $unitStructureValid,
            'Setiap unit harus memiliki judul, urutan berurutan tanpa celah, video, dan bacaan.',
        );

        $durationValid = $units->isNotEmpty() && $units->every(
            fn ($unit): bool => $unit->duration !== null && $unit->duration >= 120 && $unit->duration <= 360,
        );
        $checks['PS-09'] = $this->check(
            $durationValid,
            'Durasi video setiap unit harus 2 sampai 6 menit.',
        );
        $videoUrlsValid = $units->isNotEmpty() && $units->every(function ($unit): bool {
            if (! filter_var($unit->video_url, FILTER_VALIDATE_URL)) {
                return false;
            }

            $parts = parse_url($unit->video_url);
            if (! in_array($parts['scheme'] ?? '', ['http', 'https'], true)
                || empty($parts['host'])
                || isset($parts['user'])
                || isset($parts['pass'])) {
                return false;
            }

            return ! app(VideoUrlParser::class)->isYoutubeHost($unit->video_url)
                || app(VideoUrlParser::class)->youtubeEmbedUrl($unit->video_url) !== null;
        });
        $checks['PS-10'] = $this->check(
            $videoUrlsValid,
            'Tautan video harus berupa URL yang valid dan memakai format YouTube yang didukung atau URL video langsung.',
        );

        $readingsValid = $units->isNotEmpty() && $units->every(
            fn ($unit): bool => $this->wordCount((string) $unit->reading_content) >= 150,
        );
        $checks['PS-11'] = $this->check(
            $readingsValid,
            'Bacaan setiap unit minimal 150 kata.',
        );
        $totalDuration = $units->sum('duration');
        $checks['PS-12'] = $this->check(
            $units->isNotEmpty() && $totalDuration <= 3600,
            'Total estimasi durasi modul maksimal 60 menit.',
        );

        $quizzesValid = $units->isNotEmpty() && $units->every(
            fn ($unit): bool => $unit->quizzes->count() >= 3 && $unit->quizzes->count() <= 5,
        );
        $checks['PS-13'] = $this->check(
            $quizzesValid,
            'Setiap unit harus memiliki 3 sampai 5 soal kuis.',
        );

        $banksValid = collect(['pre', 'post'])->every(
            fn (string $type): bool => $module->testQuestions()->where('type', $type)->count() >= 10,
        );
        $checks['PS-14'] = $this->check(
            $banksValid,
            'Bank soal Pre-test dan Post-test masing-masing minimal 10 soal.',
        );
        $allQuestions = $testQuestions->concat($units->flatMap(fn ($unit) => $unit->quizzes));
        $checks['PS-15'] = $this->check(
            $allQuestions->every(
                fn ($question): bool => filled(trim((string) $question->question_text))
                    && filled(trim((string) $question->explanation)),
            ),
            'Setiap soal harus memiliki pembahasan.',
        );
        $optionsValid = $allQuestions->every(
            fn ($question): bool => is_array($question->options)
                && count($question->options) >= 3
                && is_string($question->correct_key)
                && array_key_exists($question->correct_key, $question->options),
        );
        $checks['PS-16'] = $this->check(
            $optionsValid,
            'Setiap soal harus memiliki minimal 3 opsi dan tepat satu kunci jawaban.',
        );
        $normalizedQuestions = $allQuestions
            ->map(fn ($question): string => Str::of(strip_tags((string) $question->question_text))
                ->squish()
                ->lower()
                ->toString())
            ->filter();
        $checks['PS-17'] = $this->check(
            $normalizedQuestions->count() === $normalizedQuestions->unique()->count(),
            'Tidak boleh ada soal duplikat dalam satu modul.',
        );

        $activeFrom = $module->active_from;
        $activeUntil = $module->active_until;
        $checks['PS-18'] = $this->check(
            $activeFrom !== null && $activeUntil !== null,
            'Tanggal mulai dan berakhir periode aktif wajib diisi.',
        );
        $periodValid = $activeFrom !== null && $activeUntil !== null
            && $activeUntil->greaterThanOrEqualTo($activeFrom->copy()->addDays(30))
            && $activeUntil->lessThanOrEqualTo($activeFrom->copy()->addMonthsNoOverflow(12));
        $checks['PS-19'] = $this->check(
            $periodValid,
            'Periode aktif harus minimal 30 hari dan maksimal 12 bulan.',
        );
        $approvalDate = $module->approved_at?->copy()->startOfDay() ?? today();
        $checks['PS-20'] = $this->check(
            $activeFrom !== null && $activeFrom->greaterThanOrEqualTo($approvalDate),
            'Tanggal mulai tidak boleh sebelum tanggal persetujuan.',
        );

        foreach ($checks as $code => &$check) {
            $check['url'] = match (true) {
                in_array($code, ['PS-07', 'PS-08', 'PS-09', 'PS-10', 'PS-11', 'PS-12', 'PS-13'], true)
                    => route('admin.modules.units.index', $module),
                in_array($code, ['PS-14', 'PS-15', 'PS-16', 'PS-17'], true)
                    => route('admin.modules.questions.index', [$module, 'pre']),
                default => route('admin.modules.edit', $module),
            };
        }
        unset($check);

        return $checks;
    }

    public function failed(array $checks): array
    {
        return collect($checks)
            ->filter(fn (array $check): bool => ! $check['passed'])
            ->map(fn (array $check): string => $check['message'])
            ->all();
    }

    private function check(bool $passed, string $message): array
    {
        return ['passed' => $passed, 'message' => $message];
    }

    private function wordCount(string $content): int
    {
        preg_match_all('/[\p{L}\p{N}]+/u', strip_tags($content), $matches);

        return count($matches[0]);
    }
}
