<?php

namespace App\Http\Controllers\Api;

use App\Enums\ScanResult;
use App\Enums\SeatStatus;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAllocation;
use App\Models\AttendanceLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OfflineSyncController extends Controller
{
    /**
     * Download full schedule for an exam for offline use.
     */
    public function downloadSchedule(Exam $exam)
    {
        $this->authorize('view', $exam);

        $allocations = ExamAllocation::whereHas('examSession', fn ($q) => $q->where('exam_id', $exam->id))
            ->with(['studentProfile.user', 'system', 'examSession'])
            ->get()
            ->map(function ($allocation) {
                return [
                    'aid' => $allocation->id, // Allocation ID
                    'student' => $allocation->studentProfile->user->name,
                    'matric' => $allocation->studentProfile->matric_number,
                    'system' => $allocation->system->system_code,
                    'hall' => $allocation->hall_id, // For display
                    'session' => $allocation->examSession->session_number,
                    'start' => $allocation->examSession->start_time->timestamp,
                    'end' => $allocation->examSession->end_time->timestamp,
                    'status' => $allocation->seat_status,
                ];
            });

        return response()->json([
            'exam_id' => $exam->id,
            'course' => $exam->course->code,
            'generated_at' => now()->timestamp,
            'allocations' => $allocations,
            // Pre-calculate window for JS logic
            'entry_window' => (int) config('exam.entry_window', 15),
        ]);
    }

    /**
     * Download public key for offline verification.
     */
    public function getPublicKey()
    {
        $publicKeyPath = storage_path('app/keys/exam_public.pem');

        if (!file_exists($publicKeyPath)) {
            return response()->json(['error' => 'Public key not found on server.'], 404);
        }

        return response()->json([
            'public_key' => file_get_contents($publicKeyPath)
        ]);
    }

    /**
     * Sync attendance logs from offline devices.
     */
    public function sync(Request $request)
    {
        $request->validate([
            'logs' => 'required|array',
            'logs.*.aid' => 'required|exists:exam_allocations,id',
            'logs.*.scanned_at' => 'required|integer',
            'logs.*.result' => 'required|string',
        ]);

        $syncedCount = 0;
        $errors = [];

        DB::transaction(function () use ($request, &$syncedCount, &$errors) {
            foreach ($request->logs as $logData) {
                try {
                    $allocation = ExamAllocation::find($logData['aid']);
                    $scannedAt = \Carbon\Carbon::createFromTimestamp($logData['scanned_at']);

                    $notes = 'Synced from offline storage';
                    $conflict = false;

                    // Conflict resolution: if the device reported a valid scan
                    // but the allocation is already checked in (e.g., by another device online),
                    // flag it as a duplicate rather than overwriting the existing record.
                    if ($logData['result'] === ScanResult::Valid->value
                        && $allocation->seat_status->value === SeatStatus::CheckedIn->value) {
                        $conflict = true;
                        $notes .= " | CONFLICT: Already checked in at {$allocation->checked_in_at}";
                    }

                    // 1. Create Attendance Log
                    AttendanceLog::create([
                        'exam_allocation_id' => $allocation->id,
                        'scanned_by'         => auth()->id() ?? 1,
                        'scan_result'        => $conflict ? ScanResult::Duplicate->value : $logData['result'],
                        'scanned_at'         => $scannedAt,
                        'synced_from_offline' => true,
                        'device_info'        => $request->header('User-Agent'),
                        'ip_address'         => $request->ip(),
                        'notes'              => $notes,
                    ]);

                    // 2. Update Allocation Status if valid and not a conflict
                    if ($logData['result'] === ScanResult::Valid->value && ! $conflict) {
                        $allocation->update([
                            'seat_status'    => SeatStatus::CheckedIn,
                            'checked_in_at'  => $scannedAt,
                            'checked_in_by'  => auth()->id() ?? 1,
                        ]);
                    }

                    $syncedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Allocation #{$logData['aid']}: " . $e->getMessage();
                    Log::error("Offline sync error: " . $e->getMessage());
                }
            }
        });

        return response()->json([
            'success' => true,
            'synced_count' => $syncedCount,
            'errors' => $errors,
        ]);
    }
}
