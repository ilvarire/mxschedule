<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExamStatus;
use App\Enums\Semester;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Exam;
use App\Services\ExamRegistrationService;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function index()
    {
        $exams = Exam::with('course.department')
            ->latest('exam_date')
            ->paginate(20);
        $this->refreshDraftRegistrationCounts($exams->getCollection());

        return view('admin.exams.index', compact('exams'));
    }

    public function create()
    {
        $courses = Course::with('department')->orderBy('code')->get();

        return view('admin.exams.create', compact('courses'));
    }

    public function store(Request $request)
    {
        $this->normalizeTimeInput($request);
        $this->normalizeRegistrationInput($request);

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

        $exam = Exam::create([
            ...$validated,
            'status' => ExamStatus::Draft,
        ]);

        $studentCount = app(ExamRegistrationService::class)->registeredStudentCount($exam);
        $exam->update(['total_registered_students' => $studentCount]);

        return redirect()->route('admin.exams.show', $exam)
            ->with('success', "Exam created. {$studentCount} students registered.");
    }

    public function show(Exam $exam)
    {
        $this->refreshDraftRegistrationCounts(collect([$exam]));

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
        $this->normalizeTimeInput($request);
        $this->normalizeRegistrationInput($request);

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
        $exam->update([
            'total_registered_students' => app(ExamRegistrationService::class)->registeredStudentCount($exam->refresh()),
        ]);

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

    protected function normalizeTimeInput(Request $request): void
    {
        if ($request->filled('start_time')) {
            $request->merge([
                'start_time' => substr((string) $request->input('start_time'), 0, 5),
            ]);
        }
    }

    protected function normalizeRegistrationInput(Request $request): void
    {
        $request->merge([
            'academic_session' => trim((string) $request->input('academic_session')),
            'semester' => strtolower(trim((string) $request->input('semester'))),
        ]);
    }

    protected function refreshDraftRegistrationCounts($exams): void
    {
        $registrations = app(ExamRegistrationService::class);

        foreach ($exams as $exam) {
            if (! in_array($exam->status, [ExamStatus::Draft, ExamStatus::Cancelled], true)) {
                continue;
            }

            $count = $registrations->registeredStudentCount($exam);

            if ($exam->total_registered_students !== $count) {
                $exam->forceFill(['total_registered_students' => $count])->save();
            }
        }
    }
}
