<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExamStatus;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateExamScheduleJob;
use App\Jobs\SendScheduleNotificationsJob;
use App\Models\Exam;
use App\Models\ExamAllocation;
use App\Models\System;
use App\Services\ExamRegistrationService;

class ScheduleController extends Controller
{
    public function generate(Exam $exam)
    {
        $this->authorize('schedule', $exam);

        $activeSystems = System::available()->count();

        if ($activeSystems === 0) {
            return back()->with('error', 'No active systems available. Please configure systems first.');
        }

        $registeredStudents = app(ExamRegistrationService::class)->registeredStudentCount($exam);

        if ($registeredStudents === 0) {
            return back()->with('error', 'No students are registered for this exam. Confirm the course, academic session, and semester match the CSV/course registrations.');
        }

        $exam->update([
            'status' => ExamStatus::Scheduling,
            'total_registered_students' => $registeredStudents,
        ]);

        GenerateExamScheduleJob::dispatch($exam, auth()->id());

        return back()->with('success', 'Schedule generation has been queued. Keep this page open or refresh it to see progress.');
    }

    public function reschedule(Exam $exam)
    {
        $this->authorize('reschedule', $exam);

        // Delete existing sessions (cascades to allocations and passes)
        $exam->sessions()->delete();
        $exam->update(['status' => ExamStatus::Scheduling]);

        GenerateExamScheduleJob::dispatch($exam);

        return back()->with('success', 'Re-scheduling has been queued. Previous allocations have been cleared.');
    }

    public function notify(Exam $exam)
    {
        $this->authorize('sendNotifications', $exam);

        if ($exam->status->value !== 'scheduled') {
            return back()->with('error', 'Can only send notifications for a fully scheduled exam.');
        }

        SendScheduleNotificationsJob::dispatch($exam);

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
