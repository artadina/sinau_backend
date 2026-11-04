<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUnitMaterialImageRequest;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UnitMaterialImageController extends Controller
{
    // FR-A16: store uploaded material images on the private disk with opaque names.
    public function store(StoreUnitMaterialImageRequest $request, Unit $unit): JsonResponse
    {
        $directory = "unit-materials/{$unit->id}";
        $files = Storage::disk('local')->files($directory);

        if (count($files) >= 10) {
            return response()->json(['message' => 'Materi maksimal berisi 10 gambar. Hapus gambar lama dan simpan materi terlebih dahulu.'], 422);
        }

        $file = $request->file('image');
        $extension = $file->getMimeType() === 'image/png' ? 'png' : 'jpg';
        $filename = Str::uuid().'.'.$extension;
        $path = $file->storeAs($directory, $filename, 'local');

        if ($path === false) {
            return response()->json(['message' => 'Gambar belum dapat disimpan. Silakan coba kembali.'], 500);
        }

        return response()->json([
            'url' => route('admin.units.material-images.show', [$unit, $filename], false),
            'alt' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
        ], 201);
    }

    // FR-A16, AC-45: only the Admin panel may display private images for its unit editor/preview.
    public function show(Unit $unit, string $image): BinaryFileResponse
    {
        return $this->fileResponse($unit, $image);
    }

    // FR-A16, FR-L11: Learners can only fetch images referenced by a published module.
    public function showForLearner(Unit $unit, string $image): BinaryFileResponse
    {
        $reference = '/admin/units/'.$unit->id.'/material/images/'.$image;

        abort_unless(
            $unit->module()->where('status', 'published')->exists()
            && str_contains((string) $unit->reading_content, $reference),
            404,
        );

        return $this->fileResponse($unit, $image);
    }

    private function fileResponse(Unit $unit, string $image): BinaryFileResponse
    {
        abort_unless(preg_match('/^[a-f0-9-]+\.(?:jpg|jpeg|png)$/i', $image), 404);

        $path = Storage::disk('local')->path("unit-materials/{$unit->id}/{$image}");
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Disposition' => 'inline; filename="'.$image.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
