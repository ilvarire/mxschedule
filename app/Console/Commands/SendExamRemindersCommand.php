<?php

namespace App\Console\Commands;

use App\Enums\ExamStatus;
use App\Enums\SeatStatus;
use App\Enums\SessionStatus;
use App\Models\ExamAllocation;
use App\Models\Setting;
use App\Notifications\ExamReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SendExamRemindersCommand extends Command
{
    protected $signature = 'exam:send-reminders {--dry-run : Show eligible reminders without sending them}';

    protected $description = 'Send configured reminder emails to students before exam sessions start.';

    public function handle(): int
    {
        $reminderHours = $this->reminderHours();
        $sent = 0;

        foreach ($reminderHours as $index => $hoursBefore) {
            $nextLowerThreshold = $reminderHours[$index + 1] ?? 0;
            $sent += $this->sendReminderBatch($hoursBefore, $nextLowerThreshold);
        }

        $this->info("Exam reminders processed. Sent {$sent} reminder(s).");

        return self::SUCCESS;
    }

    /**
     * @return array<int, int>
     */
    protected function reminderHours(): array
    {
        $configured = (string) Setting::getValue('exam_reminder_hours', '24,1');

        $hours = collect(explode(',', $configured))
            ->map(fn (string $value) => (int) trim($value))
            ->filter(fn (int $value) => $value > 0)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        return $hours ?: [24, 1];
    }

    protected function sendReminderBatch(int $hoursBefore, int $nextLowerThreshold): int
    {
        $now = now();
        $windowStart = $now->copy()->addHours($nextLowerThreshold);
        $windowEnd = $now->copy()->addHours($hoursBefore);
        $sent = 0;

        ExamAllocation::query()
            ->where('seat_status', SeatStatus::Allocated->value)
            ->whereDoesntHave('examPass', fn ($query) => $query->where('is_used', true))
            ->whereHas('examSession', function ($query) use ($windowStart, $windowEnd) {
                $query->where('status', SessionStatus::Pending->value)
                    ->where('start_time', '>', $windowStart)
                    ->where('start_time', '<=', $windowEnd)
                    ->whereHas('exam', fn ($examQuery) => $examQuery->where('status', ExamStatus::Scheduled->value));
            })
            ->with(['examSession.exam.course', 'studentProfile.user', 'hall', 'system'])
            ->chunkById(100, function ($allocations) use ($hoursBefore, $now, &$sent) {
                foreach ($allocations as $allocation) {
                    if (! $allocation->studentProfile?->user) {
                        continue;
                    }

                    if ($this->option('dry-run')) {
                        if ($this->reminderAlreadySent($allocation->id, $hoursBefore)) {
                            continue;
                        }

                        $this->line("Would send {$hoursBefore}h reminder for allocation #{$allocation->id}.");
                        $sent++;
                        continue;
                    }

                    if (! $this->claimReminder($allocation->id, $hoursBefore, $now)) {
                        continue;
                    }

                    $allocation->studentProfile->user->notify(new ExamReminderNotification(
                        $allocation->examSession->exam,
                        $allocation,
                        $hoursBefore,
                    ));

                    $sent++;
                }
            });

        return $sent;
    }

    protected function claimReminder(int $allocationId, int $hoursBefore, Carbon $sentAt): bool
    {
        $inserted = DB::table('exam_reminder_logs')->insertOrIgnore([
            'exam_allocation_id' => $allocationId,
            'hours_before' => $hoursBefore,
            'sent_at' => $sentAt,
            'created_at' => $sentAt,
            'updated_at' => $sentAt,
        ]);

        return $inserted === 1;
    }

    protected function reminderAlreadySent(int $allocationId, int $hoursBefore): bool
    {
        return DB::table('exam_reminder_logs')
            ->where('exam_allocation_id', $allocationId)
            ->where('hours_before', $hoursBefore)
            ->exists();
    }
}
