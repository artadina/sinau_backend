<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkStoreTestQuestionsRequest;
use App\Http\Requests\Admin\StoreTestQuestionRequest;
use App\Http\Requests\Admin\UpdateQuestionSettingsRequest;
use App\Http\Requests\Admin\UpdateTestQuestionRequest;
use App\Models\Module;
use App\Models\TestQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TestQuestionController extends Controller
{
    // FR-A05, AB-12
    public function index(Module $module, string $type): View
    {
        $this->ensureValidType($type);

        return view('admin.questions.index', [
            'module' => $module,
            'type' => $type,
            'questions' => $module->testQuestions()->where('type', $type)->orderBy('id')->get(),
            'preCount' => $module->testQuestions()->where('type', 'pre')->count(),
            'postCount' => $module->testQuestions()->where('type', 'post')->count(),
            'clearImportedQuestions' => session('question-imported', false),
        ]);
    }

    // FR-A05: render the isolated review page for a locally parsed CSV.
    public function import(Module $module, string $type): View
    {
        $this->ensureValidType($type);

        return view('admin.questions.import', compact('module', 'type'));
    }

    // FR-A05
    public function create(Module $module, string $type): View
    {
        $this->ensureValidType($type);

        return view('admin.questions.form', [
            'module' => $module,
            'type' => $type,
            'question' => new TestQuestion([
                'options' => ['A' => '', 'B' => '', 'C' => ''],
            ]),
        ]);
    }

    // FR-A05, AC-42
    public function store(StoreTestQuestionRequest $request, Module $module, string $type): RedirectResponse
    {
        $this->ensureValidType($type);
        $data = $request->validated();
        unset($data['type']);
        $module->testQuestions()->create($data + ['type' => $type]);

        return redirect()->route('admin.modules.questions.index', [$module, $type])
            ->with('status', 'Soal berhasil ditambahkan.');
    }

    // FR-A05, AB-12: save a reviewed batch atomically.
    public function storeBulk(
        BulkStoreTestQuestionsRequest $request,
        Module $module,
        string $type,
    ): RedirectResponse {
        $this->ensureValidType($type);
        $validated = $request->validated();
        $questions = $validated['questions'];
        $source = $validated['source'];

        DB::transaction(function () use ($module, $type, $questions): void {
            foreach ($questions as $question) {
                $module->testQuestions()->create($question + ['type' => $type]);
            }
        });

        return redirect()->route('admin.modules.questions.index', [$module, $type])
            ->with('status', count($questions).' soal berhasil ditambahkan.')
            ->with('question-imported', $source === 'csv');
    }

    // FR-A05
    public function edit(Module $module, string $type, TestQuestion $question): View
    {
        $this->assertQuestionType($type, $question);

        return view('admin.questions.form', compact('module', 'type', 'question'));
    }

    // FR-A05, AC-42
    public function update(
        UpdateTestQuestionRequest $request,
        Module $module,
        string $type,
        TestQuestion $question,
    ): RedirectResponse {
        $this->assertQuestionType($type, $question);
        $data = $request->validated();
        unset($data['type']);
        $question->update($data);

        return redirect()->route('admin.modules.questions.index', [$module, $type])
            ->with('status', 'Soal berhasil diperbarui.');
    }

    // FR-A05
    public function destroy(Module $module, string $type, TestQuestion $question): RedirectResponse
    {
        $this->assertQuestionType($type, $question);
        $blocked = DB::transaction(function () use ($module, $type, $question): bool {
            $lockedModule = Module::query()->lockForUpdate()->findOrFail($module->id);
            $lockedQuestion = $lockedModule->testQuestions()
                ->where('type', $type)
                ->lockForUpdate()
                ->findOrFail($question->id);
            $configuredCount = $type === 'pre'
                ? $lockedModule->pre_question_count
                : $lockedModule->post_question_count;
            $availableCount = $lockedModule->testQuestions()->where('type', $type)->count();

            if ($configuredCount !== null && $availableCount - 1 < $configuredCount) {
                return true;
            }

            $lockedQuestion->delete();

            return false;
        });

        if ($blocked) {
            return redirect()->route('admin.modules.questions.index', [$module, $type])
                ->with('error', 'Soal tidak dapat dihapus karena jumlahnya akan kurang dari pengaturan tes. Kurangi jumlah soal yang diambil terlebih dahulu.');
        }

        return redirect()->route('admin.modules.questions.index', [$module, $type])
            ->with('status', 'Soal berhasil dihapus.');
    }

    // FR-A05, AC-23, AC-42
    public function updateSettings(
        UpdateQuestionSettingsRequest $request,
        Module $module,
    ): RedirectResponse {
        $module->update($request->validated());

        return redirect()->route('admin.modules.questions.index', [$module, 'pre'])
            ->with('status', 'Pengaturan tes berhasil disimpan.');
    }

    private function ensureValidType(string $type): void
    {
        abort_unless(in_array($type, ['pre', 'post'], true), 404);
    }

    private function assertQuestionType(string $type, TestQuestion $question): void
    {
        $this->ensureValidType($type);
        abort_unless($question->type === $type, 404);
    }
}
