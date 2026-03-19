<?php

namespace App\Services;

use App\Enums\SystemStatus;
use App\Models\Hall;
use App\Models\System;
use App\Models\SystemStatusLog;
use Illuminate\Support\Collection;

class SystemManagementService
{
    /**
     * Bulk create systems in a hall.
     *
     * @return Collection<int, System>
     */
    public function bulkCreateSystems(Hall $hall, int $count, ?string $prefix = null): Collection
    {
        $prefix = $prefix ?? $hall->code;
        $existingCount = $hall->systems()->count();
        $systems = collect();

        for ($i = 1; $i <= $count; $i++) {
            $number = $existingCount + $i;
            $systemCode = $prefix . str_pad($number, 2, '0', STR_PAD_LEFT);

            $system = System::create([
                'hall_id' => $hall->id,
                'system_code' => $systemCode,
                'status' => SystemStatus::Active,
            ]);

            $systems->push($system);
        }

        return $systems;
    }

    /**
     * Update system status and log the change.
     */
    public function updateStatus(
        System $system,
        SystemStatus $newStatus,
        int $changedBy,
        ?string $reason = null,
    ): System {
        $previousStatus = $system->status;

        if ($previousStatus === $newStatus) {
            return $system;
        }

        $system->update([
            'status' => $newStatus,
            'status_note' => $reason,
        ]);

        SystemStatusLog::create([
            'system_id' => $system->id,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'changed_by' => $changedBy,
            'reason' => $reason,
        ]);

        return $system;
    }

    /**
     * Bulk update status for all systems in a hall.
     */
    public function bulkUpdateHallSystems(
        Hall $hall,
        SystemStatus $newStatus,
        int $changedBy,
        ?string $reason = null,
    ): int {
        $systems = $hall->systems()
            ->where('status', '!=', $newStatus)
            ->get();

        foreach ($systems as $system) {
            $this->updateStatus($system, $newStatus, $changedBy, $reason);
        }

        return $systems->count();
    }
}
