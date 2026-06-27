<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExamPass;
use App\Services\QrValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QrValidationController extends Controller
{
    public function validate(Request $request, QrValidationService $service): JsonResponse
    {
        $request->validate([
            'qr_payload' => 'required|string',
        ]);

        $payload = trim($request->input('qr_payload'));
        if (! $this->looksLikeJwt($payload)) {
            $pass = $this->findPassFromManualIdentifier($payload);

            if (! $pass) {
                return response()->json([
                    'valid' => false,
                    'result' => 'invalid_pass',
                    'result_label' => 'Invalid Pass',
                    'message' => 'Pass ID or pass code was not found. Scan the QR code, paste the full QR payload, or enter the printed pass code.',
                ], 422);
            }

            if ($pass === 'ambiguous') {
                return response()->json([
                    'valid' => false,
                    'result' => 'invalid_pass',
                    'result_label' => 'Invalid Pass',
                    'message' => 'More than one pass matches that code prefix. Enter the full pass code or scan the QR code.',
                ], 422);
            }

            $payload = $pass->qr_payload;
        }

        $result = $service->validate(
            $payload,
            $request->user()->id,
            $request->userAgent(),
            $request->ip(),
        );

        $response = [
            'valid' => $result['valid'],
            'result' => $result['result']->value,
            'result_label' => $result['result']->label(),
            'message' => $result['message'],
        ];

        if ($result['allocation']) {
            $alloc = $result['allocation'];
            $response['student'] = [
                'name' => $alloc->studentProfile->user->name,
                'matric_number' => $alloc->studentProfile->matric_number,
                'hall' => $alloc->hall->name,
                'system' => $alloc->system->system_code,
            ];
        }

        return response()->json($response, $result['valid'] ? 200 : 422);
    }

    protected function looksLikeJwt(string $value): bool
    {
        return substr_count($value, '.') === 2;
    }

    protected function findPassFromManualIdentifier(string $value): ExamPass|string|null
    {
        $identifier = trim($value);
        $identifier = preg_replace('/^pass\s*(id|code)?\s*:\s*/i', '', $identifier);
        $identifier = trim(strtok($identifier, '|') ?: $identifier);

        if ($identifier === '') {
            return null;
        }

        $query = ExamPass::query()->where('pass_code', $identifier);

        if (ctype_digit($identifier)) {
            $query->orWhereKey((int) $identifier);
        }

        if (strlen($identifier) >= 8) {
            $query->orWhere('pass_code', 'like', $identifier . '%');
        }

        $matches = $query->limit(2)->get();

        if ($matches->count() > 1) {
            return 'ambiguous';
        }

        return $matches->first();
    }
}
