<?php

namespace App\Http\Controllers\Web\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Superadmin\ApproveModuleRequest;
use App\Http\Requests\Superadmin\DecideModuleExtensionRequest;
use App\Http\Requests\Superadmin\RequestModuleRevisionRequest;
use App\Http\Requests\Superadmin\TakedownModuleRequest;
use App\Models\Module;
use App\Models\ModuleExtensionRequest;
use App\Services\MaterialHtmlSanitizer;
use App\Services\ModuleGovernanceService;
use App\Services\VideoUrlParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ModuleReviewController extends Controller
{
    // PS-27: oldest submissions first, with a workday-based SLA warning.
    public function index(ModuleGovernanceService $governance): View
    {
        $submissions = Module::query()
            ->where('status', 'submitted')
            ->with(['creator', 'revisionOf'])
            ->orderBy('submitted_at')
            ->paginate(10, ['*'], 'submissions');
        $submissions->getCollection()->each(function (Module $module) use ($governance): void {
            $module->setAttribute(
                'near_sla',
                $module->submitted_at !== null && $governance->isNearSla($module->submitted_at, now()),
            );
            $module->setAttribute(
                'past_sla',
                $module->submitted_at !== null && $governance->isPastSla($module->submitted_at, now()),
            );
        });

        return view('superadmin.modules.index', [
            'submissions' => $submissions,
            'allModules' => Module::query()
                ->with(['creator'])
                ->withCount('units')
                ->orderByDesc('id')
                ->paginate(10, ['*'], 'modules'),
            'extensions' => ModuleExtensionRequest::query()
                ->where('status', 'pending')
                ->with(['module.creator', 'requester'])
                ->orderBy('created_at')
                ->paginate(10, ['*'], 'extensions'),
        ]);
    }

    // PS-23–PS-26: reviewer preview is read-only and never loads learner attempts or scores.
    public function show(
        Module $module,
        MaterialHtmlSanitizer $sanitizer,
        VideoUrlParser $videoUrlParser,
    ): View
    {
        $module->load([
            'creator',
            'units.quizzes',
            'testQuestions',
            'reviews.actor',
        ]);
        $module->units->each(fn ($unit) => $unit->setAttribute(
            'preview_reading_content',
            preg_replace_callback(
                '#/admin/units/\d+/material/images/([A-Za-z0-9.-]+)#',
                fn (array $matches): string => route(
                    'superadmin.modules.units.material-images.show',
                    [$module, $unit, $matches[1]],
                    false,
                ),
                $sanitizer->sanitize($unit->reading_content, $unit),
            ),
        ));
        $module->units->each(fn ($unit) => $unit->setAttribute(
            'youtube_embed_url',
            $videoUrlParser->youtubeEmbedUrl($unit->video_url),
        ));

        return view('superadmin.modules.show', [
            'module' => $module,
        ]);
    }

    // PS-23–PS-29: approve after the manual policy checks and atomically audit the decision.
    public function approve(
        ApproveModuleRequest $request,
        Module $module,
        ModuleGovernanceService $governance,
    ): RedirectResponse {
        $source = null;
        DB::transaction(function () use ($request, $module, $governance, &$source): void {
            $locked = Module::query()->lockForUpdate()->findOrFail($module->id);
            abort_unless($locked->status === 'submitted', 403);
            abort_unless($locked->active_from !== null && $locked->active_from->greaterThanOrEqualTo(today()), 422);

            $from = $locked->status;
            $to = $locked->active_from->isFuture() ? 'approved' : 'published';
            $locked->update([
                'status' => $to,
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

            $governance->record(
                $locked,
                'approved',
                $request->user(),
                $from,
                $to,
                null,
                ['policy_checks' => $request->validated('policy_checks')],
            );

            if ($locked->revision_of_id !== null) {
                $source = Module::query()->lockForUpdate()->findOrFail($locked->revision_of_id);
                if ($source->status === 'published') {
                    $sourceFrom = $source->status;
                    $source->update(['status' => 'archived']);
                    $governance->record(
                        $source,
                        'revision_replaced',
                        $request->user(),
                        $sourceFrom,
                        'archived',
                        'Versi baru disetujui.',
                        ['replacement_module_id' => $locked->id],
                    );
                }
            }
        });

        $governance->notifyAdmin($module, 'module_approved', "Modul '{$module->title}' disetujui.");
        if ($source !== null) {
            $governance->notifyAdmin(
                $source,
                'module_archived',
                "Versi lama modul '{$source->title}' diarsipkan setelah versi baru disetujui.",
            );
        }

        return redirect()->route('superadmin.modules.show', $module)
            ->with('status', 'Modul berhasil disetujui.');
    }

    // PS-28: send the module back with a mandatory revision note.
    public function requestRevision(
        RequestModuleRevisionRequest $request,
        Module $module,
        ModuleGovernanceService $governance,
    ): RedirectResponse {
        DB::transaction(function () use ($request, $module, $governance): void {
            $locked = Module::query()->lockForUpdate()->findOrFail($module->id);
            abort_unless($locked->status === 'submitted', 403);
            $from = $locked->status;
            $locked->update(['status' => 'revision_required']);
            $governance->record(
                $locked,
                'revision_requested',
                $request->user(),
                $from,
                'revision_required',
                $request->validated('notes'),
            );
        });

        $governance->notifyAdmin(
            $module,
            'revision_required',
            "Modul '{$module->title}' perlu direvisi: ".$request->validated('notes'),
        );

        return redirect()->route('superadmin.modules.show', $module)
            ->with('status', 'Catatan revisi berhasil dikirim kepada Admin pemilik.');
    }

    // PS-23, PS-29: takedown archives immediately and records the required reason.
    public function takedown(
        TakedownModuleRequest $request,
        Module $module,
        ModuleGovernanceService $governance,
    ): RedirectResponse {
        DB::transaction(function () use ($request, $module, $governance): void {
            $locked = Module::query()->lockForUpdate()->findOrFail($module->id);
            abort_unless($locked->status === 'published', 403);
            $from = $locked->status;
            $locked->update(['status' => 'archived']);
            $governance->record(
                $locked,
                'takedown',
                $request->user(),
                $from,
                'archived',
                $request->validated('reason'),
            );
        });

        $governance->notifyAdmin(
            $module,
            'module_takedown',
            "Modul '{$module->title}' diturunkan: ".$request->validated('reason'),
        );

        return redirect()->route('superadmin.modules.show', $module)
            ->with('status', 'Modul berhasil diturunkan dan langsung tidak lagi tersedia.');
    }

    // PS-22, PS-29: extension decisions are bound to a single pending request.
    public function decideExtension(
        DecideModuleExtensionRequest $request,
        ModuleExtensionRequest $extension,
        ModuleGovernanceService $governance,
    ): RedirectResponse {
        $module = $extension->module;
        DB::transaction(function () use ($request, $extension, $governance, $module): void {
            $locked = ModuleExtensionRequest::query()->lockForUpdate()->findOrFail($extension->id);
            abort_unless($locked->status === 'pending', 409);
            $lockedModule = Module::query()->lockForUpdate()->findOrFail($locked->module_id);
            abort_unless($lockedModule->status === 'published', 409);

            $approved = $request->validated('decision') === 'approve';
            if ($approved) {
                abort_unless(
                    $lockedModule->active_from !== null
                        && $locked->requested_active_until->lessThanOrEqualTo(
                            $lockedModule->active_from->copy()->addMonthsNoOverflow(12),
                        ),
                    422,
                );
                $lockedModule->update(['active_until' => $locked->requested_active_until]);
            }

            $locked->update([
                'status' => $approved ? 'approved' : 'rejected',
                'decided_by' => $request->user()->id,
                'decided_at' => now(),
                'decision_notes' => $request->validated('notes'),
            ]);
            $governance->record(
                $lockedModule,
                $approved ? 'extension_approved' : 'extension_rejected',
                $request->user(),
                'published',
                'published',
                $request->validated('notes'),
                [
                    'extension_request_id' => $locked->id,
                    'active_until' => $approved
                        ? $locked->requested_active_until->toDateString()
                        : $locked->previous_active_until->toDateString(),
                ],
            );
        });

        $approved = $request->validated('decision') === 'approve';
        $governance->notifyAdmin(
            $module,
            $approved ? 'extension_approved' : 'extension_rejected',
            $approved
                ? "Perpanjangan modul '{$module->title}' disetujui."
                : "Perpanjangan modul '{$module->title}' ditolak: ".$request->validated('notes'),
        );

        return redirect()->route('superadmin.modules.index')
            ->with('status', $approved ? 'Perpanjangan disetujui.' : 'Perpanjangan ditolak.');
    }
}
