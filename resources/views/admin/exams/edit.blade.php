<x-layouts.app :title="'Edit Exam'">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.exams.show', $exam) }}" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></a>
            <h1 class="text-2xl font-bold text-gray-900">Edit {{ $exam->course->code }} Exam</h1>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.exams.update', $exam) }}" method="POST" class="space-y-5">
                    @csrf @method('PUT')
                    <div>
                        <label class="form-label">Course</label>
                        <select name="course_id" class="form-input-styled" required>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ old('course_id', $exam->course_id) == $course->id ? 'selected' : '' }}>
                                    {{ $course->code }} — {{ $course->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Academic Session</label>
                            <input type="text" name="academic_session" value="{{ old('academic_session', $exam->academic_session) }}" class="form-input-styled" required>
                        </div>
                        <div>
                            <label class="form-label">Semester</label>
                            <select name="semester" class="form-input-styled" required>
                                <option value="first" {{ old('semester', $exam->semester->value) === 'first' ? 'selected' : '' }}>First</option>
                                <option value="second" {{ old('semester', $exam->semester->value) === 'second' ? 'selected' : '' }}>Second</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Exam Date</label>
                            <input type="date" name="exam_date" value="{{ old('exam_date', $exam->exam_date->format('Y-m-d')) }}" class="form-input-styled" required>
                        </div>
                        <div>
                            <label class="form-label">Start Time</label>
                            <input type="time" name="start_time" value="{{ old('start_time', $exam->start_time) }}" class="form-input-styled" required>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Duration (minutes)</label>
                            <input type="number" name="duration_minutes" value="{{ old('duration_minutes', $exam->duration_minutes) }}" class="form-input-styled" min="15" max="300" required>
                        </div>
                        <div>
                            <label class="form-label">Buffer (minutes)</label>
                            <input type="number" name="buffer_minutes" value="{{ old('buffer_minutes', $exam->buffer_minutes) }}" class="form-input-styled" min="5" max="60" required>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="3" class="form-input-styled">{{ old('notes', $exam->notes) }}</textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-4">
                        <a href="{{ route('admin.exams.show', $exam) }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>
