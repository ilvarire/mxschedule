<x-layouts.app :title="'Session Attendance'">
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Session {{ $examSession->session_number }} Attendance</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $examSession->exam->course->code }} - {{ $examSession->start_time->format('M j, Y g:i A') }}</p>
        </div>
    </x-slot>

    <div class="card">
        <div class="table-container">
            <table class="data-table">
                <thead><tr><th>Student</th><th>Matric</th><th>Hall</th><th>System</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach($examSession->allocations()->with(['studentProfile.user', 'hall', 'system'])->get() as $allocation)
                    <tr>
                        <td>{{ $allocation->studentProfile->user->name }}</td>
                        <td>{{ $allocation->studentProfile->matric_number }}</td>
                        <td>{{ $allocation->hall->name }}</td>
                        <td>{{ $allocation->system->system_code }}</td>
                        <td><span class="badge badge-{{ $allocation->seat_status->color() }}">{{ $allocation->seat_status->label() }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
