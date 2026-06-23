<x-layouts.app :title="'My Exams'">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">My Exams</h1>
        <p class="text-sm text-gray-500 mt-1">Your upcoming exam schedules and passes</p>
    </x-slot>

    @if($allocations->isEmpty())
        <div class="text-center py-16">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <p class="text-gray-500 text-lg">No exam schedules yet</p>
            <p class="text-gray-400 text-sm mt-1">Your exam schedule will appear here once it's released.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            @foreach($allocations as $alloc)
                @php
                    $session = $alloc->examSession;
                    $exam = $session->exam;
                    $isUpcoming = $session->start_time->isFuture();
                @endphp
                <div class="card {{ $isUpcoming ? '' : 'opacity-60' }}">
                    <div class="card-body">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">{{ $exam->course->code }}</h3>
                                <p class="text-sm text-gray-500">{{ $exam->course->title }}</p>
                            </div>
                            <span class="badge badge-{{ $alloc->seat_status->color() }}">{{ $alloc->seat_status->label() }}</span>
                        </div>

                        <div class="space-y-3 text-sm">
                            <div class="flex items-center gap-3">
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span class="font-semibold">{{ $exam->exam_date->format('l, F j, Y') }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span><strong>Session {{ $session->session_number }}:</strong> {{ $session->start_time->format('g:i A') }} — {{ $session->end_time->format('g:i A') }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                <span><strong>{{ $alloc->hall->name }}</strong></span>
                            </div>
                            <div class="flex items-center gap-3">
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <span>System: <strong class="font-mono">{{ $alloc->system->system_code }}</strong></span>
                            </div>
                        </div>

                        @if($isUpcoming)
                            <div class="mt-5 flex gap-2">
                                <a href="{{ route('student.exam-pass.show', $alloc) }}" class="btn btn-primary flex-1">View Pass</a>
                                <a href="{{ route('student.exam-pass.download', $alloc) }}" class="btn btn-secondary flex-1">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    {{ $alloc->examPass?->pdf_path ? 'Download PDF' : 'Prepare PDF' }}
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-layouts.app>
