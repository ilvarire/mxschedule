<?php

namespace App\Services;

use App\Enums\ScanResult;
use App\Enums\SeatStatus;
use App\Models\AttendanceLog;
use App\Models\ExamAllocation;
use App\Models\ExamPass;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class QrValidationService
{
    /**
     * Validate a scanned QR code and process check-in.
     *
     * @return array{valid: bool, result: ScanResult, message: string, allocation: ?ExamAllocation}
     */
    public function validate(
        string $qrPayload,
        int $scannedByUserId,
        ?string $deviceInfo = null,
        ?string $ipAddress = null,
    ): array {
        // Step 1: Decode and verify signature
        $data = $this->decodePayload($qrPayload);

        if (! $data) {
            return $this->result(ScanResult::InvalidPass, 'Invalid QR code format.');
        }

        if (! $this->verifySignature($data)) {
            return $this->result(ScanResult::InvalidPass, 'QR code signature verification failed.');
        }

        // Step 2: Find the pass
        $pass = ExamPass::where('pass_code', $data['pid'])->first();

        if (! $pass) {
            return $this->result(ScanResult::InvalidPass, 'Exam pass not found.');
        }

        // Step 3: Check if expired
        if ($pass->expires_at->isPast()) {
            $this->logAttempt($pass->exam_allocation_id, $scannedByUserId, ScanResult::Expired, $deviceInfo, $ipAddress);
            return $this->result(ScanResult::Expired, 'This exam pass has expired.');
        }

        // Step 4: Check for duplicate use
        if ($pass->is_used) {
            $this->logAttempt($pass->exam_allocation_id, $scannedByUserId, ScanResult::Duplicate, $deviceInfo, $ipAddress);
            return $this->result(ScanResult::Duplicate, "Pass already used at {$pass->used_at->format('H:i:s')}.");
        }

        // Step 5: Load allocation with session
        $allocation = $pass->allocation()->with('examSession')->first();

        if (! $allocation) {
            return $this->result(ScanResult::InvalidPass, 'Allocation record not found.');
        }

        // Step 6: Check time window
        $session = $allocation->examSession;
        $windowMinutes = (int) Setting::getValue('entry_window_minutes', 15);

        $now = now();
        $windowStart = $session->start_time->copy()->subMinutes($windowMinutes);
        $windowEnd = $session->end_time->copy()->addMinutes($windowMinutes);

        if ($now->isBefore($windowStart)) {
            $this->logAttempt($allocation->id, $scannedByUserId, ScanResult::Early, $deviceInfo, $ipAddress);
            return $this->result(ScanResult::Early, "Too early. Entry opens at {$windowStart->format('H:i')}.");
        }

        if ($now->isAfter($windowEnd)) {
            $this->logAttempt($allocation->id, $scannedByUserId, ScanResult::Late, $deviceInfo, $ipAddress);
            return $this->result(ScanResult::Late, 'Entry window has passed.');
        }

        // Step 7: Check correct session (time slot matching)
        if ($allocation->exam_session_id !== (int) $data['ses']) {
            $this->logAttempt($allocation->id, $scannedByUserId, ScanResult::WrongSlot, $deviceInfo, $ipAddress);
            return $this->result(
                ScanResult::WrongSlot,
                "Wrong session. Your session starts at {$session->start_time->format('H:i')}."
            );
        }

        // ── ALL CHECKS PASSED — Process Check-In ────────

        // Mark pass as used
        $pass->markAsUsed();

        // Update allocation status
        $allocation->update([
            'seat_status' => SeatStatus::CheckedIn,
            'checked_in_at' => now(),
            'checked_in_by' => $scannedByUserId,
        ]);

        // Log valid scan
        $this->logAttempt($allocation->id, $scannedByUserId, ScanResult::Valid, $deviceInfo, $ipAddress);

        // Reload with student info for response
        $allocation->load('studentProfile.user', 'system', 'hall');

        return $this->result(
            ScanResult::Valid,
            'Entry confirmed.',
            $allocation,
        );
    }

    /**
     * Decode the QR payload JSON.
     */
    protected function decodePayload(string $payload): ?array
    {
        try {
            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

            // Validate required fields
            $required = ['aid', 'sid', 'pid', 'ses', 'exp', 'sig'];
            foreach ($required as $field) {
                if (! isset($data[$field])) {
                    return null;
                }
            }

            return $data;
        } catch (\JsonException $e) {
            Log::warning('QR decode failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Verify the HMAC-SHA256 signature of the QR payload.
     */
    protected function verifySignature(array $data): bool
    {
        $signingKey = Setting::getValue('qr_signing_key');

        if (! $signingKey) {
            Log::error('QR signing key not configured.');
            return false;
        }

        // Rebuild the payload string (all fields except 'sig')
        $payload = json_encode([
            'aid' => $data['aid'],
            'sid' => $data['sid'],
            'pid' => $data['pid'],
            'ses' => $data['ses'],
            'exp' => $data['exp'],
        ]);

        $expectedSig = hash_hmac('sha256', $payload, $signingKey);

        return hash_equals($expectedSig, $data['sig']);
    }

    /**
     * Create a result array.
     */
    protected function result(ScanResult $result, string $message, ?ExamAllocation $allocation = null): array
    {
        return [
            'valid' => $result->isSuccessful(),
            'result' => $result,
            'message' => $message,
            'allocation' => $allocation,
        ];
    }

    /**
     * Log an attendance attempt.
     */
    protected function logAttempt(
        int $allocationId,
        int $scannedBy,
        ScanResult $result,
        ?string $deviceInfo,
        ?string $ipAddress,
        bool $syncedFromOffline = false,
    ): void {
        AttendanceLog::create([
            'exam_allocation_id' => $allocationId,
            'scanned_by' => $scannedBy,
            'scan_result' => $result,
            'scanned_at' => now(),
            'device_info' => $deviceInfo,
            'ip_address' => $ipAddress,
            'synced_from_offline' => $syncedFromOffline,
        ]);
    }
}
