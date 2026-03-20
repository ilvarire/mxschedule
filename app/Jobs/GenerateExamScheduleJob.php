<?php

namespace App\Jobs;

use App\Models\Exam;
use App\Services\SchedulingEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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

        // Dispatch follow-up jobs
        GenerateExamPassPdfJob::dispatch($this->exam);
        SendScheduleNotificationsJob::dispatch($this->exam);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Schedule generation failed for Exam #{$this->exam->id}: {$exception->getMessage()}");
    }
}
