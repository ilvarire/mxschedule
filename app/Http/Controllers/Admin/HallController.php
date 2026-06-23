<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hall;
use App\Models\System;
use Illuminate\Http\Request;

class HallController extends Controller
{
    public function index()
    {
        $halls = Hall::withCount('systems')
            ->withCount(['systems as active_systems_count' => fn ($q) => $q->where('status', 'active')])
            ->latest()
            ->get();

        return view('admin.halls.index', compact('halls'));
    }

    public function create()
    {
        return view('admin.halls.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:halls,code',
            'location' => 'nullable|string|max:255',
            'capacity' => 'required|integer|min:1',
        ]);

        Hall::create($validated);

        return redirect()->route('admin.halls.index')
            ->with('success', 'Hall created successfully.');
    }

    public function show(Hall $hall)
    {
        $hall->load('systems');
        $hall->setRelation('systems', System::naturalSort($hall->systems));

        return view('admin.halls.show', compact('hall'));
    }

    public function edit(Hall $hall)
    {
        return view('admin.halls.edit', compact('hall'));
    }

    public function update(Request $request, Hall $hall)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => "required|string|max:10|unique:halls,code,{$hall->id}",
            'location' => 'nullable|string|max:255',
            'capacity' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $hall->update($validated);

        return redirect()->route('admin.halls.show', $hall)
            ->with('success', 'Hall updated successfully.');
    }

    public function destroy(Hall $hall)
    {
        if ($hall->systems()->whereHas('examAllocations')->exists()) {
            return back()->with('error', 'Cannot delete a hall that has exam allocation history.');
        }

        $hall->delete();

        return redirect()->route('admin.halls.index')
            ->with('success', 'Hall deleted.');
    }
}
