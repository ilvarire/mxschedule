<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ExamAllocation;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $profile = $user->studentProfile;

        if (! $profile) {
            return view('student.dashboard', ['allocations' => collect()]);
        }

        $allocations = ExamAllocation::where('student_profile_id', $profile->id)
            ->where('seat_status', '!=', 'reassigned')
            ->with([
                'examSession.exam.course',
                'system',
                'hall',
                'examPass',
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('student.dashboard', compact('allocations'));
    }
}
