<?php

namespace App\Livewire\Admin;

use App\Models\Exam;
use App\Models\ExamAllocation;
use App\Models\ExamSession;
use App\Models\Hall;
use App\Models\AttendanceLog;
use Livewire\Component;
use Livewire\Attributes\Url;

class AttendanceMonitoring extends Component
{
    #[Url]
    public ?int $examId = null;

    #[Url]
    public ?int $sessionId = null;

    public function mount()
    {
        if (!$this->examId) {
            $this->examId = Exam::orderBy('exam_date', 'desc')->first()?->id;
        }

        if ($this->examId && !$this->sessionId) {
            $this->sessionId = ExamSession::where('exam_id', $this->examId)->first()?->id;
        }
    }

    public function updatedExamId($value)
    {
        $this->sessionId = ExamSession::where('exam_id', $value)->first()?->id;
    }

    public function getExamsProperty()
    {
        return Exam::with('course')->orderBy('exam_date', 'desc')->get();
    }

    public function getSessionsProperty()
    {
        return $this->examId ? ExamSession::where('exam_id', $this->examId)->get() : collect();
    }

    public function getStatsProperty()
    {
        if (!$this->sessionId) return null;

        $totalAllocated = ExamAllocation::where('exam_session_id', $this->sessionId)
            ->where('seat_status', '!=', 'reassigned')
            ->count();
        $checkedIn = ExamAllocation::where('exam_session_id', $this->sessionId)
            ->whereIn('seat_status', ['checked_in', 'completed'])
            ->count();

        return [
            'total' => $totalAllocated,
            'checked_in' => $checkedIn,
            'absent' => $totalAllocated - $checkedIn,
            'rate' => $totalAllocated > 0 ? round(($checkedIn / $totalAllocated) * 100, 1) : 0,
        ];
    }

    public function getHallDataProperty()
    {
        if (!$this->sessionId) return [];

        $allocations = ExamAllocation::where('exam_session_id', $this->sessionId)
            ->where('seat_status', '!=', 'reassigned')
            ->with(['studentProfile.user', 'system', 'hall'])
            ->get();

        // Group by hall
        $halls = $allocations->groupBy('hall_id')->map(function ($hallAllocations) {
            $hall = $hallAllocations->first()->hall;
            return [
                'name' => $hall->name,
                'allocations' => $hallAllocations->sortBy('system.system_code')
            ];
        });

        return $halls;
    }

    public function getRecentLogsProperty()
    {
        if (!$this->sessionId) return collect();

        return AttendanceLog::whereHas('allocation', function ($q) {
                $q->where('exam_session_id', $this->sessionId);
            })
            ->where('scan_result', 'valid')
            ->with('allocation.studentProfile.user')
            ->latest()
            ->limit(10)
            ->get();
    }

    public function getScanRateChartProperty()
    {
        if (!$this->sessionId) return [];

        $session = ExamSession::find($this->sessionId);
        if (!$session) return [];

        // Build hourly buckets from session start to now (or end)
        $start = $session->start_time->copy()->startOfHour();
        $end = now()->min($session->end_time)->startOfHour();

        $driver = \Illuminate\Support\Facades\DB::getDriverName();
        $hourExpression = $driver === 'sqlite' 
            ? "CAST(strftime('%H', scanned_at) AS INTEGER)" 
            : "HOUR(scanned_at)";

        $logs = AttendanceLog::whereHas('allocation', function ($q) {
                $q->where('exam_session_id', $this->sessionId);
            })
            ->where('scan_result', 'valid')
            ->selectRaw("$hourExpression as hour, COUNT(*) as count")
            ->groupByRaw($hourExpression)
            ->pluck('count', 'hour');

        $chart = [];
        $current = $start->copy();
        while ($current->lte($end)) {
            $chart[] = [
                'label' => $current->format('H:i'),
                'count' => $logs->get($current->hour, 0),
            ];
            $current->addHour();
        }

        return $chart;
    }

    public function render()
    {
        return view('livewire.admin.attendance-monitoring', [
            'exams' => $this->exams,
            'sessions' => $this->sessions,
            'stats' => $this->stats,
            'hallData' => $this->hallData,
            'recentLogs' => $this->recentLogs,
            'scanRateChart' => $this->scanRateChart,
        ]);
    }
}
