<?php

namespace App\Services;

use App\Enums\ExamStatus;
use App\Enums\SeatStatus;
use App\Enums\SystemStatus;
use App\Jobs\SendScheduleNotificationsJob;
use App\Models\Exam;
use App\Models\ExamAllocation;
use App\Models\ExamSession;
use App\Models\StudentProfile;
use App\Models\System;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SchedulingEngine
{
    /**
     * Generate the full schedule for an exam.
     *
     * @throws \RuntimeException
     */
    public function generateSchedule(Exam $exam, ?int $userId = null): void
    {
        // ── STEP 1: Validate state ──────────────────────
        if (! $exam->canBeScheduled()) {
            throw new \RuntimeException("Exam #{$exam->id} cannot be scheduled in its current state ({$exam->status->value}).");
        }

        // Mark as scheduling (optimistic lock)
        $exam->update(['status' => ExamStatus::Scheduling]);

        try {
            DB::transaction(function () use ($exam, $userId) {
                $this->executeScheduling($exam, $userId);
            });
        } catch (\Throwable $e) {
            // Revert status on failure
            $exam->update(['status' => ExamStatus::Draft]);
            throw $e;
        }
    }

    /**
     * Core scheduling logic inside a transaction.
     */
    protected function executeScheduling(Exam $exam, ?int $userId = null): void
    {
        // ── STEP 2: Gather inputs ───────────────────────
        $students = $this->getRegisteredStudents($exam);
        $activeSystems = System::available()->lockForUpdate()->skipLocked()->get();

        $totalStudents = $students->count();
        $totalSystems = $activeSystems->count();

        if ($totalSystems === 0) {
            throw new \RuntimeException('No active systems available for scheduling.');
        }

        if ($totalStudents === 0) {
            throw new \RuntimeException('No students registered for this exam course.');
        }

        // ── STEP 3: Calculate sessions ──────────────────
        $sessionsNeeded = (int) ceil($totalStudents / $totalSystems);

        // ── STEP 4: Build time slots ────────────────────
        $slots = $this->buildTimeSlots($exam, $sessionsNeeded);

        // ── STEP 5: Shuffle students (CSPRNG fairness) ──
        $shuffledStudents = $this->cryptographicShuffle($students);

        // ── STEP 6: Batch students into sessions ────────
        $batches = $shuffledStudents->chunk($totalSystems);

        // ── STEP 7: Create sessions and allocations ─────
        // Clear any previous sessions (for re-scheduling)
        $exam->sessions()->delete();

        $sessionNumber = 0;
        foreach ($batches as $batch) {
            $sessionNumber++;
            $slot = $slots[$sessionNumber - 1];

            $session = ExamSession::create([
                'exam_id' => $exam->id,
                'session_number' => $sessionNumber,
                'start_time' => $slot['start'],
                'end_time' => $slot['end'],
                'max_capacity' => $totalSystems,
                'allocated_count' => $batch->count(),
                'status' => 'pending',
            ]);

            // Shuffle systems for this batch (anti-cheating)
            $shuffledSystems = $this->cryptographicShuffle($activeSystems);

            // Build allocation records
            $allocations = [];
            $now = now();
            $batchValues = $batch->values();

            for ($i = 0; $i < $batchValues->count(); $i++) {
                $student = $batchValues[$i];
                $system = $shuffledSystems[$i];

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

            // Bulk insert in chunks for performance
            foreach (array_chunk($allocations, 500) as $chunk) {
                ExamAllocation::insert($chunk);
            }
        }

        // ── STEP 8: Update exam status ──────────────────
        $exam->update([
            'status' => ExamStatus::Scheduled,
            'scheduled_at' => now(),
            'scheduled_by' => $userId ?? auth()->id() ?? 1,
            'total_registered_students' => $totalStudents,
        ]);

        // ── STEP 9: Dispatch student notifications ──────
        SendScheduleNotificationsJob::dispatch($exam)->onQueue('notifications');
    }

    /**
     * Get registered students for the exam's course.
     */
    protected function getRegisteredStudents(Exam $exam): Collection
    {
        return StudentProfile::whereHas('courses', function ($query) use ($exam) {
            $query->where('course_id', $exam->course_id)
                ->where('academic_session', $exam->academic_session)
                ->where('semester', $exam->semester->value);
        })->get();
    }

    /**
     * Build time slots for all sessions.
     *
     * @return array<int, array{start: Carbon, end: Carbon}>
     */
    protected function buildTimeSlots(Exam $exam, int $sessionsNeeded): array
    {
        $slots = [];
        $currentStart = Carbon::parse($exam->exam_date)->startOfDay()->addHours(Carbon::parse($exam->start_time)->hour)->addMinutes(Carbon::parse($exam->start_time)->minute);

        $operatingHourEnd = 18; // 6 PM
        $operatingHourStart = 8; // 8 AM

        for ($i = 0; $i < $sessionsNeeded; $i++) {
            $end = $currentStart->copy()->addMinutes($exam->duration_minutes);

            // Check if session exceeds operating hours
            if ($end->hour >= $operatingHourEnd && !($end->hour === $operatingHourEnd && $end->minute === 0)) {
                // Move to next day at starting hour
                $currentStart->addDay()->startOfDay()->addHours($operatingHourStart);
                $end = $currentStart->copy()->addMinutes($exam->duration_minutes);
            }

            $slots[] = [
                'start' => $currentStart->copy(),
                'end' => $end,
            ];
            $currentStart = $end->copy()->addMinutes($exam->buffer_minutes);
        }

        return $slots;
    }

    /**
     * Fisher-Yates shuffle using cryptographically secure random_int().
     */
    protected function cryptographicShuffle(Collection $items): Collection
    {
        $array = $items->values()->all();
        $count = count($array);

        for ($i = $count - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$array[$i], $array[$j]] = [$array[$j], $array[$i]];
        }

        return collect($array);
    }
}
