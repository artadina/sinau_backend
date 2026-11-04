<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUnitRequest;
use App\Http\Requests\Admin\UpdateUnitRequest;
use App\Models\Module;
use App\Models\Unit;
use App\Services\MaterialHtmlSanitizer;
use App\Services\VideoUrlParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class UnitController extends Controller
{
    // FR-A03, FR-A04, AB-12
    public function index(Module $module): View
    {
        return view('admin.units.index', [
            'module' => $module,
            'units' => $module->units()->get(),
        ]);
    }

    // FR-A03
    public function create(Module $module): View
    {
        return view('admin.units.form', ['module' => $module, 'unit' => new Unit]);
    }

    // FR-A03, FR-A04, AC-42
    public function store(StoreUnitRequest $request, Module $module, MaterialHtmlSanitizer $sanitizer): RedirectResponse
    {
        $data = $request->validated();
        $data = $this->mapDurationToSeconds($data);
        $readingContent = $data['reading_content'] ?? '';

        $unit = DB::transaction(function () use ($module, $data, $readingContent, $sanitizer): Unit {
            $unit = $module->units()->create(array_merge($data, ['reading_content' => '']));
            $unit->update(['reading_content' => $sanitizer->sanitize($readingContent, $unit)]);

            return $unit;
        });

        if ($request->boolean('continue_editing')) {
            return redirect()->route('admin.modules.units.edit', [$module, $unit])
                ->with('status', 'Unit berhasil ditambahkan. Periksa kembali gambar materi sebelum menyimpan.');
        }

        return redirect()->route('admin.modules.units.index', $module)->with('status', 'Unit berhasil ditambahkan.');
    }

    // FR-A03
    public function edit(Module $module, Unit $unit): View
    {
        return view('admin.units.form', compact('module', 'unit'));
    }

    // FR-A03, FR-A04, AC-42
    public function update(UpdateUnitRequest $request, Module $module, Unit $unit, MaterialHtmlSanitizer $sanitizer): RedirectResponse
    {
        $data = $request->validated();
        $data = $this->mapDurationToSeconds($data);
        $data['reading_content'] = $sanitizer->sanitize($data['reading_content'] ?? '', $unit);

        $unit->update($data);
        $this->deleteUnreferencedImages($unit, $data['reading_content']);

        return redirect()->route('admin.modules.units.index', $module)->with('status', 'Unit berhasil diperbarui.');
    }

    // FR-A03
    public function destroy(Module $module, Unit $unit): RedirectResponse
    {
        $hasQuizHistory = DB::transaction(function () use ($unit): bool {
            $lockedUnit = Unit::query()->lockForUpdate()->findOrFail($unit->id);

            if (DB::table('unit_quiz_attempts')->where('unit_id', $lockedUnit->id)->exists()) {
                return true;
            }

            $lockedUnit->delete();

            return false;
        });

        if ($hasQuizHistory) {
            return redirect()->route('admin.modules.units.index', $module)
                ->with('error', 'Unit tidak dapat dihapus karena sudah memiliki riwayat kuis.');
        }

        $this->deleteUnitImages($unit);

        return redirect()->route('admin.modules.units.index', $module)->with('status', 'Unit berhasil dihapus.');
    }

    // FR-A04
    public function move(Module $module, Unit $unit, string $direction): RedirectResponse
    {
        abort_unless(in_array($direction, ['up', 'down'], true), 404);

        DB::transaction(function () use ($module, $unit, $direction): void {
            $units = $module->units()->lockForUpdate()->get();
            $currentIndex = $units->search(fn (Unit $candidate): bool => $candidate->is($unit));
            $neighborIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

            if ($currentIndex === false || ! $units->has($neighborIndex)) {
                return;
            }

            $neighbor = $units[$neighborIndex];
            $temporaryOrder = 0;
            while ($units->contains('sequence_order', $temporaryOrder)) {
                $temporaryOrder--;
            }

            $currentOrder = $unit->sequence_order;
            $neighborOrder = $neighbor->sequence_order;

            $unit->update(['sequence_order' => $temporaryOrder]);
            $neighbor->update(['sequence_order' => $currentOrder]);
            $unit->update(['sequence_order' => $neighborOrder]);
        });

        return redirect()->route('admin.modules.units.index', $module)->with('status', 'Urutan unit berhasil diperbarui.');
    }

    // FR-A03
    public function preview(Unit $unit, MaterialHtmlSanitizer $sanitizer, VideoUrlParser $videoUrlParser): View
    {
        return view('admin.units.preview', [
            'module' => $unit->module,
            'unit' => $unit,
            'readingContent' => $sanitizer->sanitize($unit->reading_content, $unit),
            'youtubeEmbedUrl' => $videoUrlParser->youtubeEmbedUrl($unit->video_url),
        ]);
    }

    private function deleteUnreferencedImages(Unit $unit, string $readingContent): void
    {
        $directory = "unit-materials/{$unit->id}";

        foreach (Storage::disk('local')->files($directory) as $path) {
            $filename = basename($path);
            $reference = '/admin/units/'.$unit->id.'/material/images/'.$filename;

            if (! str_contains($readingContent, $reference) && ! Storage::disk('local')->delete($path)) {
                Log::warning('Gambar materi yang tidak terpakai gagal dihapus.', ['unit_id' => $unit->id, 'path' => $path]);
            }
        }
    }

    private function mapDurationToSeconds(array $data): array
    {
        $minutes = $data['duration_minutes'] ?? null;
        unset($data['duration_minutes']);

        $data['duration'] = $minutes === null ? null : (int) round((float) $minutes * 60);

        return $data;
    }

    private function deleteUnitImages(Unit $unit): void
    {
        $directory = "unit-materials/{$unit->id}";

        if (Storage::disk('local')->directoryExists($directory) && ! Storage::disk('local')->deleteDirectory($directory)) {
            Log::warning('Gambar unit gagal dihapus setelah unit dihapus.', ['unit_id' => $unit->id]);
        }
    }
}
