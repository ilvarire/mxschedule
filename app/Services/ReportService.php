<?php

namespace App\Services;

use App\Enums\ScanResult;
use App\Enums\SeatStatus;
use App\Models\AttendanceLog;
use App\Models\Exam;
use App\Models\ExamAllocation;
use App\Models\ExamSession;
use App\Models\Hall;
use App\Models\System;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Get attendance report for an exam.
     */
    public function attendanceReport(Exam $exam): array
    {
        $sessions = $exam->sessions()->with('allocations.studentProfile.user')->get();

        $total = 0;
        $checkedIn = 0;
        $noShow = 0;

        foreach ($sessions as $session) {
            foreach ($session->allocations as $alloc) {
                $total++;
                match ($alloc->seat_status) {
                    SeatStatus::CheckedIn, SeatStatus::Completed => $checkedIn++,
                    SeatStatus::NoShow => $noShow++,
                    default => null,
                };
            }
        }

        return [
            'exam' => $exam->load('course'),
            'sessions' => $sessions,
            'total_students' => $total,
            'checked_in' => $checkedIn,
            'no_show' => $noShow,
            'attendance_rate' => $total > 0 ? round(($checkedIn / $total) * 100, 1) : 0,
        ];
    }

    /**
     * System usage statistics.
     */
    public function systemUsageReport(): Collection
    {
        return Hall::with(['systems' => function ($q) {
            $q->withCount(['examAllocations as total_usage'])
                ->withCount(['examAllocations as active_allocation_count' => function ($q) {
                    $q->where('seat_status', '!=', SeatStatus::Reassigned);
                }]);
        }])->get()->map(function ($hall) {
            return [
                'hall' => $hall->name,
                'hall_code' => $hall->code,
                'total_systems' => $hall->systems->count(),
                'active_systems' => $hall->systems->where('status', \App\Enums\SystemStatus::Active)->count(),
                'systems' => $hall->systems->map(fn ($s) => [
                    'code' => $s->system_code,
                    'status' => $s->status->label(),
                    'total_usage' => $s->total_usage,
                    'last_used' => $s->last_used_at?->format('Y-m-d H:i'),
                ]),
            ];
        });
    }

    /**
     * Load distribution across halls for a specific exam.
     */
    public function loadDistributionReport(Exam $exam): Collection
    {
        return ExamSession::where('exam_id', $exam->id)
            ->with(['allocations' => fn ($q) => $q->where('seat_status', '!=', SeatStatus::Reassigned)])
            ->get()
            ->map(function ($session) {
                $hallGroups = $session->allocations->groupBy('hall_id');

                return [
                    'session_number' => $session->session_number,
                    'start_time' => $session->start_time->format('H:i'),
                    'end_time' => $session->end_time->format('H:i'),
                    'total_allocated' => $session->allocated_count,
                    'halls' => $hallGroups->map(function ($allocations, $hallId) {
                        $hall = Hall::find($hallId);
                        return [
                            'hall' => $hall->name,
                            'count' => $allocations->count(),
                        ];
                    })->values(),
                ];
            });
    }

    /**
     * Missed exams (no-show) report.
     */
    public function missedExamsReport(Exam $exam): Collection
    {
        return ExamAllocation::whereHas('examSession', fn ($q) => $q->where('exam_id', $exam->id))
            ->where('seat_status', SeatStatus::NoShow)
            ->with(['studentProfile.user', 'examSession', 'system', 'hall'])
            ->get();
    }

    /**
     * Dashboard statistics.
     */
    public function dashboardStats(): array
    {
        return [
            'total_students' => \App\Models\StudentProfile::count(),
            'active_systems' => System::where('status', \App\Enums\SystemStatus::Active)->count(),
            'total_halls' => Hall::where('is_active', true)->count(),
            'faulty_systems' => System::where('status', \App\Enums\SystemStatus::Faulty)->count(),
            'upcoming_exams' => Exam::where('exam_date', '>=', now()->toDateString())
                ->where('status', '!=', \App\Enums\ExamStatus::Cancelled)
                ->count(),
            'today_exams' => Exam::where('exam_date', now()->toDateString())->count(),
            'recent_attendance_rate' => $this->recentAttendanceRate(),
        ];
    }

    /**
     * Calculate recent attendance rate (last 7 days).
     */
    protected function recentAttendanceRate(): float
    {
        $total = ExamAllocation::whereHas('examSession', function ($q) {
            $q->where('start_time', '>=', now()->subDays(7));
        })->count();

        if ($total === 0) {
            return 0;
        }

        $attended = ExamAllocation::whereHas('examSession', function ($q) {
            $q->where('start_time', '>=', now()->subDays(7));
        })->whereIn('seat_status', [SeatStatus::CheckedIn, SeatStatus::Completed])->count();

        return round(($attended / $total) * 100, 1);
    }
}
