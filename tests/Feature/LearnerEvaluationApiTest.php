<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Module;
use App\Models\TestAttempt;
use App\Models\TestQuestion;
use App\Models\Unit;
use App\Models\UnitQuiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LearnerEvaluationApiTest extends TestCase
{
    use RefreshDatabase;

    // FR-L17, FR-L22, AC-19, AC-20, AC-21: unit access, start, and idempotent resume.
    public function test_pretest_unlocks_units_and_resume_keeps_the_same_question_set(): void
    {
        $learner = $this->learner();
        $module = $this->module(preCount: 2, shuffle: false);
        $unit = $this->unit($module);
        $questions = $this->test_questions($module, 'pre', 3);

        $this->withToken($this->token($learner))->getJson("/api/units/{$unit->id}")
            ->assertForbidden()
            ->assertJsonPath('code', 'pretest_required');

        $start = $this->withToken($this->token($learner))
            ->postJson("/api/modules/{$module->id}/tests/pre/start")
            ->assertCreated()
            ->assertJsonCount(2, 'data.questions')
            ->assertJsonMissingPath('data.questions.0.correct_key')
            ->assertJsonMissingPath('data.questions.0.explanation');
        $attemptId = $start->json('data.attempt_id');
        $questionIds = collect($start->json('data.questions'))->pluck('id')->all();

        $this->withToken($this->token($learner))
            ->postJson("/api/modules/{$module->id}/tests/pre/start")
            ->assertOk()
            ->assertJsonPath('data.attempt_id', $attemptId)
            ->assertJsonCount(2, 'data.questions');
        $this->assertSame($questionIds, collect($start->json('data.questions'))->pluck('id')->all());
        $this->assertDatabaseCount('test_attempts', 1);

        $this->withToken($this->token($learner))
            ->putJson("/api/test-attempts/{$attemptId}/answers", [
                'answers' => [['question_id' => $questionIds[0], 'selected_keys' => ['A']]],
            ])->assertOk();

        $resume = $this->withToken($this->token($learner))
            ->postJson("/api/modules/{$module->id}/tests/pre/start")
            ->assertOk();
        $this->assertSame($questionIds, collect($resume->json('data.questions'))->pluck('id')->all());
        $this->assertSame(['A'], $resume->json('data.answers.'.(string) $questionIds[0]));

        $this->withToken($this->token($learner))
            ->postJson("/api/test-attempts/{$attemptId}/finish")
            ->assertOk();
        $this->withToken($this->token($learner))->getJson("/api/units/{$unit->id}")
            ->assertOk();

        $this->withToken($this->token($learner))
            ->postJson("/api/modules/{$module->id}/tests/pre/start")
            ->assertUnprocessable()
            ->assertJsonPath('code', 'pretest_already_done');
    }

    // AC-22, AC-28: blank answers fail, partial checkbox credit, and result is disclosed only after finish.
    public function test_finish_scores_blank_and_checkbox_answers_accurately(): void
    {
        $learner = $this->learner();
        $module = $this->module(preCount: 2);
        $multiple = TestQuestion::query()->create([
            'module_id' => $module->id, 'type' => 'pre', 'question_format' => 'multiple_choice',
            'question_text' => 'Satu jawaban?', 'options' => ['A' => 'Benar', 'B' => 'Salah'],
            'correct_key' => 'A', 'correct_keys' => ['A'], 'explanation' => 'A adalah kunci.',
        ]);
        $checkbox = TestQuestion::query()->create([
            'module_id' => $module->id, 'type' => 'pre', 'question_format' => 'checkbox',
            'question_text' => 'Pilih dua kunci?', 'options' => ['A' => 'Benar 1', 'B' => 'Benar 2', 'C' => 'Salah'],
            'correct_key' => 'A', 'correct_keys' => ['A', 'B'], 'explanation' => 'A dan B benar.',
        ]);

        $start = $this->withToken($this->token($learner))
            ->postJson("/api/modules/{$module->id}/tests/pre/start")->assertCreated();
        $attemptId = $start->json('data.attempt_id');

        $this->withToken($this->token($learner))
            ->getJson("/api/test-attempts/{$attemptId}/result")
            ->assertForbidden()
            ->assertJsonPath('code', 'attempt_not_finished');

        $this->withToken($this->token($learner))->putJson("/api/test-attempts/{$attemptId}/answers", [
            'answers' => [
                ['question_id' => $multiple->id, 'selected_keys' => []],
                ['question_id' => $checkbox->id, 'selected_keys' => ['A']],
            ],
        ])->assertOk();

        $finish = $this->withToken($this->token($learner))
            ->postJson("/api/test-attempts/{$attemptId}/finish")
            ->assertOk();
        $this->assertEquals(25.0, $finish->json('data.score'));
        $finish
            ->assertJsonPath('data.results.0.credit', 0)
            ->assertJsonPath('data.results.1.credit', 0.5);

        $result = $this->withToken($this->token($learner))
            ->getJson("/api/test-attempts/{$attemptId}/result")
            ->assertOk();
        $this->assertNotEmpty($result->json('data.results.0.correct_keys'));
        $this->assertNotEmpty($result->json('data.results.0.explanation'));
    }

    // AC-27, AC-29: post lock and repeated attempts.
    public function test_posttest_is_locked_until_units_complete_and_repeating_creates_new_attempt(): void
    {
        $learner = $this->learner();
        $module = $this->module(postCount: 2);
        $unit = $this->unit($module);
        $this->test_questions($module, 'post', 3);
        $this->completePretest($learner, $module);

        $this->withToken($this->token($learner))
            ->getJson("/api/modules/{$module->id}/tests/post")
            ->assertOk()
            ->assertJsonPath('data.locked', true)
            ->assertJsonPath('data.remaining_active_units', 1);
        $this->withToken($this->token($learner))
            ->postJson("/api/modules/{$module->id}/tests/post/start")
            ->assertForbidden()
            ->assertJsonPath('code', 'post_test_locked');

        DB::table('learner_unit_progress')->insert([
            'user_id' => $learner->id, 'unit_id' => $unit->id,
            'last_video_pos' => 300, 'is_completed' => true, 'updated_at' => now(),
        ]);
        $first = $this->withToken($this->token($learner))
            ->postJson("/api/modules/{$module->id}/tests/post/start")
            ->assertCreated();
        $firstId = $first->json('data.attempt_id');
        $this->withToken($this->token($learner))
            ->postJson("/api/test-attempts/{$firstId}/finish")->assertOk();

        $second = $this->withToken($this->token($learner))
            ->postJson("/api/modules/{$module->id}/tests/post/start")
            ->assertCreated();
        $this->assertNotSame($firstId, $second->json('data.attempt_id'));
        $this->assertDatabaseCount('test_attempts', 3);
    }

    // AC-25, AC-26: unit quiz can repeat and never changes unit/test state.
    public function test_unit_quiz_is_repeatable_practice_and_does_not_complete_unit(): void
    {
        $learner = $this->learner();
        $module = $this->module();
        $unit = $this->unit($module);
        $this->completePretest($learner, $module);
        $questions = collect();
        foreach (range(1, 3) as $number) {
            $questions->push(UnitQuiz::query()->create([
                'unit_id' => $unit->id,
                'question_text' => "Soal {$number}",
                'question_format' => 'multiple_choice',
                'options' => ['A' => 'Benar', 'B' => 'Salah'],
                'correct_key' => 'A',
                'correct_keys' => ['A'],
                'explanation' => 'Pembahasan.',
            ]));
        }

        $this->withToken($this->token($learner))->getJson("/api/units/{$unit->id}/quiz")
            ->assertOk()
            ->assertJsonCount(3, 'data.questions')
            ->assertJsonMissingPath('data.questions.0.correct_key')
            ->assertJsonMissingPath('data.questions.0.explanation');

        $payload = ['answers' => $questions->map(fn (UnitQuiz $q): array => [
            'question_id' => $q->id,
            'selected_keys' => ['B'],
        ])->all()];
        $first = $this->withToken($this->token($learner))
            ->postJson("/api/units/{$unit->id}/quiz/submit", $payload)
            ->assertOk();
        $this->assertEquals(0.0, $first->json('data.score'));
        $first->assertJsonPath('data.return_to_material', true);

        $this->withToken($this->token($learner))
            ->postJson("/api/units/{$unit->id}/quiz/submit", $payload)
            ->assertOk();
        $this->assertDatabaseCount('unit_quiz_attempts', 2);
        $this->assertFalse(DB::table('learner_unit_progress')
            ->where('user_id', $learner->id)->where('unit_id', $unit->id)->exists());
    }

    // AB-09, AB-11: owner isolation and no key leak before finish.
    public function test_learner_cannot_access_another_attempt_or_answer_outside_attempt(): void
    {
        $learnerA = $this->learner();
        $learnerB = $this->learner();
        $module = $this->module(preCount: 1);
        $question = $this->test_questions($module, 'pre', 1)->first();
        $start = $this->withToken($this->token($learnerA))
            ->postJson("/api/modules/{$module->id}/tests/pre/start")->assertCreated();
        $attemptId = $start->json('data.attempt_id');

        Sanctum::actingAs($learnerB);
        $crossResponse = $this
            ->putJson("/api/test-attempts/{$attemptId}/answers", [
                'answers' => [['question_id' => $question->id, 'selected_key' => 'A']],
            ]);
        $crossResponse->assertForbidden();

        $outside = $this->test_questions($module, 'post', 1)->first();
        Sanctum::actingAs($learnerA);
        $this
            ->putJson("/api/test-attempts/{$attemptId}/answers", [
                'answers' => [['question_id' => $outside->id, 'selected_key' => 'A']],
            ])->assertUnprocessable()
            ->assertJsonPath('code', 'question_outside_attempt');

        $startContent = json_encode($start->json());
        $this->assertStringNotContainsString('correct_key', $startContent);
        $this->assertStringNotContainsString('explanation', $startContent);
    }

    private function learner(bool $consent = true): User
    {
        return User::factory()->create([
            'role' => Role::Learner->value,
            'consent_given' => $consent,
            'consent_at' => $consent ? now() : null,
        ]);
    }

    private function token(User $user): string
    {
        return $user->createToken('evaluation-test')->plainTextToken;
    }

    private function module(int $preCount = 2, int $postCount = 2, bool $shuffle = false): Module
    {
        $admin = User::factory()->create(['role' => Role::Admin->value]);

        return Module::query()->create([
            'title' => 'Modul '.uniqid(),
            'description' => 'Deskripsi.',
            'learning_objectives' => 'Tujuan.',
            'status' => 'published',
            'created_by' => $admin->id,
            'active_from' => today()->subDay(),
            'active_until' => today()->addMonth(),
            'pre_question_count' => $preCount,
            'post_question_count' => $postCount,
            'shuffle_questions' => $shuffle,
        ]);
    }

    private function unit(Module $module): Unit
    {
        return Unit::query()->create([
            'module_id' => $module->id,
            'title' => 'Unit evaluasi',
            'learning_objective' => 'Belajar.',
            'video_url' => 'https://example.test/video.mp4',
            'reading_content' => '<p>Materi.</p>',
            'sequence_order' => 1,
            'duration' => 300,
            'is_active' => true,
        ]);
    }

    private function test_questions(Module $module, string $type, int $count)
    {
        $questions = collect();
        foreach (range(1, $count) as $number) {
            $questions->push(TestQuestion::query()->create([
                'module_id' => $module->id,
                'type' => $type,
                'question_format' => 'multiple_choice',
                'question_text' => "Soal {$type} {$number}",
                'options' => ['A' => 'Benar', 'B' => 'Salah', 'C' => 'Lainnya'],
                'correct_key' => 'A',
                'correct_keys' => ['A'],
                'explanation' => "Pembahasan {$type} {$number}.",
            ]));
        }

        return $questions;
    }

    private function completePretest(User $learner, Module $module): void
    {
        TestAttempt::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'type' => 'pre',
            'score' => 50,
            'completed_at' => now(),
        ]);
    }
}
