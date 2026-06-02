<x-layouts.app :title="$exam->course->code . ' Exam'">
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <a href="{{ route('admin.exams.index') }}" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $exam->course->code }} — {{ $exam->course->title }}</h1>
                    <p class="text-sm text-gray-500">{{ $exam->exam_date->format('l, F j, Y') }} at {{ $exam->start_time }} · {{ $exam->duration_minutes }} min · {{ $exam->academic_session }} {{ $exam->semester->label() }}</p>
                </div>
            </div>
            <span class="badge badge-{{ $exam->status->color() }} text-sm px-3 py-1">{{ $exam->status->label() }}</span>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Info Card -->
        <div class="card">
            <div class="card-header"><h3 class="text-sm font-semibold text-gray-900">Exam Details</h3></div>
            <div class="card-body space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Registered Students</span><span class="font-semibold">{{ number_format($exam->total_registered_students) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Duration</span><span class="font-semibold">{{ $exam->duration_minutes }} min</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Buffer</span><span class="font-semibold">{{ $exam->buffer_minutes }} min</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Sessions</span><span class="font-semibold">{{ $exam->sessions->count() }}</span></div>
                @if($exam->scheduled_at)
                <div class="flex justify-between"><span class="text-gray-500">Scheduled</span><span class="font-semibold">{{ $exam->scheduled_at->diffForHumans() }}</span></div>
                @endif
                @if($exam->notes)
                <div class="pt-3 border-t border-gray-100"><p class="text-gray-600">{{ $exam->notes }}</p></div>
                @endif
            </div>
        </div>

        <!-- Actions Card -->
        <div class="card">
            <div class="card-header"><h3 class="text-sm font-semibold text-gray-900">Actions</h3></div>
            <div class="card-body space-y-3">
                @if($exam->canBeScheduled())
                    <form action="{{ route('admin.exams.schedule', $exam) }}" method="POST" onsubmit="return confirm('Generate schedule for {{ $exam->total_registered_students }} students?')">
                        @csrf
                        <button type="submit" class="btn btn-primary w-full">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            Generate Schedule
                        </button>
                    </form>
                @endif
                @if($exam->status === \App\Enums\ExamStatus::Scheduled)
                    <a href="{{ route('admin.exams.allocations', $exam) }}" class="btn btn-secondary w-full">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        View Allocations
                    </a>
                    <form action="{{ route('admin.exams.notify', $exam) }}" method="POST"
                          onsubmit="return confirm('This will send email notifications to all {{ $exam->total_registered_students }} allocated students. Continue?')">
                        @csrf
                        <button type="submit" class="btn btn-secondary w-full">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Resend Notifications
                        </button>
                    </form>
                    <form action="{{ route('admin.exams.reschedule', $exam) }}" method="POST" onsubmit="return confirm('This will clear all current allocations and passes. Continue?')">
                        @csrf
                        <button type="submit" class="btn btn-danger w-full">Re-schedule</button>
                    </form>
                @endif
                @if($exam->canBeModified())
                    <a href="{{ route('admin.exams.edit', $exam) }}" class="btn btn-secondary w-full">Edit Exam</a>
                @endif
            </div>
        </div>

        <!-- Sessions Summary -->
        <div class="card">
            <div class="card-header"><h3 class="text-sm font-semibold text-gray-900">Sessions</h3></div>
            <div class="card-body">
                @if($exam->sessions->isEmpty())
                    <p class="text-center text-gray-400 py-4 text-sm">No sessions generated yet</p>
                @else
                    <div class="space-y-3">
                        @foreach($exam->sessions as $session)
                        <div class="p-3 rounded-lg bg-gray-50 border border-gray-100">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-semibold text-gray-900">Session {{ $session->session_number }}</span>
                                <span class="badge badge-{{ $session->status->color() }}">{{ $session->status->label() }}</span>
                            </div>
                            <p class="text-xs text-gray-500">{{ $session->start_time->format('g:i A') }} — {{ $session->end_time->format('g:i A') }}</p>
                            <p class="text-xs text-gray-500">{{ $session->allocated_count }}/{{ $session->max_capacity }} allocated</p>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
