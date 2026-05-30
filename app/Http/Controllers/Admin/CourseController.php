<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::with('department')
            ->withCount('exams')
            ->latest()
            ->get();

        return view('admin.courses.index', compact('courses'));
    }

    public function create()
    {
        $departments = Department::orderBy('name')->get();
        return view('admin.courses.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'code' => 'required|string|max:20|unique:courses,code',
            'title' => 'required|string|max:255',
            'credit_units' => 'required|integer|min:1|max:10',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));

        Course::create($validated);

        return redirect()->route('admin.courses.index')
            ->with('success', 'Course created successfully.');
    }

    public function edit(Course $course)
    {
        $departments = Department::orderBy('name')->get();
        return view('admin.courses.edit', compact('course', 'departments'));
    }

    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'code' => "required|string|max:20|unique:courses,code,{$course->id}",
            'title' => 'required|string|max:255',
            'credit_units' => 'required|integer|min:1|max:10',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));

        $course->update($validated);

        return redirect()->route('admin.courses.index')
            ->with('success', 'Course updated successfully.');
    }

    public function destroy(Course $course)
    {
        if ($course->exams()->count() > 0) {
            return back()->with('error', 'Cannot delete course because it has existing exams attached to it.');
        }

        if ($course->studentProfiles()->count() > 0) {
            return back()->with('error', 'Cannot delete course because students are enrolled in it.');
        }

        $course->delete();

        return redirect()->route('admin.courses.index')
            ->with('success', 'Course deleted successfully.');
    }
}
