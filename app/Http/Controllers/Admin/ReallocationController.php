<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamAllocation;
use App\Models\System;
use App\Services\ReallocationService;
use Illuminate\Http\Request;

class ReallocationController extends Controller
{
    public function reassign(Request $request, ReallocationService $service)
    {
        $validated = $request->validate([
            'allocation_id' => 'required|exists:exam_allocations,id',
            'new_system_id' => 'required|exists:systems,id',
        ]);

        $allocation = ExamAllocation::findOrFail($validated['allocation_id']);
        $newSystem = System::findOrFail($validated['new_system_id']);

        $newAllocation = $service->reassignStudent($allocation, $newSystem);

        return back()->with('success', 'Student reassigned successfully.');
    }
}
