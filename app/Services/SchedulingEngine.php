<?php

namespace App\Services;

use App\Enums\ExamStatus;
use App\Enums\SeatStatus;
use App\Jobs\SendScheduleNotificationsJob;
use App\Models\Exam;
use App\Models\ExamAllocation;
use App\Models\ExamSession;
use App\Models\System;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SchedulingEngine
{
    public function generateSchedule(Exam $exam, ?int $userId = null): void
    {
        if (! $exam->canBeScheduled()) {
            throw new \RuntimeException("Exam #{$exam->id} cannot be scheduled in its current state ({$exam->status->value}).");
        }

        $exam->update(['status' => ExamStatus::Scheduling]);

        try {
            DB::transaction(fn () => $this->executeScheduling($exam, $userId));
        } catch (\Throwable $e) {
            $exam->update(['status' => ExamStatus::Draft]);
            throw $e;
        }
    }

    protected function executeScheduling(Exam $exam, ?int $userId = null): void
    {
        $students = $this->getRegisteredStudents($exam);

        if (System::available()->count() === 0) {
            throw new \RuntimeException('No active systems available for scheduling.');
        }

        if ($students->isEmpty()) {
            throw new \RuntimeException('No students registered for this exam course.');
        }

        $exam->sessions()->delete();

        $remainingStudents = $this->cryptographicShuffle($students)->values();
        $currentStart = $this->firstSlotStart($exam);
        $sessionNumber = 0;
        $emptySlotCount = 0;

        while ($remainingStudents->isNotEmpty()) {
            [$slotStart, $slotEnd] = $this->normalizeSlot($currentStart, $exam->duration_minutes);
            $availableSystems = $this->availableSystemsForSlot($slotStart, $slotEnd);

            if ($availableSystems->isEmpty()) {
                $emptySlotCount++;
                if ($emptySlotCount > 365) {
                    throw new \RuntimeException('Unable to find an available exam slot within the next 365 operating slots.');
                }

                $currentStart = $slotEnd->copy()->addMinutes($exam->buffer_minutes);
                continue;
            }

            $emptySlotCount = 0;
            $batch = $remainingStudents->splice(0, $availableSystems->count());
            $sessionNumber++;

            $session = ExamSession::create([
                'exam_id' => $exam->id,
                'session_number' => $sessionNumber,
                'start_time' => $slotStart,
                'end_time' => $slotEnd,
                'max_capacity' => $availableSystems->count(),
                'allocated_count' => $batch->count(),
                'status' => 'pending',
            ]);

            $shuffledSystems = $this->cryptographicShuffle($availableSystems);
            $allocations = [];
            $now = now();

            foreach ($batch->values() as $index => $student) {
                $system = $shuffledSystems[$index];
                $allocations[] = [
                    'exam_session_id' => $session->id,
                    'student_profile_id' => $student->id,
                    'system_id' => $system->id,
                    'hall_id' => $system->hall_id,
                    'seat_status' => SeatStatus::Allocated->value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($allocations, 500) as $chunk) {
                ExamAllocation::insert($chunk);
            }

            $currentStart = $slotEnd->copy()->addMinutes($exam->buffer_minutes);
        }

        $exam->update([
            'status' => ExamStatus::Scheduled,
            'scheduled_at' => now(),
            'scheduled_by' => $userId ?? auth()->id() ?? 1,
            'total_registered_students' => $students->count(),
        ]);

        SendScheduleNotificationsJob::dispatch($exam)->onQueue('notifications');
    }

    protected function getRegisteredStudents(Exam $exam): Collection
    {
        return app(ExamRegistrationService::class)
            ->registeredStudentsQuery($exam)
            ->get();
    }

    protected function firstSlotStart(Exam $exam): Carbon
    {
        $time = Carbon::parse($exam->start_time);

        return Carbon::parse($exam->exam_date)->startOfDay()
            ->addHours($time->hour)
            ->addMinutes($time->minute);
    }

    /**
     * @return array{Carbon, Carbon}
     */
    protected function normalizeSlot(Carbon $requestedStart, int $durationMinutes): array
    {
        $start = $requestedStart->copy();
        $end = $start->copy()->addMinutes($durationMinutes);

        if ($start->hour < 8 || $end->gt($start->copy()->startOfDay()->addHours(18))) {
            $start = $start->copy()->addDay()->startOfDay()->addHours(8);
            $end = $start->copy()->addMinutes($durationMinutes);
        }

        if ($end->gt($start->copy()->startOfDay()->addHours(18))) {
            throw new \RuntimeException('Exam duration exceeds the configured 8 AM to 6 PM operating window.');
        }

        return [$start, $end];
    }

    protected function availableSystemsForSlot(Carbon $start, Carbon $end): Collection
    {
        return System::available()
            ->whereDoesntHave('examAllocations', function ($query) use ($start, $end) {
                $query->where('seat_status', '!=', SeatStatus::Reassigned)
                    ->whereHas('examSession', function ($sessionQuery) use ($start, $end) {
                        $sessionQuery->where('start_time', '<', $end)
                            ->where('end_time', '>', $start);
                    });
            })
            ->lockForUpdate()
            ->get();
    }

    protected function cryptographicShuffle(Collection $items): Collection
    {
        $array = $items->values()->all();

        for ($i = count($array) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$array[$i], $array[$j]] = [$array[$j], $array[$i]];
        }

        return collect($array);
    }
}
