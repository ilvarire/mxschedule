<?php

namespace App\Services;

use App\Enums\ScanResult;
use App\Enums\SeatStatus;
use App\Models\AttendanceLog;
use App\Models\ExamAllocation;
use App\Models\ExamPass;
use App\Models\Setting;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QrValidationService
{
    /**
     * Validate a live scan and process check-in.
     *
     * @return array{valid: bool, result: ScanResult, message: string, allocation: ?ExamAllocation}
     */
    public function validate(
        string $qrPayload,
        int $scannedByUserId,
        ?string $deviceInfo = null,
        ?string $ipAddress = null,
    ): array {
        return DB::transaction(function () use ($qrPayload, $scannedByUserId, $deviceInfo, $ipAddress) {
            return $this->processVerifiedScan(
                $qrPayload,
                now(),
                $scannedByUserId,
                $deviceInfo,
                $ipAddress,
            );
        });
    }

    /**
     * Reconcile an offline scan using the signed QR payload and device scan time.
     *
     * @return array{valid: bool, result: ScanResult, message: string, allocation: ?ExamAllocation}
     */
    public function reconcileOfflineScan(
        string $qrPayload,
        Carbon $scannedAt,
        int $scannedByUserId,
        ?string $deviceInfo = null,
        ?string $ipAddress = null,
    ): array {
        return DB::transaction(function () use ($qrPayload, $scannedAt, $scannedByUserId, $deviceInfo, $ipAddress) {
            return $this->processVerifiedScan(
                $qrPayload,
                $scannedAt,
                $scannedByUserId,
                $deviceInfo,
                $ipAddress,
                true,
            );
        });
    }

    protected function processVerifiedScan(
        string $qrPayload,
        Carbon $scannedAt,
        int $scannedByUserId,
        ?string $deviceInfo,
        ?string $ipAddress,
        bool $syncedFromOffline = false,
    ): array {
        $data = $this->decodePayload($qrPayload, $scannedAt->timestamp);

        if (! $data) {
            return $this->result(ScanResult::InvalidPass, 'QR code is invalid or signature verification failed.');
        }

        $pass = ExamPass::where('pass_code', $data['pid'])->lockForUpdate()->first();
        if (! $pass) {
            return $this->result(ScanResult::InvalidPass, 'Exam pass not found.');
        }

        $allocation = $pass->allocation()->with('examSession')->lockForUpdate()->first();
        if (! $allocation || ! $this->claimsMatchAllocation($data, $allocation)) {
            return $this->result(ScanResult::InvalidPass, 'Exam pass claims do not match the allocation.');
        }

        if ($scannedAt->isAfter($pass->expires_at)) {
            return $this->loggedResult($allocation->id, $scannedByUserId, ScanResult::Expired, 'This exam pass has expired.', $scannedAt, $deviceInfo, $ipAddress, $syncedFromOffline);
        }

        if ($pass->is_used || $allocation->seat_status === SeatStatus::CheckedIn) {
            $usedAt = $pass->used_at?->format('H:i:s') ?? $allocation->checked_in_at?->format('H:i:s') ?? 'an earlier time';
            return $this->loggedResult($allocation->id, $scannedByUserId, ScanResult::Duplicate, "Pass already used at {$usedAt}.", $scannedAt, $deviceInfo, $ipAddress, $syncedFromOffline);
        }

        $session = $allocation->examSession;
        $windowMinutes = (int) Setting::getValue('entry_window_minutes', 15);
        $windowStart = $session->start_time->copy()->subMinutes($windowMinutes);
        $windowEnd = $session->end_time->copy()->addMinutes($windowMinutes);

        if ($scannedAt->isBefore($windowStart)) {
            return $this->loggedResult($allocation->id, $scannedByUserId, ScanResult::Early, "Too early. Entry opens at {$windowStart->format('H:i')}.", $scannedAt, $deviceInfo, $ipAddress, $syncedFromOffline);
        }

        if ($scannedAt->isAfter($windowEnd)) {
            return $this->loggedResult($allocation->id, $scannedByUserId, ScanResult::Late, 'Entry window has passed.', $scannedAt, $deviceInfo, $ipAddress, $syncedFromOffline);
        }

        $pass->update(['is_used' => true, 'used_at' => $scannedAt]);
        $allocation->update([
            'seat_status' => SeatStatus::CheckedIn,
            'checked_in_at' => $scannedAt,
            'checked_in_by' => $scannedByUserId,
        ]);
        $allocation->system()->update(['last_used_at' => $scannedAt]);

        $this->logAttempt($allocation->id, $scannedByUserId, ScanResult::Valid, $scannedAt, $deviceInfo, $ipAddress, $syncedFromOffline);
        $allocation->load('studentProfile.user', 'system', 'hall');

        return $this->result(ScanResult::Valid, 'Entry confirmed.', $allocation);
    }

    protected function claimsMatchAllocation(array $data, ExamAllocation $allocation): bool
    {
        return (int) $data['aid'] === $allocation->id
            && (int) $data['sid'] === $allocation->student_profile_id
            && (int) $data['ses'] === $allocation->exam_session_id;
    }

    protected function decodePayload(string $payload, ?int $verificationTime = null): ?array
    {
        $originalTimestamp = JWT::$timestamp;

        try {
            JWT::$timestamp = $verificationTime;
            $publicKeyPath = storage_path('app/keys/exam_public.pem');

            if (file_exists($publicKeyPath)) {
                $key = new Key(file_get_contents($publicKeyPath), 'RS256');
            } else {
                $signingKey = Setting::getValue('qr_signing_key') ?: config('app.key');
                if (! $signingKey) {
                    throw new \RuntimeException('QR signing key is not configured.');
                }
                $key = new Key($signingKey, 'HS256');
            }

            $data = (array) JWT::decode($payload, $key);

            foreach (['aid', 'sid', 'pid', 'ses'] as $field) {
                if (! isset($data[$field])) {
                    return null;
                }
            }

            return $data;
        } catch (\Exception $e) {
            Log::warning('QR decode/verify failed', ['error' => $e->getMessage()]);
            return null;
        } finally {
            JWT::$timestamp = $originalTimestamp;
        }
    }

    protected function loggedResult(
        int $allocationId,
        int $scannedBy,
        ScanResult $result,
        string $message,
        Carbon $scannedAt,
        ?string $deviceInfo,
        ?string $ipAddress,
        bool $syncedFromOffline,
    ): array {
        $this->logAttempt($allocationId, $scannedBy, $result, $scannedAt, $deviceInfo, $ipAddress, $syncedFromOffline);

        return $this->result($result, $message);
    }

    protected function result(ScanResult $result, string $message, ?ExamAllocation $allocation = null): array
    {
        return [
            'valid' => $result->isSuccessful(),
            'result' => $result,
            'message' => $message,
            'allocation' => $allocation,
        ];
    }

    protected function logAttempt(
        int $allocationId,
        int $scannedBy,
        ScanResult $result,
        Carbon $scannedAt,
        ?string $deviceInfo,
        ?string $ipAddress,
        bool $syncedFromOffline,
    ): void {
        AttendanceLog::create([
            'exam_allocation_id' => $allocationId,
            'scanned_by' => $scannedBy,
            'scan_result' => $result,
            'scanned_at' => $scannedAt,
            'device_info' => $deviceInfo,
            'ip_address' => $ipAddress,
            'synced_from_offline' => $syncedFromOffline,
        ]);
    }
}
