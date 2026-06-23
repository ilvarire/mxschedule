<x-layouts.app :title="'Settings'">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">Settings</h1>
        <p class="text-sm text-gray-500 mt-1">Scheduling, pass reveal, and attendance defaults</p>
    </x-slot>

    @php $values = $settings->pluck('value', 'key'); @endphp
    <form method="POST" action="{{ route('admin.settings.update') }}" class="card max-w-2xl">
        @csrf
        @method('PUT')

        <div class="card-body space-y-5">
            <div>
                <label for="setting_academic_session" class="form-label">Academic Session</label>
                <input id="setting_academic_session" class="form-input-styled" name="settings[academic_session]" value="{{ $values['academic_session'] ?? '' }}" required>
            </div>

            <div>
                <label for="setting_current_semester" class="form-label">Semester</label>
                <select id="setting_current_semester" class="form-input-styled" name="settings[current_semester]">
                    <option value="first" {{ ($values['current_semester'] ?? '') === 'first' ? 'selected' : '' }}>First</option>
                    <option value="second" {{ ($values['current_semester'] ?? '') === 'second' ? 'selected' : '' }}>Second</option>
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="setting_entry_window_minutes" class="form-label">Entry Window</label>
                    <input id="setting_entry_window_minutes" type="number" class="form-input-styled" name="settings[entry_window_minutes]" value="{{ $values['entry_window_minutes'] ?? 15 }}" min="0" required>
                    <p class="text-xs text-gray-500 mt-1">Minutes before session start.</p>
                </div>

                <div>
                    <label for="setting_pass_grace_minutes" class="form-label">Pass Grace</label>
                    <input id="setting_pass_grace_minutes" type="number" class="form-input-styled" name="settings[pass_grace_minutes]" value="{{ $values['pass_grace_minutes'] ?? 5 }}" min="0" required>
                    <p class="text-xs text-gray-500 mt-1">Minutes after session end.</p>
                </div>

                <div>
                    <label for="setting_delayed_reveal_hours" class="form-label">Delayed Reveal</label>
                    <input id="setting_delayed_reveal_hours" type="number" class="form-input-styled" name="settings[delayed_reveal_hours]" value="{{ $values['delayed_reveal_hours'] ?? 0 }}" min="0" required>
                    <p class="text-xs text-gray-500 mt-1">Hours before pass is visible.</p>
                </div>
            </div>

            <div>
                <label for="setting_exam_reminder_hours" class="form-label">Exam Reminder Hours</label>
                <input id="setting_exam_reminder_hours" class="form-input-styled" name="settings[exam_reminder_hours]" value="{{ $values['exam_reminder_hours'] ?? '24,1' }}" placeholder="24,1" required>
                <p class="text-xs text-gray-500 mt-1">Comma-separated hours before the exam session starts. Example: 24,1 sends 24-hour and 1-hour reminders.</p>
                @error('settings.exam_reminder_hours') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="btn btn-primary w-full sm:w-auto">Save Settings</button>
        </div>
    </form>
</x-layouts.app>
