<x-layouts.app :title="'Settings'">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">Settings</h1>
        <p class="text-sm text-gray-500 mt-1">Scheduling, pass reveal, and attendance defaults</p>
    </x-slot>

    @php $values = $settings->pluck('value', 'key'); @endphp
    <form method="POST" action="{{ route('admin.settings.update') }}" class="card max-w-2xl">
        @csrf @method('PUT')
        <div class="card-body space-y-4">
            <label class="block">Academic Session<input class="form-input-styled mt-1" name="settings[academic_session]" value="{{ $values['academic_session'] ?? '' }}" required></label>
            <label class="block">Semester
                <select class="form-input-styled mt-1" name="settings[current_semester]">
                    <option value="first" {{ ($values['current_semester'] ?? '') === 'first' ? 'selected' : '' }}>First</option>
                    <option value="second" {{ ($values['current_semester'] ?? '') === 'second' ? 'selected' : '' }}>Second</option>
                </select>
            </label>
            <label class="block">Entry Window (minutes)<input type="number" class="form-input-styled mt-1" name="settings[entry_window_minutes]" value="{{ $values['entry_window_minutes'] ?? 15 }}" required></label>
            <label class="block">Pass Grace Period (minutes)<input type="number" class="form-input-styled mt-1" name="settings[pass_grace_minutes]" value="{{ $values['pass_grace_minutes'] ?? 5 }}" required></label>
            <label class="block">Delayed Reveal (hours)<input type="number" class="form-input-styled mt-1" name="settings[delayed_reveal_hours]" value="{{ $values['delayed_reveal_hours'] ?? 0 }}" required></label>
            <button class="btn btn-primary">Save Settings</button>
        </div>
    </form>
</x-layouts.app>
