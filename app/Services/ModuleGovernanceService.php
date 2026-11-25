<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Module;
use App\Models\ModuleReview;
use App\Models\User;
use App\Notifications\ModuleGovernanceNotification;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ModuleGovernanceService
{
    // PS-27, PS-29: review deadlines use weekdays; every status action is auditable.
    public function record(
        Module $module,
        string $action,
        ?User $actor,
        ?string $statusFrom = null,
        ?string $statusTo = null,
        ?string $notes = null,
        array $metadata = [],
    ): ModuleReview {
        return $module->reviews()->create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'status_from' => $statusFrom,
            'status_to' => $statusTo,
            'notes' => $notes,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }

    public function notifyAdmin(Module $module, string $event, string $message): void
    {
        $owner = $module->creator;

        if ($owner !== null && ! $owner->trashed()) {
            $owner->notify(new ModuleGovernanceNotification($event, $message, $module->id));
        }
    }

    public function notifySuperadmins(Module $module, string $event, string $message): void
    {
        User::query()->where('role', Role::Superadmin->value)
            ->get()
            ->each(fn (User $user) => $user->notify(
                new ModuleGovernanceNotification($event, $message, $module->id),
            ));
    }

    public function isNearSla(CarbonInterface $submittedAt, CarbonInterface $now): bool
    {
        return $this->businessDaysElapsed($submittedAt, $now) >= 2;
    }

    public function isPastSla(CarbonInterface $submittedAt, CarbonInterface $now): bool
    {
        return $this->businessDaysElapsed($submittedAt, $now) > 3;
    }

    public function businessDaysElapsed(CarbonInterface $from, CarbonInterface $to): int
    {
        $day = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();
        $elapsed = 0;

        while ($day->lt($end)) {
            $day->addDay();

            if ($day->isWeekday()) {
                $elapsed++;
            }
        }

        return $elapsed;
    }

    public function failedChecks(Module $module, ModuleQualityGate $qualityGate): Collection
    {
        return collect($qualityGate->inspect($module))
            ->filter(fn (array $check): bool => ! $check['passed']);
    }
}
