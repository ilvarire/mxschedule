<?php

namespace App\Console\Commands;

use App\Enums\ExamStatus;
use App\Enums\SeatStatus;
use App\Enums\SessionStatus;
use App\Models\Exam;
use App\Models\ExamAllocation;
use App\Models\ExamSession;
use Illuminate\Console\Command;

class SyncExamLifecycleCommand extends Command
{
    protected $signature = 'exam:sync-lifecycle';

    protected $description = 'Advance exam, session, and attendance states based on the current time.';

    public function handle(): int
    {
        $now = now();

        ExamSession::where('status', SessionStatus::Pending)
            ->where('start_time', '<=', $now)
            ->where('end_time', '>', $now)
            ->update(['status' => SessionStatus::InProgress]);

        $finishedSessionIds = ExamSession::where('status', '!=', SessionStatus::Completed)
            ->where('end_time', '<=', $now)
            ->pluck('id');

        if ($finishedSessionIds->isNotEmpty()) {
            ExamAllocation::whereIn('exam_session_id', $finishedSessionIds)
                ->where('seat_status', SeatStatus::Allocated)
                ->update(['seat_status' => SeatStatus::NoShow]);

            ExamAllocation::whereIn('exam_session_id', $finishedSessionIds)
                ->where('seat_status', SeatStatus::CheckedIn)
                ->update(['seat_status' => SeatStatus::Completed]);

            ExamSession::whereIn('id', $finishedSessionIds)
                ->update(['status' => SessionStatus::Completed]);
        }

        Exam::where('status', ExamStatus::Scheduled)
            ->whereHas('sessions', fn ($q) => $q->where('status', SessionStatus::InProgress))
            ->update(['status' => ExamStatus::InProgress]);

        Exam::whereIn('status', [ExamStatus::Scheduled, ExamStatus::InProgress])
            ->whereHas('sessions')
            ->whereDoesntHave('sessions', fn ($q) => $q->where('status', '!=', SessionStatus::Completed))
            ->update(['status' => ExamStatus::Completed]);

        $this->info('Exam lifecycle synchronized.');

        return self::SUCCESS;
    }
}
