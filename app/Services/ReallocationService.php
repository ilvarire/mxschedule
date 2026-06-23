<?php

namespace App\Services;

use App\Enums\SeatStatus;
use App\Enums\SystemStatus;
use App\Models\ExamAllocation;
use App\Models\ExamSession;
use App\Models\System;
use App\Notifications\ReallocationAttentionRequiredNotification;
use App\Notifications\StudentReallocatedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReallocationService
{
    public function __construct(
        protected ExamPassService $passService,
        protected AdminNotificationService $adminNotifier,
    ) {}

    /**
     * Reassign students from a faulty or deactivated system.
     *
     * @return Collection<int, ExamAllocation> New allocations
     */
    public function reassignFromSystem(System $faultySystem): Collection
    {
        $affectedAllocations = ExamAllocation::where('system_id', $faultySystem->id)
            ->where('seat_status', SeatStatus::Allocated)
            ->whereHas('examSession', function ($q) {
                $q->where('end_time', '>', now());
            })
            ->with(['examSession', 'studentProfile'])
            ->get();

        if ($affectedAllocations->isEmpty()) {
            return collect();
        }

        $reassigned = Cache::lock("reallocate_system_{$faultySystem->id}", 10)->get(function () use ($affectedAllocations, $faultySystem) {
            $result = DB::transaction(function () use ($affectedAllocations, $faultySystem) {
                $newAllocations = collect();
                $needsAttention = collect();

                foreach ($affectedAllocations as $old) {
                    $replacement = $this->findReplacementSystem(
                        $old->examSession,
                        $faultySystem->hall_id,
                        $old->exam_session_id,
                    );

                    if (! $replacement) {
                        $needsAttention->push($old);
                        continue;
                    }

                    $old->update(['seat_status' => SeatStatus::Reassigned]);

                    $new = ExamAllocation::create([
                        'exam_session_id' => $old->exam_session_id,
                        'student_profile_id' => $old->student_profile_id,
                        'system_id' => $replacement->id,
                        'hall_id' => $replacement->hall_id,
                        'seat_status' => SeatStatus::Allocated,
                        'reassigned_from_id' => $old->id,
                    ]);

                    $old->examPass?->delete();
                    $this->passService->generateForAllocation($new);

                    $newAllocations->push($new);
                }

                return compact('newAllocations', 'needsAttention');
            });

            $result['newAllocations']->each(function (ExamAllocation $newAllocation) {
                $this->notifyStudentReallocated(
                    $newAllocation,
                    'Your exam seat has been updated because your previous system became unavailable.',
                );
            });

            $result['needsAttention']->each(function (ExamAllocation $allocation) use ($faultySystem) {
                $this->adminNotifier->notify(new ReallocationAttentionRequiredNotification($allocation, $faultySystem));
            });

            return $result['newAllocations'];
        });

        return $reassigned ?? collect();
    }

    /**
     * Manually reassign a specific student to a new system.
     */
    public function reassignStudent(ExamAllocation $oldAllocation, System $newSystem): ExamAllocation
    {
        $reassigned = Cache::lock("reallocate_student_{$oldAllocation->id}", 10)->get(function () use ($oldAllocation, $newSystem) {
            $new = DB::transaction(function () use ($oldAllocation, $newSystem) {
                $oldAllocation = ExamAllocation::query()->lockForUpdate()->findOrFail($oldAllocation->id);
                $newSystem = System::query()->lockForUpdate()->findOrFail($newSystem->id);

                if ($oldAllocation->seat_status === SeatStatus::Reassigned) {
                    throw ValidationException::withMessages(['allocation_id' => 'This allocation has already been reassigned.']);
                }

                if (! $newSystem->isActive() || ! $newSystem->hall?->is_active) {
                    throw ValidationException::withMessages(['new_system_id' => 'Select an active system in an active hall.']);
                }

                $systemIsOccupied = ExamAllocation::where('exam_session_id', $oldAllocation->exam_session_id)
                    ->where('system_id', $newSystem->id)
                    ->where('seat_status', '!=', SeatStatus::Reassigned)
                    ->exists();

                if ($systemIsOccupied) {
                    throw ValidationException::withMessages(['new_system_id' => 'That system is already allocated in this session.']);
                }

                $oldAllocation->update(['seat_status' => SeatStatus::Reassigned]);

                $new = ExamAllocation::create([
                    'exam_session_id' => $oldAllocation->exam_session_id,
                    'student_profile_id' => $oldAllocation->student_profile_id,
                    'system_id' => $newSystem->id,
                    'hall_id' => $newSystem->hall_id,
                    'seat_status' => SeatStatus::Allocated,
                    'reassigned_from_id' => $oldAllocation->id,
                ]);

                $oldAllocation->examPass?->delete();
                $this->passService->generateForAllocation($new);

                return $new;
            });

            $this->notifyStudentReallocated(
                $new,
                'An exam officer has updated your exam seat assignment.',
            );

            return $new;
        });

        if (! $reassigned) {
            throw ValidationException::withMessages(['allocation_id' => 'This allocation is currently being reassigned. Please try again.']);
        }

        return $reassigned;
    }

    /**
     * Find a replacement system, preferring the same hall.
     */
    protected function findReplacementSystem(
        ExamSession $session,
        int $preferredHallId,
        int $sessionId,
    ): ?System {
        $allocatedSystemIds = ExamAllocation::where('exam_session_id', $sessionId)
            ->where('seat_status', '!=', SeatStatus::Reassigned)
            ->pluck('system_id');

        $replacement = System::where('status', SystemStatus::Active)
            ->where('hall_id', $preferredHallId)
            ->whereNotIn('id', $allocatedSystemIds)
            ->whereHas('hall', fn ($q) => $q->where('is_active', true))
            ->first();

        if ($replacement) {
            return $replacement;
        }

        return System::where('status', SystemStatus::Active)
            ->whereNotIn('id', $allocatedSystemIds)
            ->whereHas('hall', fn ($q) => $q->where('is_active', true))
            ->first();
    }

    protected function notifyStudentReallocated(ExamAllocation $newAllocation, string $reason): void
    {
        $newAllocation->loadMissing(['studentProfile.user', 'reassignedFrom.hall', 'reassignedFrom.system']);

        $user = $newAllocation->studentProfile->user;
        $oldAllocation = $newAllocation->reassignedFrom;

        if (! $user || ! $oldAllocation) {
            return;
        }

        $user->notify(new StudentReallocatedNotification($newAllocation, $oldAllocation, $reason));
    }
}
