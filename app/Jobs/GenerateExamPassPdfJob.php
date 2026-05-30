<?php

namespace App\Jobs;

use App\Models\Exam;
use App\Services\ExamPassService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateExamPassPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public Exam $exam,
    ) {
        $this->onQueue('pdf');
    }

    public function handle(ExamPassService $passService): void
    {
        Log::info("Generating passes for Exam #{$this->exam->id}");

        $count = $passService->generateForExam($this->exam->id);
        $this->exam->allocations()->with('examPass')->get()->each(function ($allocation) use ($passService) {
            if ($allocation->examPass && ! $allocation->examPass->pdf_path) {
                $passService->generatePdf($allocation->examPass);
            }
        });

        Log::info("Generated {$count} passes for Exam #{$this->exam->id}");
    }
}
