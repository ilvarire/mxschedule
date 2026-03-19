<?php

namespace App\Jobs;

use App\Models\Exam;
use App\Models\ExamAllocation;
use App\Notifications\ScheduleReleasedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendScheduleNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public Exam $exam,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        Log::info("Sending schedule notifications for Exam #{$this->exam->id}");

        $allocations = ExamAllocation::whereHas('examSession', fn ($q) => $q->where('exam_id', $this->exam->id))
            ->with(['studentProfile.user', 'examSession'])
            ->get();

        foreach ($allocations as $allocation) {
            $user = $allocation->studentProfile->user;
            $user->notify(new ScheduleReleasedNotification($this->exam, $allocation));
        }

        Log::info("Sent {$allocations->count()} notifications for Exam #{$this->exam->id}");
    }
}
