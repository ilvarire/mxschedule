<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAllocation;
use App\Models\Setting;
use App\Services\QrValidationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OfflineSyncController extends Controller
{
    public function downloadSchedule(Exam $exam)
    {
        $this->authorize('view', $exam);
        $exam->loadMissing(['course', 'sessions']);

        $allocations = ExamAllocation::whereHas('examSession', fn ($q) => $q->where('exam_id', $exam->id))
            ->where('seat_status', '!=', 'reassigned')
            ->with(['studentProfile.user', 'system', 'hall', 'examSession'])
            ->get()
            ->map(fn ($allocation) => [
                'aid' => $allocation->id,
                'student' => $allocation->studentProfile->user->name,
                'matric' => $allocation->studentProfile->matric_number,
                'system' => $allocation->system->system_code,
                'hall' => $allocation->hall->name,
                'session' => $allocation->examSession->session_number,
                'start' => $allocation->examSession->start_time->timestamp,
                'end' => $allocation->examSession->end_time->timestamp,
                'status' => $allocation->seat_status->value,
            ]);

        return response()->json([
            'exam_id' => $exam->id,
            'course' => $exam->course->code,
            'exam_label' => "{$exam->course->code} - {$exam->course->title}",
            'exam_date' => $exam->exam_date->toDateString(),
            'session_count' => $exam->sessions->count(),
            'allocation_count' => $allocations->count(),
            'generated_at' => now()->timestamp,
            'allocations' => $allocations,
            'entry_window' => (int) Setting::getValue('entry_window_minutes', 15),
        ]);
    }

    public function getPublicKey()
    {
        $publicKeyPath = storage_path('app/keys/exam_public.pem');

        if (! file_exists($publicKeyPath)) {
            return response()->json(['error' => 'RSA public key not found. Offline validation is unavailable.'], 404);
        }

        return response()->json(['public_key' => file_get_contents($publicKeyPath)]);
    }

    public function sync(Request $request, QrValidationService $validationService)
    {
        $validated = $request->validate([
            'logs' => 'required|array|max:500',
            'logs.*.qr_payload' => 'required|string',
            'logs.*.scanned_at' => 'required|integer',
        ]);

        $syncedCount = 0;
        $errors = [];
        $acceptedIndexes = [];

        foreach ($validated['logs'] as $index => $logData) {
            try {
                $result = $validationService->reconcileOfflineScan(
                    $logData['qr_payload'],
                    Carbon::createFromTimestamp($logData['scanned_at']),
                    $request->user()->id,
                    $request->userAgent(),
                    $request->ip(),
                );

                if ($result['valid']) {
                    $syncedCount++;
                    $acceptedIndexes[] = $index;
                } else {
                    $errors[] = "Log #{$index}: {$result['message']}";
                }
            } catch (\Throwable $e) {
                $errors[] = "Log #{$index}: {$e->getMessage()}";
                Log::error('Offline sync error', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'success' => empty($errors),
            'synced_count' => $syncedCount,
            'accepted_indexes' => $acceptedIndexes,
            'errors' => $errors,
        ], empty($errors) ? 200 : 422);
    }
}
