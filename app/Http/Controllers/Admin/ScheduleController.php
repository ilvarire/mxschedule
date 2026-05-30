<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateExamScheduleJob;
use App\Jobs\SendScheduleNotificationsJob;
use App\Models\Exam;
use App\Models\ExamAllocation;
use App\Models\System;

class ScheduleController extends Controller
{
    public function generate(Exam $exam)
    {
        $this->authorize('schedule', $exam);

        $activeSystems = System::available()->count();

        if ($activeSystems === 0) {
            return back()->with('error', 'No active systems available. Please configure systems first.');
        }

        GenerateExamScheduleJob::dispatch($exam, auth()->id());

        return back()->with('success', 'Schedule generation started. This may take a moment.');
    }

    public function reschedule(Exam $exam)
    {
        $this->authorize('reschedule', $exam);

        // Delete existing sessions (cascades to allocations and passes)
        $exam->sessions()->delete();
        $exam->update(['status' => 'draft']);

        GenerateExamScheduleJob::dispatch($exam);

        return back()->with('success', 'Re-scheduling started. Previous allocations have been cleared.');
    }

    public function notify(Exam $exam)
    {
        $this->authorize('schedule', $exam);

        if ($exam->status->value !== 'scheduled') {
            return back()->with('error', 'Can only send notifications for a fully scheduled exam.');
        }

        SendScheduleNotificationsJob::dispatch($exam)->onQueue('notifications');

        return back()->with('success', 'Notifications are being sent to all allocated students.');
    }

    public function allocations(Exam $exam)
    {
        $allocations = ExamAllocation::whereHas('examSession', fn ($q) => $q->where('exam_id', $exam->id))
            ->with(['studentProfile.user', 'system', 'hall', 'examSession'])
            ->orderBy('exam_session_id')
            ->paginate(50);

        return view('admin.exams.allocations', compact('exam', 'allocations'));
    }
}
