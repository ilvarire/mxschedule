<x-layouts.app :title="'Create Exam'">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.exams.index') }}" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></a>
            <h1 class="text-2xl font-bold text-gray-900">Create Exam</h1>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.exams.store') }}" method="POST" class="space-y-5">
                    @csrf
                    <x-form-error-summary />

                    <div>
                        <label for="course_id" class="form-label">Course</label>
                        <select id="course_id" name="course_id" class="form-input-styled" required>
                            <option value="">Select a course…</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>
                                    {{ $course->code }} — {{ $course->title }} ({{ $course->department->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('course_id') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="academic_session" class="form-label">Academic Session</label>
                            <input id="academic_session" type="text" name="academic_session" value="{{ old('academic_session', \App\Models\Setting::getValue('academic_session', '2025/2026')) }}" class="form-input-styled" placeholder="e.g. 2025/2026" required>
                            @error('academic_session') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="semester" class="form-label">Semester</label>
                            <select id="semester" name="semester" class="form-input-styled" required>
                                <option value="first" {{ old('semester', \App\Models\Setting::getValue('current_semester', 'first')) === 'first' ? 'selected' : '' }}>First</option>
                                <option value="second" {{ old('semester') === 'second' ? 'selected' : '' }}>Second</option>
                            </select>
                            @error('semester') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="exam_date" class="form-label">Exam Date</label>
                            <input id="exam_date" type="date" name="exam_date" value="{{ old('exam_date') }}" class="form-input-styled" required>
                            @error('exam_date') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="start_time" class="form-label">Start Time</label>
                            <input id="start_time" type="time" name="start_time" value="{{ old('start_time', '09:00') }}" class="form-input-styled" required>
                            @error('start_time') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="duration_minutes" class="form-label">Duration (minutes)</label>
                            <input id="duration_minutes" type="number" name="duration_minutes" value="{{ old('duration_minutes', 60) }}" class="form-input-styled" min="15" max="300" required>
                            @error('duration_minutes') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="buffer_minutes" class="form-label">Buffer Between Sessions (min)</label>
                            <input id="buffer_minutes" type="number" name="buffer_minutes" value="{{ old('buffer_minutes', 15) }}" class="form-input-styled" min="5" max="60" required>
                            @error('buffer_minutes') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="notes" class="form-label">Notes <span class="text-gray-400">(optional)</span></label>
                        <textarea id="notes" name="notes" rows="3" class="form-input-styled" placeholder="Any special instructions…">{{ old('notes') }}</textarea>
                        @error('notes') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <a href="{{ route('admin.exams.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Create Exam</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>
