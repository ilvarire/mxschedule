<?php

namespace App\Services;

use App\Models\ExamAllocation;
use App\Models\ExamPass;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ExamPassService
{
    /**
     * Generate passes for all allocations of an exam.
     */
    public function generateForExam(int $examId): int
    {
        $allocations = ExamAllocation::whereHas('examSession', fn ($q) => $q->where('exam_id', $examId))
            ->whereDoesntHave('examPass')
            ->with(['examSession', 'studentProfile.user', 'system', 'hall'])
            ->get();

        $count = 0;
        foreach ($allocations as $allocation) {
            $this->generateForAllocation($allocation);
            $count++;
        }

        return $count;
    }

    /**
     * Generate a pass for a single allocation.
     */
    public function generateForAllocation(ExamAllocation $allocation): ExamPass
    {
        $allocation->loadMissing(['examSession', 'studentProfile.user', 'system', 'hall']);

        // Build pass code (unique hash)
        $passCode = hash('sha256', implode('|', [
            $allocation->id,
            $allocation->student_profile_id,
            $allocation->exam_session_id,
            $allocation->system_id,
            now()->timestamp,
            random_int(0, PHP_INT_MAX),
        ]));

        // Build QR payload
        $payload = $this->buildQrPayload($allocation, $passCode);

        // Calculate expiry (session end + grace)
        $graceMinutes = (int) Setting::getValue('pass_grace_minutes', 5);
        $expiresAt = $allocation->examSession->end_time->copy()->addMinutes($graceMinutes);

        return ExamPass::create([
            'exam_allocation_id' => $allocation->id,
            'pass_code' => $passCode,
            'qr_payload' => $payload,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Build the signed QR payload.
     */
    protected function buildQrPayload(ExamAllocation $allocation, string $passCode): string
    {
        $now = time();
        $data = [
            'iss' => config('app.url'),
            'aud' => 'invigilator-app',
            'sub' => (string) $allocation->student_profile_id,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $allocation->examSession->end_time->timestamp,
            'jti' => $passCode,
            
            // Custom claims
            'aid' => $allocation->id,
            'sid' => $allocation->student_profile_id,
            'pid' => $passCode,
            'ses' => $allocation->exam_session_id,
        ];

        $privateKeyPath = storage_path('app/keys/exam_private.pem');

        if (file_exists($privateKeyPath)) {
            $privateKey = file_get_contents($privateKeyPath);
            return \Firebase\JWT\JWT::encode($data, $privateKey, 'RS256');
        } else {
            // Fallback to HMAC if RSA keys are not set up (for local dev)
            $signingKey = Setting::getValue('qr_signing_key') ?? 'default_dev_key';
            return \Firebase\JWT\JWT::encode($data, $signingKey, 'HS256');
        }
    }

    /**
     * Generate the PDF for an exam pass (quarter A4).
     */
    public function generatePdf(ExamPass $pass): string
    {
        $pass->loadMissing([
            'allocation.examSession.exam.course',
            'allocation.studentProfile.user',
            'allocation.system',
            'allocation.hall',
        ]);

        $allocation = $pass->allocation;
        $qrCodeSvg = QrCode::format('svg')
            ->size(200)
            ->errorCorrection('H')
            ->generate($pass->qr_payload);

        $pdf = Pdf::loadView('pdf.exam-pass', [
            'pass' => $pass,
            'allocation' => $allocation,
            'student' => $allocation->studentProfile,
            'user' => $allocation->studentProfile->user,
            'session' => $allocation->examSession,
            'exam' => $allocation->examSession->exam,
            'course' => $allocation->examSession->exam->course,
            'system' => $allocation->system,
            'hall' => $allocation->hall,
            'qrCodeSvg' => $qrCodeSvg,
        ]);

        // Quarter A4: 105mm × 148.5mm
        $pdf->setPaper([0, 0, 297.64, 420.94], 'portrait');

        $filename = "exam-passes/{$allocation->id}_{$pass->pass_code}.pdf";

        // Store via the Storage abstraction so cloud drivers (S3 etc.) work transparently.
        Storage::disk('public')->put($filename, $pdf->output());

        $pass->update(['pdf_path' => $filename]);

        return $filename;
    }

    /**
     * Check if a pass should be visible (delayed reveal).
     */
    public function isPassVisible(ExamPass $pass): bool
    {
        $delayHours = (int) Setting::getValue('delayed_reveal_hours', 0);

        if ($delayHours === 0) {
            return true;
        }

        $session = $pass->allocation->examSession;
        $revealAt = $session->start_time->copy()->subHours($delayHours);

        return now()->gte($revealAt);
    }
}
