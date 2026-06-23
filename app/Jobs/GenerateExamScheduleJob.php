<?php

namespace App\Jobs;

use App\Models\Exam;
use App\Notifications\ExamJobFailedNotification;
use App\Services\AdminNotificationService;
use App\Services\SchedulingEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// SendScheduleNotificationsJob is dispatched by SchedulingEngine::executeScheduling().
// Do not import or re-dispatch it here to avoid duplicate student notifications.

class GenerateExamScheduleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 600;
    public ?int $userId = null;

    public function __construct(
        public Exam $exam,
        ?int $userId = null,
    ) {
        $this->userId = $userId ?? auth()->id() ?? 1;
        $this->onQueue('scheduling');
    }

    public function handle(SchedulingEngine $engine): void
    {
        Log::info("Generating schedule for Exam #{$this->exam->id} requested by User #{$this->userId}");

        $engine->generateSchedule($this->exam, $this->userId);

        Log::info("Schedule generated for Exam #{$this->exam->id}");

        // Dispatch PDF generation.
        // Note: SendScheduleNotificationsJob is already dispatched inside SchedulingEngine::executeScheduling()
        // at step 9. Do NOT dispatch it again here to avoid duplicate student notifications.
        GenerateExamPassPdfJob::dispatch($this->exam);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Schedule generation failed for Exam #{$this->exam->id}: {$exception->getMessage()}");
        $this->exam->update(['status' => \App\Enums\ExamStatus::Draft]);

        app(AdminNotificationService::class)->notify(new ExamJobFailedNotification(
            $this->exam->fresh('course'),
            'Schedule generation',
            $exception->getMessage(),
        ));
    }
}
