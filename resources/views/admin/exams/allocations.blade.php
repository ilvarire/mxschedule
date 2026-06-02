<x-layouts.app :title="'Allocations'">
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <a href="{{ route('admin.exams.show', $exam) }}" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Allocations — {{ $exam->course->code }}</h1>
                    <p class="text-sm text-gray-500">{{ $exam->exam_date->format('M j, Y') }} · {{ $allocations->total() }} total allocations</p>
                </div>
            </div>
            <a href="{{ route('admin.reports.download', ['type' => 'attendance', 'exam_id' => $exam->id, 'format' => 'csv']) }}" class="btn btn-secondary btn-sm">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export CSV
            </a>
        </div>
    </x-slot>

    <div class="card">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr><th>Session</th><th>Student</th><th>Matric No.</th><th>Hall</th><th>System</th><th>Status</th><th>Checked In</th></tr>
                </thead>
                <tbody>
                    @foreach($allocations as $alloc)
                    <tr>
                        <td><span class="badge badge-blue">Session {{ $alloc->examSession->session_number }}</span></td>
                        <td class="font-medium">{{ $alloc->studentProfile->user->name }}</td>
                        <td class="font-mono text-xs">{{ $alloc->studentProfile->matric_number }}</td>
                        <td>{{ $alloc->hall->name }}</td>
                        <td class="font-mono font-semibold">{{ $alloc->system->system_code }}</td>
                        <td><span class="badge badge-{{ $alloc->seat_status->color() }}">{{ $alloc->seat_status->label() }}</span></td>
                        <td>{{ $alloc->checked_in_at?->format('H:i:s') ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $allocations->links() }}</div>
</x-layouts.app>
