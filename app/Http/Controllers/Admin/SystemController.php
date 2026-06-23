<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SystemStatus;
use App\Http\Controllers\Controller;
use App\Models\Hall;
use App\Models\System;
use App\Services\ReallocationService;
use App\Services\SystemManagementService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class SystemController extends Controller
{
    public function index()
    {
        $sortedSystems = System::naturalSort(System::with('hall')->get());
        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 50;

        $systems = new LengthAwarePaginator(
            $sortedSystems->forPage($page, $perPage)->values(),
            $sortedSystems->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );

        return view('admin.systems.index', compact('systems'));
    }

    public function bulkCreate(Request $request, Hall $hall, SystemManagementService $service)
    {
        $validated = $request->validate([
            'count' => 'required|integer|min:1|max:500',
            'prefix' => 'nullable|string|max:10',
        ]);

        $systems = $service->bulkCreateSystems(
            $hall,
            $validated['count'],
            $validated['prefix'] ?? null,
        );

        return redirect()->route('admin.halls.show', $hall)
            ->with('success', "{$systems->count()} systems created.");
    }

    public function updateStatus(
        Request $request,
        System $system,
        SystemManagementService $service,
        ReallocationService $reallocationService,
    ) {
        $validated = $request->validate([
            'status' => 'required|in:active,inactive,faulty',
            'reason' => 'nullable|string|max:500',
        ]);

        $newStatus = SystemStatus::from($validated['status']);
        $service->updateStatus($system, $newStatus, auth()->id(), $validated['reason'] ?? null);

        // Auto-reassign students if system is now unavailable
        if ($newStatus !== SystemStatus::Active) {
            $reassigned = $reallocationService->reassignFromSystem($system);
            if ($reassigned->isNotEmpty()) {
                return back()->with('success', "System status updated. {$reassigned->count()} student(s) reassigned.");
            }
        }

        return back()->with('success', 'System status updated.');
    }
}
