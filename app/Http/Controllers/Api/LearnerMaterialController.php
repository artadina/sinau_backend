<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateUnitProgressRequest;
use App\Http\Resources\Api\LearnerHomeResource;
use App\Http\Resources\Api\LearnerModuleDetailResource;
use App\Http\Resources\Api\LearnerModuleSummaryResource;
use App\Http\Resources\Api\LearnerUnitProgressResource;
use App\Http\Resources\Api\LearnerUnitCompletionResource;
use App\Http\Resources\Api\LearnerUnitResource;
use App\Models\Module;
use App\Models\Unit;
use App\Services\MaterialHtmlSanitizer;
use App\Services\VideoUrlParser;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LearnerMaterialController extends Controller
{
    // FR-L08, AB-08, AC-14: aggregate published module metadata and owner progress without N+1 queries.
    public function index(Request $request)
    {
        $modules = Module::query()
            ->published()
            ->withCount([
                'units as active_unit_count' => fn ($query) => $query->where('is_active', true),
            ])
            ->withSum([
                'units as estimated_duration' => fn ($query) => $query
                    ->where('is_active', true)
                    ->whereNotNull('duration'),
            ], 'duration')
            ->orderBy('id')
            ->get();

        $completedByModule = DB::table('learner_unit_progress as progress')
            ->join('units', 'units.id', '=', 'progress.unit_id')
            ->where('progress.user_id', $request->user()->id)
            ->where('progress.is_completed', true)
            ->where('units.is_active', true)
            ->whereIn('units.module_id', $modules->modelKeys())
            ->groupBy('units.module_id')
            ->selectRaw('units.module_id, COUNT(*) as completed_count')
            ->pluck('completed_count', 'module_id');

        $modules->each(function (Module $module) use ($completedByModule): void {
            $activeCount = (int) $module->active_unit_count;
            $completedCount = (int) ($completedByModule[$module->id] ?? 0);
            $module->setAttribute(
                'progress_percentage',
                $activeCount === 0 ? 0 : (int) round($completedCount / $activeCount * 100),
            );
        });

        return LearnerModuleSummaryResource::collection($modules);
    }

    // FR-L09, AB-03, AC-14: hide unavailable modules and aggregate the Learner's module state.
    public function showModule(Request $request, Module $module): LearnerModuleDetailResource
    {
        abort_unless(Module::published()->whereKey($module->id)->exists(), 404);

        $module->load([
            'units' => fn ($query) => $query->where('is_active', true)->orderBy('sequence_order'),
        ]);
        $unitIds = $module->units->modelKeys();
        $progressByUnit = DB::table('learner_unit_progress')
            ->where('user_id', $request->user()->id)
            ->whereIn('unit_id', $unitIds)
            ->get()
            ->keyBy('unit_id');

        $completedCount = 0;
        $module->units->each(function (Unit $unit) use ($progressByUnit, &$completedCount): void {
            $progress = $progressByUnit->get($unit->id);
            $completed = (bool) ($progress?->is_completed ?? false);
            $completedCount += (int) $completed;
            $unit->setAttribute(
                'learner_status',
                $completed ? 'selesai' : ($progress === null ? 'belum' : 'sedang'),
            );
        });

        $attempts = DB::table('test_attempts')
            ->where('user_id', $request->user()->id)
            ->where('module_id', $module->id)
            ->whereIn('type', ['pre', 'post'])
            ->get(['type', 'completed_at'])
            ->groupBy('type');

        $allUnitsCompleted = $module->units->isNotEmpty()
            && $completedCount === $module->units->count();

        $module->setAttribute(
            'progress_percentage',
            $module->units->isEmpty()
                ? 0
                : (int) round($completedCount / $module->units->count() * 100),
        );
        $module->setAttribute('pre_test_status', $this->testStatus($attempts->get('pre', collect())));
        $module->setAttribute(
            'post_test_status',
            $allUnitsCompleted
                ? $this->testStatus($attempts->get('post', collect()))
                : 'terkunci',
        );

        return new LearnerModuleDetailResource($module);
    }

    // FR-L10–FR-L12, FR-S07, AC-15, AC-18, AC-19: protect unit access and return sanitized media.
    public function showUnit(
        Request $request,
        Unit $unit,
        MaterialHtmlSanitizer $sanitizer,
        VideoUrlParser $videoUrlParser,
    ): LearnerUnitResource {
        $this->ensureUnitAccessible($unit, (int) $request->user()->id);

        $progress = DB::table('learner_unit_progress')
            ->where('user_id', $request->user()->id)
            ->where('unit_id', $unit->id)
            ->first();

        $video = $this->videoPayload($unit, $videoUrlParser);
        $readingContent = $sanitizer->sanitize($unit->reading_content, $unit);
        $unit->setAttribute(
            'sanitized_reading_content',
            str_replace(
                '/admin/units/'.$unit->id.'/material/images/',
                '/api/units/'.$unit->id.'/material/images/',
                $readingContent,
            ),
        );
        $unit->setAttribute('video_payload', $video);
        $unit->setAttribute('last_video_pos', $progress === null ? null : (int) $progress->last_video_pos);
        $unit->setAttribute('is_completed', (bool) ($progress?->is_completed ?? false));

        return new LearnerUnitResource($unit);
    }

    // FR-S03, AC-15: upsert the caller's position without changing completion state.
    public function updateProgress(UpdateUnitProgressRequest $request, Unit $unit): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $this->ensureUnitAccessible($unit, $userId);
        $position = (int) $request->validated('last_video_pos');
        $timestamp = now();

        DB::table('learner_unit_progress')->upsert(
            [[
                'user_id' => $userId,
                'unit_id' => $unit->id,
                'last_video_pos' => $position,
                'is_completed' => false,
                'updated_at' => $timestamp,
            ]],
            ['user_id', 'unit_id'],
            ['last_video_pos', 'updated_at'],
        );

        $progress = DB::table('learner_unit_progress')
            ->where('user_id', $userId)
            ->where('unit_id', $unit->id)
            ->first();

        return response()->json([
            'message' => 'Posisi belajar berhasil disimpan.',
            'data' => new LearnerUnitProgressResource([
                'unit_id' => $unit->id,
                'last_video_pos' => $progress->last_video_pos,
                'is_completed' => $progress->is_completed,
            ]),
        ]);
    }

    // FR-L13, AC-17: complete once, recalculate module progress, and return the next active unit.
    public function complete(Request $request, Unit $unit): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $this->ensureUnitAccessible($unit, $userId);

        DB::transaction(function () use ($unit, $userId): void {
            $progress = DB::table('learner_unit_progress')
                ->where('user_id', $userId)
                ->where('unit_id', $unit->id)
                ->lockForUpdate()
                ->first();

            if ($progress === null) {
                DB::table('learner_unit_progress')->insert([
                    'user_id' => $userId,
                    'unit_id' => $unit->id,
                    'last_video_pos' => 0,
                    'is_completed' => true,
                    'updated_at' => now(),
                ]);
            } elseif (! $progress->is_completed) {
                DB::table('learner_unit_progress')
                    ->where('user_id', $userId)
                    ->where('unit_id', $unit->id)
                    ->update(['is_completed' => true, 'updated_at' => now()]);
            }
        });

        $activeUnitCount = Unit::query()
            ->where('module_id', $unit->module_id)
            ->where('is_active', true)
            ->count();
        $completedUnitCount = DB::table('learner_unit_progress as progress')
            ->join('units', 'units.id', '=', 'progress.unit_id')
            ->where('progress.user_id', $userId)
            ->where('progress.is_completed', true)
            ->where('units.module_id', $unit->module_id)
            ->where('units.is_active', true)
            ->count();
        $nextUnit = Unit::query()
            ->where('module_id', $unit->module_id)
            ->where('is_active', true)
            ->where('sequence_order', '>', $unit->sequence_order)
            ->orderBy('sequence_order')
            ->first(['id', 'title']);

        return response()->json([
            'message' => 'Unit berhasil ditandai selesai.',
            'data' => new LearnerUnitCompletionResource([
                'unit_id' => $unit->id,
                'is_completed' => true,
                'module_progress_percentage' => $activeUnitCount === 0
                    ? 0
                    : (int) round($completedUnitCount / $activeUnitCount * 100),
                'next_unit' => $nextUnit === null
                    ? null
                    : ['id' => $nextUnit->id, 'title' => $nextUnit->title],
            ]),
        ]);
    }

    // FR-L14, AC-17: select the most recently updated accessible progress for this Learner.
    public function home(Request $request): LearnerHomeResource
    {
        $progress = DB::table('learner_unit_progress as progress')
            ->join('units', 'units.id', '=', 'progress.unit_id')
            ->join('modules', 'modules.id', '=', 'units.module_id')
            ->where('progress.user_id', $request->user()->id)
            ->where('units.is_active', true)
            ->where('modules.status', 'published')
            ->whereDate('modules.active_from', '<=', today())
            ->whereDate('modules.active_until', '>=', today())
            ->orderByDesc('progress.updated_at')
            ->orderByDesc('progress.id')
            ->first([
                'units.id as unit_id',
                'units.title as unit_title',
                'units.duration',
                'progress.last_video_pos',
                'modules.id as module_id',
                'modules.title as module_title',
            ]);

        $continueLearning = $progress === null
            ? null
            : [
                'unit_id' => (int) $progress->unit_id,
                'unit_title' => $progress->unit_title,
                'module_id' => (int) $progress->module_id,
                'module_title' => $progress->module_title,
                'remaining_duration' => $progress->duration === null
                    ? null
                    : max(0, (int) $progress->duration - (int) $progress->last_video_pos),
            ];

        return new LearnerHomeResource($continueLearning);
    }

    private function ensureUnitAccessible(Unit $unit, int $userId): void
    {
        if (! Module::published()->whereKey($unit->module_id)->exists()) {
            $this->abortWithCode(403, 'module_unavailable', 'Materi ini belum dapat diakses saat ini.');
        }

        if (! $unit->is_active) {
            $this->abortWithCode(403, 'unit_inactive', 'Unit ini sedang tidak tersedia.');
        }

        $preTestCompleted = DB::table('test_attempts')
            ->where('user_id', $userId)
            ->where('module_id', $unit->module_id)
            ->where('type', 'pre')
            ->whereNotNull('completed_at')
            ->exists();

        if (! $preTestCompleted) {
            $this->abortWithCode(
                403,
                'pretest_required',
                'Selesaikan pre-test modul terlebih dahulu untuk membuka materi.',
            );
        }
    }

    private function videoPayload(Unit $unit, VideoUrlParser $videoUrlParser): array
    {
        $embedUrl = $videoUrlParser->youtubeEmbedUrl($unit->video_url);
        if ($embedUrl !== null) {
            return ['type' => 'youtube', 'video_id' => basename((string) parse_url($embedUrl, PHP_URL_PATH))];
        }

        $parts = parse_url($unit->video_url);
        if ($videoUrlParser->isYoutubeHost($unit->video_url)
            || ! filter_var($unit->video_url, FILTER_VALIDATE_URL)
            || ! in_array($parts['scheme'] ?? '', ['http', 'https'], true)
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])) {
            $this->abortWithCode(422, 'video_unavailable', 'Video unit belum dapat diputar.');
        }

        return ['type' => 'file', 'url' => $unit->video_url];
    }

    private function testStatus(Collection $attempts): string
    {
        if ($attempts->contains(fn ($attempt): bool => $attempt->completed_at !== null)) {
            return 'selesai';
        }

        return $attempts->isNotEmpty() ? 'sedang' : 'belum';
    }

    private function abortWithCode(int $status, string $code, string $message): never
    {
        throw new HttpResponseException(response()->json([
            'message' => $message,
            'code' => $code,
        ], $status));
    }
}
