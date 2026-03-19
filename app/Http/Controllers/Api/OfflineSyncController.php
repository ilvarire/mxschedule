<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\Exam;
use App\Models\ExamAllocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfflineSyncController extends Controller
{
    /**
     * Download schedule data for offline use.
     */
    public function downloadSchedule(Exam $exam): JsonResponse
    {
        $allocations = ExamAllocation::whereHas('examSession', fn ($q) => $q->where('exam_id', $exam->id))
            ->with(['examSession', 'studentProfile.user', 'system', 'hall', 'examPass'])
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'session_id' => $a->exam_session_id,
                'session_number' => $a->examSession->session_number,
                'session_start' => $a->examSession->start_time->timestamp,
                'session_end' => $a->examSession->end_time->timestamp,
                'student_name' => $a->studentProfile->user->name,
                'matric_number' => $a->studentProfile->matric_number,
                'system_code' => $a->system->system_code,
                'hall_name' => $a->hall->name,
                'pass_code' => $a->examPass?->pass_code,
                'qr_payload' => $a->examPass?->qr_payload,
            ]);

        return response()->json([
            'exam_id' => $exam->id,
            'course' => $exam->course->code,
            'date' => $exam->exam_date->toDateString(),
            'allocations' => $allocations,
            'downloaded_at' => now()->timestamp,
        ]);
    }

    /**
     * Sync offline attendance records.
     */
    public function sync(Request $request): JsonResponse
    {
        $request->validate([
            'records' => 'required|array',
            'records.*.allocation_id' => 'required|integer',
            'records.*.scan_result' => 'required|string',
            'records.*.scanned_at' => 'required|integer', // timestamp
        ]);

        $accepted = 0;
        $rejected = 0;

        foreach ($request->input('records') as $record) {
            // Check if allocation exists
            $allocation = ExamAllocation::find($record['allocation_id']);
            if (! $allocation) {
                $rejected++;
                continue;
            }

            // Check for duplicate (same allocation + similar timestamp)
            $exists = AttendanceLog::where('exam_allocation_id', $record['allocation_id'])
                ->where('scan_result', 'valid')
                ->exists();

            if ($exists) {
                $rejected++;
                continue;
            }

            AttendanceLog::create([
                'exam_allocation_id' => $record['allocation_id'],
                'scanned_by' => $request->user()->id,
                'scan_result' => $record['scan_result'],
                'scanned_at' => \Carbon\Carbon::createFromTimestamp($record['scanned_at']),
                'device_info' => $request->userAgent(),
                'ip_address' => $request->ip(),
                'synced_from_offline' => true,
            ]);

            $accepted++;
        }

        return response()->json([
            'accepted' => $accepted,
            'rejected' => $rejected,
            'total' => count($request->input('records')),
        ]);
    }
}
