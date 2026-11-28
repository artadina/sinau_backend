<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SaveTestAnswersRequest;
use App\Http\Requests\Api\SubmitUnitQuizRequest;
use App\Models\Module;
use App\Models\TestAttempt;
use App\Models\TestAttemptAnswer;
use App\Models\TestQuestion;
use App\Models\Unit;
use App\Models\UnitQuiz;
use App\Models\UnitQuizAttempt;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LearnerEvaluationController extends Controller
{
    // FR-L17, FR-L18, FR-L19: opening state for a test.
    public function testInfo(Request $request, Module $module, string $type): JsonResponse
    {
        $this->ensureAvailableModule($module);
        $this->ensureType($type);

        $count = $type === 'pre'
            ? (int) $module->pre_question_count
            : (int) $module->post_question_count;

        $attempts = TestAttempt::query()
            ->where('user_id', $request->user()->id)
            ->where('module_id', $module->id)
            ->where('type', $type)
            ->orderByDesc('id')
            ->get();

        $completed = $attempts->first(fn (TestAttempt $attempt): bool => $attempt->completed_at !== null);
        $running = $attempts->first(fn (TestAttempt $attempt): bool => $attempt->completed_at === null);

        if ($type === 'post') {
            $remaining = $this->remainingActiveUnits($module, (int) $request->user()->id);
            if ($remaining > 0) {
                return response()->json(['data' => [
                    'type' => 'post',
                    'title' => 'Post-test '.$module->title,
                    'question_count' => $count,
                    'estimated_minutes' => max(1, (int) ceil($count * 2)),
                    'status' => 'terkunci',
                    'locked' => true,
                    'remaining_active_units' => $remaining,
                    'message' => "Selesaikan {$remaining} unit tersisa untuk membuka post-test.",
                    'hint' => 'Kembali ke materi, lalu tandai setiap unit sebagai selesai.',
                ]]);
            }
        }

        $status = $completed ? 'selesai' : ($running ? 'sedang_berjalan' : 'belum_mulai');

        return response()->json(['data' => [
            'type' => $type,
            'title' => ($type === 'pre' ? 'Pre-test ' : 'Post-test ').$module->title,
            'question_count' => $count,
            'estimated_minutes' => max(1, (int) ceil($count * 2)),
            'status' => $status,
            'locked' => false,
            'attempt_id' => $status === 'sedang_berjalan' ? $running->id : null,
            ...($type === 'pre'
                ? ['pre_test_notice' => 'Ini hanya untuk mengetahui pemahaman awalmu, bukan penilaian']
                : []),
        ]]);
    }

    // FR-L22, FR-L23, AB-05: start once or resume; post-test creates a new attempt.
    public function startTest(Request $request, Module $module, string $type): JsonResponse
    {
        $this->ensureAvailableModule($module);
        $this->ensureType($type);
        $userId = (int) $request->user()->id;

        if ($type === 'post') {
            $remaining = $this->remainingActiveUnits($module, $userId);
            if ($remaining > 0) {
                $this->abortWithCode(403, 'post_test_locked',
                    "Selesaikan {$remaining} unit tersisa untuk membuka post-test.");
            }
        } else {
            $preCompleted = TestAttempt::query()
                ->where('user_id', $userId)->where('module_id', $module->id)
                ->where('type', 'pre')->whereNotNull('completed_at')->exists();
            if ($preCompleted) {
                $this->abortWithCode(422, 'pretest_already_done',
                    'Pre-test modul ini sudah pernah diselesaikan dan tidak dapat diulang.');
            }
        }

        $running = TestAttempt::query()
            ->where('user_id', $userId)->where('module_id', $module->id)->where('type', $type)
            ->whereNull('completed_at')->latest('id')->first();

        if ($running !== null) {
            return $this->runningTestResponse($running, 'Pengerjaan tes dilanjutkan.');
        }

        $count = $type === 'pre' ? (int) $module->pre_question_count : (int) $module->post_question_count;
        $questions = TestQuestion::query()
            ->where('module_id', $module->id)->where('type', $type)->orderBy('id')->get();
        if ($count < 1 || $questions->count() < $count) {
            $this->abortWithCode(422, 'insufficient_questions',
                'Bank soal modul belum memiliki cukup soal untuk tes ini.',
                ['required' => $count, 'available' => $questions->count()]);
        }

        $selected = $module->shuffle_questions
            ? $questions->shuffle()->take($count)
            : $questions->take($count);

        $attempt = DB::transaction(function () use ($userId, $module, $type, $selected): TestAttempt {
            $attempt = TestAttempt::query()->create([
                'user_id' => $userId,
                'module_id' => $module->id,
                'type' => $type,
                'score' => 0,
                'completed_at' => null,
            ]);
            foreach ($selected as $question) {
                TestAttemptAnswer::query()->create([
                    'attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'selected_key' => '',
                    'selected_keys' => [],
                    'is_correct' => false,
                ]);
            }

            return $attempt;
        });

        return $this->runningTestResponse($attempt, 'Pengerjaan tes berhasil dimulai.', 201);
    }

    // FR-L22, AC-21: autosave one or several answers.
    public function saveAnswers(SaveTestAnswersRequest $request, TestAttempt $attempt): JsonResponse
    {
        $this->ensureAttemptOwner($attempt, (int) $request->user()->id);
        if ($attempt->completed_at !== null) {
            $this->abortWithCode(422, 'attempt_already_finished',
                'Jawaban tidak dapat diubah setelah pengerjaan tes selesai.');
        }

        $attempt->load('answers.question');
        $allowed = $attempt->answers->keyBy('question_id');
        $saved = [];

        DB::transaction(function () use ($request, $allowed, &$saved): void {
            foreach ($request->validated('answers') as $input) {
                $questionId = (int) $input['question_id'];
                if (! $allowed->has($questionId)) {
                    $this->abortWithCode(422, 'question_outside_attempt',
                        'Soal yang dikirim tidak termasuk dalam pengerjaan tes ini.');
                }
                $answer = $allowed->get($questionId);
                $question = $answer->question;
                $keys = array_values(array_unique($input['selected_keys'] ?? (
                    isset($input['selected_key']) ? [$input['selected_key']] : []
                )));
                $validKeys = array_keys($question->options);
                foreach ($keys as $key) {
                    if (! in_array($key, $validKeys, true)) {
                        $this->abortWithCode(422, 'invalid_answer_key',
                            'Pilihan jawaban tidak tersedia untuk soal ini.');
                    }
                }
                if ($question->question_format !== 'checkbox' && count($keys) > 1) {
                    $this->abortWithCode(422, 'invalid_answer_key',
                        'Soal ini hanya menerima satu pilihan jawaban.');
                }

                $answer->update([
                    'selected_key' => $keys[0] ?? '',
                    'selected_keys' => $keys,
                    'is_correct' => false,
                ]);
                $saved[] = $questionId;
            }
        });

        return response()->json(['message' => 'Jawaban berhasil disimpan.', 'data' => [
            'attempt_id' => $attempt->id,
            'saved_question_ids' => array_values(array_unique($saved)),
            'status' => 'sedang_berjalan',
        ]]);
    }

    // FR-L20, NFR-09: atomic finish and server-side score.
    public function finishTest(Request $request, TestAttempt $attempt): JsonResponse
    {
        $this->ensureAttemptOwner($attempt, (int) $request->user()->id);
        if ($attempt->completed_at !== null) {
            return $this->finishedTestResponse($attempt);
        }

        $result = DB::transaction(function () use ($attempt): array {
            $lockedAttempt = TestAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            if ($lockedAttempt->completed_at !== null) {
                return $this->buildFinishedData($lockedAttempt);
            }

            $answers = TestAttemptAnswer::query()
                ->where('attempt_id', $lockedAttempt->id)->orderBy('id')
                ->with('question')->get();
            $totalCredit = 0.0;

            foreach ($answers as $answer) {
                $credit = $this->answerCredit($answer);
                $answer->update([
                    'is_correct' => $credit >= 1.0,
                ]);
                $totalCredit += $credit;
            }

            $score = $answers->isEmpty()
                ? 0
                : round($totalCredit / $answers->count() * 100, 2);
            $lockedAttempt->update([
                'score' => number_format((float) $score, 2, '.', ''),
                'completed_at' => now(),
            ]);

            return $this->buildFinishedData($lockedAttempt);
        });

        return response()->json([
            'message' => 'Pengerjaan tes selesai.',
            'data' => $result,
        ]);
    }

    // FR-L21: key and explanation are disclosed only after completion.
    public function testResult(Request $request, TestAttempt $attempt): JsonResponse
    {
        $this->ensureAttemptOwner($attempt, (int) $request->user()->id);
        if ($attempt->completed_at === null) {
            $this->abortWithCode(403, 'attempt_not_finished',
                'Hasil tes baru tersedia setelah pengerjaan diselesaikan.');
        }

        return $this->finishedTestResponse($attempt);
    }

    // FR-L28: unit quiz questions, no answers or explanation.
    public function unitQuiz(Request $request, Unit $unit): JsonResponse
    {
        $this->ensureUnitAccessible($unit, (int) $request->user()->id);
        $questions = UnitQuiz::query()->where('unit_id', $unit->id)->orderBy('id')->get();
        if ($questions->count() < 3 || $questions->count() > 5) {
            $this->abortWithCode(422, 'invalid_quiz_question_count',
                'Kuis unit harus memiliki 3 sampai 5 soal.');
        }

        return response()->json(['data' => [
            'unit_id' => $unit->id,
            'title' => 'Kuis: '.$unit->title,
            'question_count' => $questions->count(),
            'questions' => $questions->values()->map(fn (UnitQuiz $q, int $index): array => [
                'id' => $q->id,
                'position' => $index + 1,
                'question_text' => $q->question_text,
                'question_format' => $q->question_format,
                'options' => $q->options,
            ]),
        ]]);
    }

    // FR-L29, FR-L30, AB-04: repeatable unit quiz practice.
    public function submitUnitQuiz(SubmitUnitQuizRequest $request, Unit $unit): JsonResponse
    {
        $this->ensureUnitAccessible($unit, (int) $request->user()->id);
        $questions = UnitQuiz::query()->where('unit_id', $unit->id)->orderBy('id')->get();
        if ($questions->count() < 3 || $questions->count() > 5) {
            $this->abortWithCode(422, 'invalid_quiz_question_count',
                'Kuis unit harus memiliki 3 sampai 5 soal.');
        }

        $byQuestion = $questions->keyBy('id');
        $provided = collect($request->validated('answers'))->keyBy('question_id');
        if ($provided->keys()->sort()->values()->all() !== $byQuestion->keys()->sort()->values()->all()) {
            $this->abortWithCode(422, 'incomplete_quiz_answers',
                'Semua soal kuis unit wajib dijawab atau dikirim sebagai jawaban kosong.');
        }

        $totalCredit = 0.0;
        $results = [];
        foreach ($questions as $index => $question) {
            $input = $provided->get($question->id);
            $selected = array_values(array_unique($input['selected_keys'] ?? (
                isset($input['selected_key']) ? [$input['selected_key']] : []
            )));
            foreach ($selected as $key) {
                if (! in_array($key, array_keys($question->options), true)) {
                    $this->abortWithCode(422, 'invalid_answer_key',
                        'Pilihan jawaban tidak tersedia untuk soal kuis ini.');
                }
            }
            if ($question->question_format !== 'checkbox' && count($selected) > 1) {
                $this->abortWithCode(422, 'invalid_answer_key',
                    'Soal kuis ini hanya menerima satu pilihan jawaban.');
            }

            $credit = $this->quizCredit($question, $selected);
            $totalCredit += $credit;
            $results[] = [
                'question_id' => $question->id,
                'position' => $index + 1,
                'question_text' => $question->question_text,
                'options' => $question->options,
                'learner_answers' => $selected,
                'correct_keys' => $question->answer_keys,
                'is_correct' => $credit >= 1.0,
                'explanation' => $question->explanation,
            ];
        }

        $score = round($totalCredit / $questions->count() * 100, 2);
        $attempt = UnitQuizAttempt::query()->create([
            'user_id' => (int) $request->user()->id,
            'unit_id' => $unit->id,
            'score' => number_format($score, 2, '.', ''),
            'completed_at' => now(),
        ]);
        $satisfactory = $score >= 70;

        return response()->json(['message' => 'Kuis unit selesai.', 'data' => [
            'attempt_id' => $attempt->id,
            'unit_id' => $unit->id,
            'score' => round((float) $attempt->score, 2),
            'results' => $results,
            'satisfactory' => $satisfactory,
            'return_to_material' => ! $satisfactory,
            'hint' => $satisfactory
                ? 'Anda dapat mengulang kuis atau melanjutkan ke unit berikutnya.'
                : 'Lihat kembali materi unit, lalu ulangi kuis kapan saja.',
        ]]);
    }

    private function runningTestResponse(TestAttempt $attempt, string $message, int $status = 200): JsonResponse
    {
        $attempt->load('answers.question');
        $answers = [];
        $questions = $attempt->answers->map(function (TestAttemptAnswer $answer, int $index) use (&$answers): array {
            $selected = $answer->selected_keys ?: [];
            $answers[(string) $answer->question_id] = $selected;
            $question = $answer->question;

            return [
                'id' => $question->id,
                'position' => $index + 1,
                'question_text' => $question->question_text,
                'question_format' => $question->question_format,
                'options' => $question->options,
            ];
        })->values();

        return response()->json([
            'message' => $message,
            'data' => [
                'attempt_id' => $attempt->id,
                'type' => $attempt->type,
                'status' => 'sedang_berjalan',
                'questions' => $questions,
                'answers' => $answers,
            ],
        ], $status);
    }

    private function finishedTestResponse(TestAttempt $attempt): JsonResponse
    {
        return response()->json([
            'message' => 'Pengerjaan tes selesai.',
            'data' => $this->buildFinishedData($attempt),
        ]);
    }

    private function buildFinishedData(TestAttempt $attempt): array
    {
        $answers = TestAttemptAnswer::query()
            ->where('attempt_id', $attempt->id)->orderBy('id')->with('question')->get();
        $results = $answers->values()->map(function (TestAttemptAnswer $answer, int $index): array {
            $question = $answer->question;
            $selected = $answer->selected_keys ?: [];
            $credit = $this->answerCredit($answer);

            return [
                'question_id' => $question->id,
                'position' => $index + 1,
                'question_text' => $question->question_text,
                'options' => $question->options,
                'learner_answers' => $selected,
                'correct_keys' => $question->answer_keys,
                'is_correct' => $credit >= 1.0,
                'credit' => round($credit, 4),
                'explanation' => $question->explanation,
            ];
        })->all();

        return [
            'attempt_id' => $attempt->id,
            'type' => $attempt->type,
            'status' => 'selesai',
            'score' => round((float) $attempt->score, 2),
            'appreciation' => 'Terima kasih sudah menyelesaikan tes.',
            'results' => $results,
        ];
    }

    private function answerCredit(TestAttemptAnswer $answer): float
    {
        $question = $answer->question;
        $selected = $answer->selected_keys ?: [];

        return $this->quizCredit($question, $selected);
    }

    private function quizCredit(TestQuestion|UnitQuiz $question, array $selected): float
    {
        $correct = $question->answer_keys;
        if ($question->question_format === 'checkbox') {
            if ($correct === [] || $selected === []) {
                return 0.0;
            }
            $validSelected = array_values(array_intersect($selected, $correct));
            $hasWrong = count(array_diff($selected, $correct)) > 0;
            if ($hasWrong) {
                return 0.0;
            }

            return count($validSelected) / count($correct);
        }

        return count($selected) === 1 && in_array($selected[0], $correct, true) ? 1.0 : 0.0;
    }

    private function remainingActiveUnits(Module $module, int $userId): int
    {
        $activeIds = $module->units()->where('is_active', true)->pluck('id');
        if ($activeIds->isEmpty()) {
            return 0;
        }
        $completed = DB::table('learner_unit_progress')
            ->where('user_id', $userId)->where('is_completed', true)
            ->whereIn('unit_id', $activeIds)->count();

        return max(0, $activeIds->count() - $completed);
    }

    private function ensureAvailableModule(Module $module): void
    {
        if (! Module::published()->whereKey($module->id)->exists()) {
            $this->abortWithCode(404, 'module_unavailable',
                'Modul belum diterbitkan atau tidak dapat diakses saat ini.');
        }
    }

    private function ensureType(string $type): void
    {
        if (! in_array($type, ['pre', 'post'], true)) {
            abort(404);
        }
    }

    private function ensureUnitAccessible(Unit $unit, int $userId): void
    {
        if (! Module::published()->whereKey($unit->module_id)->exists()) {
            $this->abortWithCode(403, 'module_unavailable', 'Materi ini belum dapat diakses saat ini.');
        }
        if (! $unit->is_active) {
            $this->abortWithCode(403, 'unit_inactive', 'Unit ini sedang tidak tersedia.');
        }
        $preCompleted = TestAttempt::query()
            ->where('user_id', $userId)->where('module_id', $unit->module_id)
            ->where('type', 'pre')->whereNotNull('completed_at')->exists();
        if (! $preCompleted) {
            $this->abortWithCode(403, 'pretest_required',
                'Selesaikan pre-test modul terlebih dahulu untuk membuka materi.');
        }
    }

    private function ensureAttemptOwner(TestAttempt $attempt, int $userId): void
    {
        $owned = TestAttempt::query()
            ->whereKey($attempt->id)
            ->where('user_id', $userId)
            ->exists();
        if (! $owned) {
            throw new HttpResponseException(response()->json([
                'message' => 'Pengerjaan tes tidak dapat diakses oleh akun ini.',
            ], 403));
        }
    }

    private function abortWithCode(int $status, string $code, string $message, array $extra = []): never
    {
        throw new HttpResponseException(response()->json(array_merge([
            'message' => $message,
            'code' => $code,
        ], $extra), $status));
    }
}
