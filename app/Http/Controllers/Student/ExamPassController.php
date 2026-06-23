<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateExamPassPdfJob;
use App\Models\ExamAllocation;
use App\Services\ExamPassService;
use Illuminate\Support\Facades\Storage;

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

        $allocation->loadMissing([
            'examSession.exam',
            'studentProfile.user',
            'examPass',
        ]);

        $pass = $allocation->examPass;

        if (! $pass) {
            $pass = $passService->generateForAllocation($allocation);
            GenerateExamPassPdfJob::dispatch($allocation->examSession->exam);

            return back()->with('success', 'Your exam pass PDF is being prepared. Please try the download again in a minute.');
        }

        if (! $passService->isPassVisible($pass)) {
            return back()->with('error', 'Pass is not yet available.');
        }

        if (! $pass->pdf_path || ! Storage::disk('public')->exists($pass->pdf_path)) {
            GenerateExamPassPdfJob::dispatch($allocation->examSession->exam);

            return back()->with('success', 'Your exam pass PDF is being prepared. Please try the download again in a minute.');
        }

        return Storage::disk('public')->download(
            $pass->pdf_path,
            $this->downloadFilename($allocation)
        );
    }

    protected function downloadFilename(ExamAllocation $allocation): string
    {
        $matric = preg_replace('/[^A-Za-z0-9_-]+/', '-', $allocation->studentProfile->matric_number);

        return 'exam-pass-' . trim($matric, '-') . '.pdf';
    }
}
