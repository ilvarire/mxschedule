<x-layouts.app :title="'Attendance Report'">
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Attendance Report</h1>
                @if(isset($exam))
                    <p class="text-sm text-gray-500 mt-1">{{ $exam->course->code }} — {{ $exam->exam_date->format('M j, Y') }}</p>
                @else
                    <p class="text-sm text-gray-500 mt-1">Select an exam to view attendance</p>
                @endif
            </div>
            @if(isset($exam))
            <div class="flex gap-2">
                <a href="{{ route('admin.reports.download', ['type' => 'attendance', 'exam_id' => $exam->id, 'format' => 'csv']) }}" class="btn btn-secondary btn-sm">CSV</a>
                <a href="{{ route('admin.reports.download', ['type' => 'attendance', 'exam_id' => $exam->id]) }}" class="btn btn-primary btn-sm">PDF</a>
            </div>
            @endif
        </div>
    </x-slot>

    <!-- Exam Selector -->
    <div class="card mb-6">
        <div class="card-body">
            <form method="GET" class="flex gap-4 items-end">
                <div class="flex-1">
                    <label class="form-label">Select Exam</label>
                    <select name="exam_id" class="form-input-styled" onchange="this.form.submit()">
                        <option value="">Choose an exam…</option>
                        @foreach(\App\Models\Exam::with('course')->latest('exam_date')->get() as $e)
                            <option value="{{ $e->id }}" {{ request('exam_id') == $e->id ? 'selected' : '' }}>
                                {{ $e->course->code }} — {{ $e->exam_date->format('M j, Y') }} ({{ $e->status->label() }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    @if(isset($exam))
    <!-- Summary Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <div class="stat-card">
            <div class="stat-value">{{ $total_students }}</div>
            <div class="stat-label">Total Students</div>
        </div>
        <div class="stat-card">
            <div class="stat-value text-emerald-600">{{ $checked_in }}</div>
            <div class="stat-label">Checked In</div>
        </div>
        <div class="stat-card">
            <div class="stat-value text-red-600">{{ $no_show }}</div>
            <div class="stat-label">No Show</div>
        </div>
        <div class="stat-card">
            <div class="stat-value text-indigo-600">{{ $attendance_rate }}%</div>
            <div class="stat-label">Attendance Rate</div>
        </div>
    </div>

    <!-- Per-Session Breakdown -->
    @foreach($sessions as $session)
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="text-sm font-semibold text-gray-900">Session {{ $session->session_number }} — {{ $session->start_time->format('g:i A') }} to {{ $session->end_time->format('g:i A') }}</h3>
            <span class="text-xs text-gray-500">{{ $session->allocations->count() }} students</span>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead><tr><th>Student</th><th>Matric</th><th>Hall</th><th>System</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach($session->allocations as $alloc)
                    <tr>
                        <td class="font-medium">{{ $alloc->studentProfile->user->name ?? 'N/A' }}</td>
                        <td class="font-mono text-xs">{{ $alloc->studentProfile->matric_number ?? 'N/A' }}</td>
                        <td>{{ $alloc->hall->name ?? '—' }}</td>
                        <td class="font-mono">{{ $alloc->system->system_code ?? '—' }}</td>
                        <td><span class="badge badge-{{ $alloc->seat_status->color() }}">{{ $alloc->seat_status->label() }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach
    @endif
</x-layouts.app>
