<?php

namespace App\Console\Commands;

use App\Models\Module;
use App\Models\ModuleExtensionRequest;
use App\Services\ModuleGovernanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessModuleGovernanceSchedule extends Command
{
    protected $signature = 'modules:process-governance-schedule';

    protected $description = 'Publikasikan dan arsipkan modul sesuai periode aktif serta kirim pengingat.';

    // PS-21, PS-29: scheduled publication, archival, and reminders run once each day.
    public function handle(ModuleGovernanceService $governance): int
    {
        Module::query()->where('status', 'approved')
            ->whereDate('active_from', '<=', today())
            ->whereDate('active_until', '>=', today())
            ->orderBy('id')
            ->get()
            ->each(function (Module $module) use ($governance): void {
                DB::transaction(function () use ($module, $governance): void {
                    $locked = Module::query()->lockForUpdate()->findOrFail($module->id);
                    if ($locked->status !== 'approved' || $locked->active_from?->isFuture()) {
                        return;
                    }

                    $locked->update(['status' => 'published']);
                    $governance->record($locked, 'automatically_published', null, 'approved', 'published');
                });
                $governance->notifyAdmin($module, 'module_published', "Modul '{$module->title}' mulai tayang.");
            });

        Module::query()->whereIn('status', ['published', 'approved'])
            ->whereDate('active_until', '<', today())
            ->orderBy('id')
            ->get()
            ->each(function (Module $module) use ($governance): void {
                $result = DB::transaction(function () use ($module, $governance): array {
                    $locked = Module::query()->lockForUpdate()->findOrFail($module->id);
                    if (! in_array($locked->status, ['published', 'approved'], true)
                        || $locked->active_until === null
                        || ! $locked->active_until->isBefore(today())) {
                        return ['changed' => false, 'extension_rejected' => false];
                    }

                    $from = $locked->status;
                    $locked->update(['status' => 'archived']);
                    $governance->record($locked, 'automatically_archived', null, $from, 'archived');
                    $pendingExtensions = ModuleExtensionRequest::query()
                        ->where('module_id', $locked->id)
                        ->where('status', 'pending')
                        ->lockForUpdate()
                        ->get();

                    foreach ($pendingExtensions as $extension) {
                        $extension->update([
                            'status' => 'rejected',
                            'decided_at' => now(),
                            'decision_notes' => 'Periode aktif berakhir sebelum pengajuan diputuskan.',
                        ]);
                        $governance->record(
                            $locked,
                            'extension_rejected',
                            null,
                            'published',
                            'published',
                            $extension->decision_notes,
                            ['extension_request_id' => $extension->id],
                        );
                    }

                    return ['changed' => true, 'extension_rejected' => $pendingExtensions->isNotEmpty()];
                });

                if ($result['changed']) {
                    $message = "Modul '{$module->title}' diarsipkan karena periode aktif berakhir.";
                    $governance->notifyAdmin($module, 'module_archived', $message);
                    $governance->notifySuperadmins($module, 'module_archived', $message);
                    if ($result['extension_rejected']) {
                        $governance->notifyAdmin(
                            $module,
                            'extension_rejected',
                            "Pengajuan perpanjangan modul '{$module->title}' tidak diputuskan sebelum periode aktif berakhir.",
                        );
                    }
                }
            });

        foreach ([14, 3] as $daysBefore) {
            Module::query()->where('status', 'published')
                ->whereDate('active_until', today()->addDays($daysBefore))
                ->orderBy('id')
                ->get()
                ->each(function (Module $module) use ($daysBefore, $governance): void {
                    $reminderId = DB::table('module_reminders')->insertOrIgnore([
                        'module_id' => $module->id,
                        'days_before' => $daysBefore,
                        'active_until' => $module->active_until->toDateString(),
                        'sent_at' => now(),
                    ]);

                    if ($reminderId === 1) {
                        $governance->notifyAdmin(
                            $module,
                            'module_expiry_reminder',
                            "Periode aktif modul '{$module->title}' berakhir dalam {$daysBefore} hari.",
                        );
                    }
                });
        }

        return self::SUCCESS;
    }
}
