<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUnitQuizRequest;
use App\Http\Requests\Admin\UpdateUnitQuizRequest;
use App\Models\Unit;
use App\Models\UnitQuiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UnitQuizController extends Controller
{
    // FR-A11, AB-12
    public function index(Unit $unit): View
    {
        return view('admin.quizzes.index', [
            'unit' => $unit,
            'module' => $unit->module,
            'quizzes' => $unit->quizzes()->orderBy('id')->get(),
        ]);
    }

    // FR-A11
    public function create(Unit $unit): View
    {
        return view('admin.quizzes.form', [
            'unit' => $unit,
            'module' => $unit->module,
            'quiz' => new UnitQuiz([
                'options' => ['A' => '', 'B' => '', 'C' => ''],
            ]),
        ]);
    }

    // FR-A11, AC-42
    public function store(StoreUnitQuizRequest $request, Unit $unit): RedirectResponse
    {
        $data = $request->validated();
        $blocked = DB::transaction(function () use ($unit, $data): bool {
            $lockedUnit = Unit::query()->lockForUpdate()->findOrFail($unit->id);
            $count = $lockedUnit->quizzes()->count();

            if ($lockedUnit->module()->value('status') === 'published' && $count >= 5) {
                return true;
            }

            $lockedUnit->quizzes()->create($data);

            return false;
        });

        if ($blocked) {
            return redirect()->route('admin.units.quizzes.index', $unit)
                ->with('error', "Unit '{$unit->title}' sudah memiliki 5 soal kuis; modul terbit tidak dapat ditambah menjadi lebih dari 5.");
        }

        return redirect()->route('admin.units.quizzes.index', $unit)
            ->with('status', 'Soal kuis berhasil ditambahkan.');
    }

    // FR-A11
    public function edit(Unit $unit, UnitQuiz $quiz): View
    {
        return view('admin.quizzes.form', [
            'unit' => $unit,
            'module' => $unit->module,
            'quiz' => $quiz,
        ]);
    }

    // FR-A11, AC-42
    public function update(UpdateUnitQuizRequest $request, Unit $unit, UnitQuiz $quiz): RedirectResponse
    {
        $quiz->update($request->validated());

        return redirect()->route('admin.units.quizzes.index', $unit)
            ->with('status', 'Soal kuis berhasil diperbarui.');
    }

    // FR-A11, EC-08
    public function destroy(Unit $unit, UnitQuiz $quiz): RedirectResponse
    {
        $error = DB::transaction(function () use ($unit, $quiz): ?string {
            $lockedUnit = Unit::query()->lockForUpdate()->findOrFail($unit->id);
            $lockedQuiz = $lockedUnit->quizzes()->lockForUpdate()->findOrFail($quiz->id);
            $count = $lockedUnit->quizzes()->count();

            if ($lockedUnit->module()->value('status') === 'published' && $count - 1 < 3) {
                return "Soal tidak dapat dihapus karena unit '{$lockedUnit->title}' harus memiliki minimal 3 soal kuis selama modul terbit.";
            }

            $lockedQuiz->delete();

            return null;
        });

        if ($error !== null) {
            return redirect()->route('admin.units.quizzes.index', $unit)->with('error', $error);
        }

        return redirect()->route('admin.units.quizzes.index', $unit)
            ->with('status', 'Soal kuis berhasil dihapus.');
    }
}
