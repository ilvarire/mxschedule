<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExamStatus;
use App\Enums\Semester;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Exam;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function index()
    {
        $exams = Exam::with('course.department')
            ->latest('exam_date')
            ->paginate(20);

        return view('admin.exams.index', compact('exams'));
    }

    public function create()
    {
        $courses = Course::with('department')->orderBy('code')->get();

        return view('admin.exams.create', compact('courses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'academic_session' => 'required|string|max:20',
            'semester' => 'required|in:first,second',
            'exam_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'duration_minutes' => 'required|integer|min:15|max:300',
            'buffer_minutes' => 'required|integer|min:5|max:60',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Count registered students
        $studentCount = \App\Models\StudentProfile::whereHas('courses', function ($q) use ($validated) {
            $q->where('course_id', $validated['course_id'])
                ->where('academic_session', $validated['academic_session'])
                ->where('semester', $validated['semester']);
        })->count();

        $exam = Exam::create([
            ...$validated,
            'total_registered_students' => $studentCount,
            'status' => ExamStatus::Draft,
        ]);

        return redirect()->route('admin.exams.show', $exam)
            ->with('success', "Exam created. {$studentCount} students registered.");
    }

    public function show(Exam $exam)
    {
        $exam->load([
            'course.department',
            'sessions.allocations',
            'scheduler',
        ]);

        return view('admin.exams.show', compact('exam'));
    }

    public function edit(Exam $exam)
    {
        $this->authorize('update', $exam);
        $courses = Course::with('department')->orderBy('code')->get();

        return view('admin.exams.edit', compact('exam', 'courses'));
    }

    public function update(Request $request, Exam $exam)
    {
        $this->authorize('update', $exam);

        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'academic_session' => 'required|string|max:20',
            'semester' => 'required|in:first,second',
            'exam_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'duration_minutes' => 'required|integer|min:15|max:300',
            'buffer_minutes' => 'required|integer|min:5|max:60',
            'notes' => 'nullable|string|max:1000',
        ]);

        $exam->update($validated);

        return redirect()->route('admin.exams.show', $exam)
            ->with('success', 'Exam updated.');
    }

    public function destroy(Exam $exam)
    {
        $this->authorize('delete', $exam);

        $exam->delete();

        return redirect()->route('admin.exams.index')
            ->with('success', 'Exam deleted.');
    }
}
