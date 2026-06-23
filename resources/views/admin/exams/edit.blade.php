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
                    <x-form-error-summary />

                    <div>
                        <label for="course_id" class="form-label">Course</label>
                        <select id="course_id" name="course_id" class="form-input-styled" required>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ old('course_id', $exam->course_id) == $course->id ? 'selected' : '' }}>
                                    {{ $course->code }} — {{ $course->title }}
                                </option>
                            @endforeach
                        </select>
                        @error('course_id') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="academic_session" class="form-label">Academic Session</label>
                            <input id="academic_session" type="text" name="academic_session" value="{{ old('academic_session', $exam->academic_session) }}" class="form-input-styled" required>
                            @error('academic_session') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="semester" class="form-label">Semester</label>
                            <select id="semester" name="semester" class="form-input-styled" required>
                                <option value="first" {{ old('semester', $exam->semester->value) === 'first' ? 'selected' : '' }}>First</option>
                                <option value="second" {{ old('semester', $exam->semester->value) === 'second' ? 'selected' : '' }}>Second</option>
                            </select>
                            @error('semester') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="exam_date" class="form-label">Exam Date</label>
                            <input id="exam_date" type="date" name="exam_date" value="{{ old('exam_date', $exam->exam_date->format('Y-m-d')) }}" class="form-input-styled" required>
                            @error('exam_date') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="start_time" class="form-label">Start Time</label>
                            <input id="start_time" type="time" name="start_time" value="{{ old('start_time', $exam->start_time) }}" class="form-input-styled" required>
                            @error('start_time') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="duration_minutes" class="form-label">Duration (minutes)</label>
                            <input id="duration_minutes" type="number" name="duration_minutes" value="{{ old('duration_minutes', $exam->duration_minutes) }}" class="form-input-styled" min="15" max="300" required>
                            @error('duration_minutes') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="buffer_minutes" class="form-label">Buffer (minutes)</label>
                            <input id="buffer_minutes" type="number" name="buffer_minutes" value="{{ old('buffer_minutes', $exam->buffer_minutes) }}" class="form-input-styled" min="5" max="60" required>
                            @error('buffer_minutes') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label for="notes" class="form-label">Notes</label>
                        <textarea id="notes" name="notes" rows="3" class="form-input-styled">{{ old('notes', $exam->notes) }}</textarea>
                        @error('notes') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
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
