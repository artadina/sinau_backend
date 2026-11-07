<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Module;
use App\Models\TestQuestion;
use App\Models\Unit;
use App\Models\UnitQuiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminQuestionManagementTest extends TestCase
{
    use RefreshDatabase;

    // FR-A05, AC-23, AB-12: every Admin manages shared test banks and settings.
    public function test_admin_can_manage_pre_post_banks_and_question_settings(): void
    {
        $admin = $this->admin();
        $module = $this->module($admin);
        $anotherAdmin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.modules.questions.index', [$module, 'pre']))
            ->assertOk()
            ->assertSee('Pre-test')
            ->assertSee('Post-test')
            ->assertSee('Tambah kartu soal')
            ->assertSee('Pilih file CSV')
            ->assertSee('Template CSV')
            ->assertSee('<dialog', false)
            ->assertSee(route('admin.modules.questions.import', [$module, 'pre']), false);

        $this->post(route('admin.modules.questions.store', [$module, 'pre']), $this->questionData([
            'type' => 'pre',
            'question_text' => 'Soal Pre-test 1',
        ]))->assertRedirect(route('admin.modules.questions.index', [$module, 'pre']));
        $preQuestion = $module->testQuestions()->where('question_text', 'Soal Pre-test 1')->firstOrFail();
        $this->createTestQuestion($module, 'pre', 'Soal Pre-test 2');
        $this->createTestQuestion($module, 'pre', 'Soal Pre-test 3');
        $this->createTestQuestion($module, 'post', 'Soal Post-test 1');

        $this->put(route('admin.modules.questions.settings', $module), [
            'pre_question_count' => 4,
            'post_question_count' => 1,
            'shuffle_questions' => 1,
        ])->assertSessionHasErrors([
            'pre_question_count' => 'Jumlah soal Pre-test tidak boleh melebihi jumlah soal di bank.',
        ]);

        $this->actingAs($anotherAdmin)
            ->put(route('admin.modules.questions.settings', $module), [
                'pre_question_count' => 2,
                'post_question_count' => 1,
                'shuffle_questions' => 1,
            ])
            ->assertRedirect(route('admin.modules.questions.index', [$module, 'pre']))
            ->assertSessionHas('status', 'Pengaturan tes berhasil disimpan.');

        $this->assertDatabaseHas('modules', [
            'id' => $module->id,
            'pre_question_count' => 2,
            'post_question_count' => 1,
            'shuffle_questions' => true,
        ]);

        $this->put(route('admin.modules.questions.update', [$module, 'pre', $preQuestion]), $this->questionData([
            'question_text' => 'Soal Pre-test diperbarui',
            'type' => 'pre',
        ]))->assertRedirect(route('admin.modules.questions.index', [$module, 'pre']));

        $this->assertDatabaseHas('test_questions', [
            'id' => $preQuestion->id,
            'question_text' => 'Soal Pre-test diperbarui',
        ]);

        $this->delete(route('admin.modules.questions.destroy', [$module, 'pre', $preQuestion]))
            ->assertSessionHas('status', 'Soal berhasil dihapus.');

        $remainingPreQuestion = $module->testQuestions()->where('type', 'pre')->firstOrFail();
        $this->delete(route('admin.modules.questions.destroy', [$module, 'pre', $remainingPreQuestion]))
            ->assertSessionHas('error');
        $this->assertSame(2, $module->testQuestions()->where('type', 'pre')->count());
    }

    // FR-A05, FR-A11, AC-42: question errors are validated and reported in Indonesian.
    public function test_question_forms_validate_required_choices_and_correct_key(): void
    {
        $admin = $this->admin();
        $module = $this->module($admin);

        $this->actingAs($admin)
            ->get(route('admin.modules.questions.create', [$module, 'pre']))
            ->assertOk()
            ->assertSee('Teks soal')
            ->assertSee('Pilihan A')
            ->assertSee('Opsional');

        $this->post(route('admin.modules.questions.store', [$module, 'pre']), [
            'type' => 'pre',
            'question_text' => '',
            'options' => ['A' => 'Satu', 'B' => 'Dua'],
            'correct_key' => 'C',
            'explanation' => '',
        ])->assertSessionHasErrors(['question_text', 'options', 'options.C', 'correct_key', 'explanation']);

        $this->post(route('admin.modules.questions.store', [$module, 'pre']), $this->questionData([
            'type' => 'pre',
            'correct_key' => 'D',
        ]))->assertSessionHasErrors(['correct_key']);
    }

    // FR-A05, AC-42: batch saves are all-or-nothing and derive test type from the selected tab.
    public function test_admin_can_save_multiple_test_questions_atomically(): void
    {
        $admin = $this->admin();
        $module = $this->module($admin);

        $this->actingAs($admin)
            ->post(route('admin.modules.questions.bulk', [$module, 'pre']), [
                'source' => 'manual',
                'questions' => [
                    $this->questionData(['question_text' => 'Soal batch satu']),
                    $this->questionData([
                        'question_text' => 'Soal batch dua',
                        'options' => [
                            'A' => 'Satu',
                            'B' => 'Dua',
                            'C' => 'Tiga',
                            'D' => '',
                        ],
                        'correct_key' => 'C',
                    ]),
                ],
            ])
            ->assertRedirect(route('admin.modules.questions.index', [$module, 'pre']))
            ->assertSessionHas('status', '2 soal berhasil ditambahkan.');

        $this->assertDatabaseCount('test_questions', 2);
        $this->assertDatabaseHas('test_questions', [
            'module_id' => $module->id,
            'type' => 'pre',
            'question_text' => 'Soal batch dua',
        ]);

        $this->post(route('admin.modules.questions.bulk', [$module, 'post']), [
            'source' => 'manual',
            'questions' => [
                $this->questionData(['question_text' => 'Soal valid']),
                $this->questionData([
                    'question_text' => '',
                    'correct_key' => 'D',
                ]),
            ],
        ])->assertSessionHasErrors(['questions.1.question_text', 'questions.1.correct_key']);

        $this->assertDatabaseCount('test_questions', 2);
    }

    // FR-A05, AC-48: CSV review has a dedicated Admin page isolated from the existing question list.
    public function test_csv_review_page_is_separate_and_admin_only(): void
    {
        $admin = $this->admin();
        $module = $this->module($admin);

        $this->actingAs($admin)
            ->get(route('admin.modules.questions.import', [$module, 'pre']))
            ->assertOk()
            ->assertSee('Pratinjau Impor CSV')
            ->assertSee('Soal untuk ditambahkan')
            ->assertSee('Soal yang sudah ada di bank tidak ikut diubah.')
            ->assertDontSee('Tambah beberapa soal sekaligus');

        foreach ([Role::Learner, Role::Superadmin] as $role) {
            $user = User::factory()->create(['role' => $role->value]);
            $this->actingAs($user)
                ->get(route('admin.modules.questions.import', [$module, 'pre']))
                ->assertForbidden();
        }
    }

    // FR-A11, AC-24: four quiz questions publish; two and six are rejected.
    public function test_module_publish_enforces_three_to_five_quizzes_and_test_bank_settings(): void
    {
        $admin = $this->admin();
        $module = $this->module($admin);
        $unit = $this->unit($module);
        $this->fillTestBanks($module);

        for ($index = 1; $index <= 4; $index++) {
            $this->createQuiz($unit, "Soal kuis {$index}");
        }

        $this->setQuestionSettings($module);
        $this->actingAs($admin)
            ->put(route('admin.modules.status', $module), ['status' => 'published'])
            ->assertRedirect(route('admin.modules.index'));
        $this->assertDatabaseHas('modules', ['id' => $module->id, 'status' => 'published']);

        $this->put(route('admin.modules.status', $module), ['status' => 'draft']);
        $unit->quizzes()->orderBy('id')->limit(2)->get()->each->delete();
        $this->put(route('admin.modules.status', $module), ['status' => 'published'])
            ->assertSessionHasErrors(['status' => "Unit '{$unit->title}' baru punya 2 soal kuis, perlu 3 sampai 5."]);
        $this->assertDatabaseHas('modules', ['id' => $module->id, 'status' => 'draft']);

        for ($index = 5; $index <= 8; $index++) {
            $this->createQuiz($unit, "Soal kuis {$index}");
        }
        $this->put(route('admin.modules.status', $module), ['status' => 'published'])
            ->assertSessionHasErrors(['status' => "Unit '{$unit->title}' memiliki 6 soal kuis; jumlah yang diizinkan 3 sampai 5."]);
    }

    // FR-A11, EC-08: deleting a quiz cannot leave a published unit below three questions.
    public function test_admin_cannot_delete_a_quiz_from_a_published_unit_below_minimum(): void
    {
        $admin = $this->admin();
        $module = $this->module($admin);
        $unit = $this->unit($module);
        $this->fillTestBanks($module);
        $this->setQuestionSettings($module);
        $firstQuiz = $this->createQuiz($unit, 'Soal kuis 1');
        $this->createQuiz($unit, 'Soal kuis 2');
        $this->createQuiz($unit, 'Soal kuis 3');
        $this->actingAs($admin)->put(route('admin.modules.status', $module), ['status' => 'published']);

        $this->delete(route('admin.units.quizzes.destroy', [$unit, $firstQuiz]))
            ->assertRedirect(route('admin.units.quizzes.index', $unit))
            ->assertSessionHas('error', "Soal tidak dapat dihapus karena unit '{$unit->title}' harus memiliki minimal 3 soal kuis selama modul terbit.");

        $this->assertSame(3, $unit->quizzes()->count());
    }

    // FR-A11, AC-42: Admin can add, edit, and delete unit quiz questions.
    public function test_admin_can_create_update_and_delete_a_unit_quiz(): void
    {
        $admin = $this->admin();
        $module = $this->module($admin);
        $unit = $this->unit($module);

        $this->actingAs($admin)
            ->get(route('admin.units.quizzes.index', $unit))
            ->assertOk()
            ->assertSee('Belum ada soal kuis');
        $this->get(route('admin.units.quizzes.create', $unit))->assertOk();

        $this->post(route('admin.units.quizzes.store', $unit), $this->questionData())
            ->assertRedirect(route('admin.units.quizzes.index', $unit))
            ->assertSessionHas('status', 'Soal kuis berhasil ditambahkan.');
        $quiz = $unit->quizzes()->firstOrFail();

        $this->get(route('admin.units.quizzes.edit', [$unit, $quiz]))->assertOk();
        $this->put(route('admin.units.quizzes.update', [$unit, $quiz]), $this->questionData([
            'question_text' => 'Soal kuis diperbarui',
        ]))->assertRedirect(route('admin.units.quizzes.index', $unit));
        $this->assertDatabaseHas('unit_quizzes', [
            'id' => $quiz->id,
            'question_text' => 'Soal kuis diperbarui',
        ]);

        $this->delete(route('admin.units.quizzes.destroy', [$unit, $quiz]))
            ->assertSessionHas('status', 'Soal kuis berhasil dihapus.');
        $this->assertDatabaseMissing('unit_quizzes', ['id' => $quiz->id]);
    }

    // FR-A05, FR-A11, FR-S05: Learner and Superadmin are forbidden from question routes.
    public function test_only_admin_can_access_test_bank_and_unit_quiz_routes(): void
    {
        $module = $this->module($this->admin());
        $unit = $this->unit($module);

        foreach ([Role::Learner, Role::Superadmin] as $role) {
            $user = User::factory()->create(['role' => $role->value]);
            $this->actingAs($user)
                ->get(route('admin.modules.questions.index', [$module, 'pre']))
                ->assertForbidden();
            $this->get(route('admin.units.quizzes.index', $unit))->assertForbidden();
        }
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => Role::Admin->value,
            'is_temp_password' => false,
        ]);
    }

    private function module(User $creator): Module
    {
        return Module::query()->create([
            'title' => 'Modul Soal',
            'description' => '',
            'status' => 'draft',
            'created_by' => $creator->id,
        ]);
    }

    private function unit(Module $module): Unit
    {
        return $module->units()->create([
            'title' => 'Unit Kuis',
            'learning_objective' => 'Memahami materi kuis.',
            'video_url' => 'https://example.test/video.mp4',
            'reading_content' => '',
            'sequence_order' => 1,
        ]);
    }

    private function questionData(array $overrides = []): array
    {
        return array_merge([
            'question_text' => 'Apa jawaban yang benar?',
            'options' => ['A' => 'Jawaban satu', 'B' => 'Jawaban dua', 'C' => 'Jawaban tiga'],
            'correct_key' => 'A',
            'explanation' => 'Pembahasan jawaban.',
        ], $overrides);
    }

    private function createTestQuestion(Module $module, string $type, string $text): TestQuestion
    {
        return $module->testQuestions()->create($this->questionData([
            'question_text' => $text,
            'type' => $type,
        ]));
    }

    private function createQuiz(Unit $unit, string $text): UnitQuiz
    {
        return $unit->quizzes()->create($this->questionData(['question_text' => $text]));
    }

    private function fillTestBanks(Module $module): void
    {
        $this->createTestQuestion($module, 'pre', 'Soal Pre-test');
        $this->createTestQuestion($module, 'post', 'Soal Post-test');
    }

    private function setQuestionSettings(Module $module): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.modules.questions.settings', $module), [
                'pre_question_count' => 1,
                'post_question_count' => 1,
                'shuffle_questions' => 0,
            ])
            ->assertRedirect();
    }
}
