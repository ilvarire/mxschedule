<x-layouts.app :title="'Exams'">
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Examinations</h1>
                <p class="text-sm text-gray-500 mt-1">Create exams and generate schedules</p>
            </div>
            @can('create_exams')
            <a href="{{ route('admin.exams.create') }}" class="btn btn-primary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Create Exam
            </a>
            @endcan
        </div>
    </x-slot>

    <div class="card">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr><th>Course</th><th>Date</th><th>Duration</th><th>Students</th><th>Sessions</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($exams as $exam)
                    <tr>
                        <td>
                            <div class="font-semibold text-gray-900">{{ $exam->course->code }}</div>
                            <div class="text-xs text-gray-500">{{ $exam->course->title }}</div>
                        </td>
                        <td>
                            <div>{{ $exam->exam_date->format('M j, Y') }}</div>
                            <div class="text-xs text-gray-500">{{ $exam->start_time }}</div>
                        </td>
                        <td>{{ $exam->duration_minutes }} min</td>
                        <td>{{ number_format($exam->total_registered_students) }}</td>
                        <td>{{ $exam->sessions->count() }}</td>
                        <td><span class="badge badge-{{ $exam->status->color() }}">{{ $exam->status->label() }}</span></td>
                        <td>
                            <a href="{{ route('admin.exams.show', $exam) }}" class="text-indigo-600 hover:underline text-sm">View →</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-gray-400 py-8">No exams created yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $exams->links() }}</div>
</x-layouts.app>
