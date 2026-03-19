<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ExamAllocation;
use App\Services\ExamPassService;

class ExamPassController extends Controller
{
    public function show(ExamAllocation $allocation, ExamPassService $passService)
    {
        $this->authorize('view', $allocation);

        $allocation->load([
            'examSession.exam.course',
            'studentProfile.user',
            'system',
            'hall',
            'examPass',
        ]);

        // Check delayed reveal
        if ($allocation->examPass && ! $passService->isPassVisible($allocation->examPass)) {
            return view('student.exam-pass-hidden', [
                'allocation' => $allocation,
                'revealTime' => $allocation->examSession->start_time
                    ->subHours((int) \App\Models\Setting::getValue('delayed_reveal_hours', 0)),
            ]);
        }

        return view('student.exam-pass', compact('allocation'));
    }

    public function download(ExamAllocation $allocation, ExamPassService $passService)
    {
        $this->authorize('download', $allocation);

        $pass = $allocation->examPass;

        if (! $pass) {
            return back()->with('error', 'Exam pass not yet generated.');
        }

        if (! $passService->isPassVisible($pass)) {
            return back()->with('error', 'Pass is not yet available.');
        }

        // Generate PDF if not cached
        if (! $pass->pdf_path || ! file_exists(storage_path("app/public/{$pass->pdf_path}"))) {
            $passService->generatePdf($pass);
            $pass->refresh();
        }

        return response()->download(
            storage_path("app/public/{$pass->pdf_path}"),
            "exam-pass-{$allocation->studentProfile->matric_number}.pdf"
        );
    }
}
