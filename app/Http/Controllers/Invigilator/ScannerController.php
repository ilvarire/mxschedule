<?php

namespace App\Http\Controllers\Invigilator;

use App\Enums\ExamStatus;
use App\Http\Controllers\Controller;
use App\Models\Exam;

class ScannerController extends Controller
{
    public function index()
    {
        $exams = Exam::with(['course', 'sessions' => fn ($query) => $query->orderBy('session_number')])
            ->whereIn('status', [ExamStatus::Scheduled, ExamStatus::InProgress])
            ->whereHas('sessions')
            ->orderBy('exam_date')
            ->orderBy('start_time')
            ->get();

        return view('invigilator.scanner', compact('exams'));
    }
}
