<x-layouts.app :title="'Missed Exams Report'">
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Missed Exams</h1>
                <p class="text-gray-500 mt-1">Students who did not show up for their scheduled sessions</p>
            </div>
            <div class="flex gap-2 w-full sm:w-auto">
                <form action="{{ route('admin.reports.show', 'missed-exams') }}" method="GET" class="flex gap-2 w-full sm:w-auto">
                    <select name="exam_id" class="form-input-styled text-sm py-1.5" onchange="this.form.submit()">
                        <option value="">Select Exam...</option>
                        @foreach(\App\Models\Exam::with('course')->get() as $e)
                            <option value="{{ $e->id }}" {{ request('exam_id') == $e->id ? 'selected' : '' }}>
                                {{ $e->course->code }} — {{ $e->exam_date->format('M d') }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
    </x-slot>

    @if(!isset($missed) || $missed->isEmpty())
        <div class="card p-12 text-center">
            <svg class="w-16 h-16 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            <h3 class="text-lg font-medium text-gray-900">No Missed Exams Recorded</h3>
            <p class="text-gray-500 mt-1">Either no students missed this exam yet, or no exam was selected.</p>
        </div>
    @else
        <div class="card">
            <div class="card-body p-0">
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Matric Number</th>
                                <th>Session</th>
                                <th>Hall</th>
                                <th>System Code</th>
                                <th>Resolution</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($missed as $alloc)
                            <tr>
                                <td>
                                    <div class="font-semibold text-gray-900">{{ $alloc->studentProfile->user->name }}</div>
                                </td>
                                <td>{{ $alloc->studentProfile->matric_number }}</td>
                                <td>
                                    <span class="text-xs font-bold px-2 py-0.5 bg-gray-100 rounded text-gray-600">Session {{ $alloc->examSession->session_number }}</span>
                                    <div class="text-[10px] text-gray-400 mt-0.5">{{ $alloc->examSession->start_time->format('H:i') }}</div>
                                </td>
                                <td>{{ $alloc->hall->name }}</td>
                                <td>{{ $alloc->system->system_code }}</td>
                                <td>
                                    <form action="{{ route('admin.reallocate') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="allocation_id" value="{{ $alloc->id }}">
                                        <select name="new_system_id" class="form-input-styled text-xs mb-1" required>
                                            <option value="">Select system...</option>
                                            @foreach(\App\Models\System::available()->orderBy('system_code')->get() as $system)
                                                <option value="{{ $system->id }}">{{ $system->system_code }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="text-indigo-600 hover:text-indigo-900 text-xs font-bold uppercase tracking-wider">Re-allocate</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</x-layouts.app>
