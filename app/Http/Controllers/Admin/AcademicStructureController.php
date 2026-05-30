<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Faculty;
use Illuminate\Http\Request;

class AcademicStructureController extends Controller
{
    public function index()
    {
        return view('admin.academic-structure.index', [
            'faculties' => Faculty::with('departments')->orderBy('name')->get(),
        ]);
    }

    public function storeFaculty(Request $request)
    {
        Faculty::create($request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:faculties,code',
        ]));

        return back()->with('success', 'Faculty created.');
    }

    public function storeDepartment(Request $request)
    {
        Department::create($request->validate([
            'faculty_id' => 'required|exists:faculties,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:departments,code',
        ]));

        return back()->with('success', 'Department created.');
    }

    public function destroyFaculty(Faculty $faculty)
    {
        if ($faculty->departments()->exists()) {
            return back()->with('error', 'Delete the faculty departments first.');
        }

        $faculty->delete();
        return back()->with('success', 'Faculty deleted.');
    }

    public function destroyDepartment(Department $department)
    {
        if ($department->courses()->exists() || $department->studentProfiles()->exists()) {
            return back()->with('error', 'Cannot delete a department with courses or students.');
        }

        $department->delete();
        return back()->with('success', 'Department deleted.');
    }
}
