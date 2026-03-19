<?php

namespace App\Services;

use App\Enums\SeatStatus;
use App\Enums\SystemStatus;
use App\Models\ExamAllocation;
use App\Models\ExamSession;
use App\Models\System;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReallocationService
{
    protected ExamPassService $passService;

    public function __construct(ExamPassService $passService)
    {
        $this->passService = $passService;
    }

    /**
     * Reassign students from a faulty or deactivated system.
     *
     * @return Collection<int, ExamAllocation> New allocations
     */
    public function reassignFromSystem(System $faultySystem): Collection
    {
        // Find active allocations on this system (future sessions only)
        $affectedAllocations = ExamAllocation::where('system_id', $faultySystem->id)
            ->where('seat_status', SeatStatus::Allocated)
            ->whereHas('examSession', function ($q) {
                $q->where('start_time', '>', now());
            })
            ->with(['examSession', 'studentProfile'])
            ->get();

        if ($affectedAllocations->isEmpty()) {
            return collect();
        }

        return DB::transaction(function () use ($affectedAllocations, $faultySystem) {
            $newAllocations = collect();

            foreach ($affectedAllocations as $old) {
                // Find a replacement system
                $replacement = $this->findReplacementSystem(
                    $old->examSession,
                    $faultySystem->hall_id,
                    $old->exam_session_id,
                );

                if (! $replacement) {
                    // No replacement available — flag for manual intervention
                    continue;
                }

                // Create new allocation
                $new = ExamAllocation::create([
                    'exam_session_id' => $old->exam_session_id,
                    'student_profile_id' => $old->student_profile_id,
                    'system_id' => $replacement->id,
                    'hall_id' => $replacement->hall_id,
                    'seat_status' => SeatStatus::Allocated,
                    'reassigned_from_id' => $old->id,
                ]);

                // Mark old allocation as reassigned
                $old->update(['seat_status' => SeatStatus::Reassigned]);

                // Delete old pass and generate new one
                $old->examPass?->delete();
                $this->passService->generateForAllocation($new);

                $newAllocations->push($new);
            }

            return $newAllocations;
        });
    }

    /**
     * Manually reassign a specific student to a new system.
     */
    public function reassignStudent(ExamAllocation $oldAllocation, System $newSystem): ExamAllocation
    {
        return DB::transaction(function () use ($oldAllocation, $newSystem) {
            // Create new allocation
            $new = ExamAllocation::create([
                'exam_session_id' => $oldAllocation->exam_session_id,
                'student_profile_id' => $oldAllocation->student_profile_id,
                'system_id' => $newSystem->id,
                'hall_id' => $newSystem->hall_id,
                'seat_status' => SeatStatus::Allocated,
                'reassigned_from_id' => $oldAllocation->id,
            ]);

            // Mark old allocation
            $oldAllocation->update(['seat_status' => SeatStatus::Reassigned]);

            // Regenerate pass
            $oldAllocation->examPass?->delete();
            $this->passService->generateForAllocation($new);

            return $new;
        });
    }

    /**
     * Find a replacement system, preferring the same hall.
     */
    protected function findReplacementSystem(
        ExamSession $session,
        int $preferredHallId,
        int $sessionId,
    ): ?System {
        // Get systems already allocated in this session
        $allocatedSystemIds = ExamAllocation::where('exam_session_id', $sessionId)
            ->where('seat_status', '!=', SeatStatus::Reassigned)
            ->pluck('system_id');

        // First: try same hall
        $replacement = System::where('status', SystemStatus::Active)
            ->where('hall_id', $preferredHallId)
            ->whereNotIn('id', $allocatedSystemIds)
            ->whereHas('hall', fn ($q) => $q->where('is_active', true))
            ->first();

        if ($replacement) {
            return $replacement;
        }

        // Fallback: any hall
        return System::where('status', SystemStatus::Active)
            ->whereNotIn('id', $allocatedSystemIds)
            ->whereHas('hall', fn ($q) => $q->where('is_active', true))
            ->first();
    }
}
