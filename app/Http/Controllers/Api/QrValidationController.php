<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        $result = $service->validate(
            $request->input('qr_payload'),
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
}
