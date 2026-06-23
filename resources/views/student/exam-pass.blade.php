<x-layouts.app :title="'Exam Pass'">
    @php
        $session = $allocation->examSession;
        $exam = $session->exam;
        $pass = $allocation->examPass;
    @endphp

    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('student.dashboard') }}" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></a>
            <h1 class="text-2xl font-bold text-gray-900">Exam Pass — {{ $exam->course->code }}</h1>
        </div>
    </x-slot>

    <div class="max-w-md mx-auto">
        <!-- Pass Card -->
        <div class="card overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 px-6 py-5 text-center">
                <h2 class="text-white text-xl font-bold">EXAM PASS</h2>
                <p class="text-indigo-200 text-sm mt-1">{{ $exam->course->code }} — {{ $exam->course->title }}</p>
                <span class="inline-block mt-2 bg-white/20 text-white text-xs font-bold px-3 py-1 rounded-full">
                    Session {{ $session->session_number }}
                </span>
            </div>

            <!-- Student Info -->
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
                <p class="font-bold text-gray-900 text-lg">{{ $allocation->studentProfile->user->name }}</p>
                <p class="text-gray-500 text-sm font-mono">{{ $allocation->studentProfile->matric_number }}</p>
            </div>

            <!-- Details -->
            <div class="px-6 py-4 space-y-4">
                <div class="flex justify-between items-center py-2 border-b border-gray-50">
                    <span class="text-xs text-gray-400 uppercase tracking-wider">Date</span>
                    <span class="text-sm font-semibold text-gray-900">{{ $exam->exam_date->format('l, F j, Y') }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-50">
                    <span class="text-xs text-gray-400 uppercase tracking-wider">Time</span>
                    <span class="text-sm font-semibold text-gray-900">{{ $session->start_time->format('g:i A') }} — {{ $session->end_time->format('g:i A') }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-50">
                    <span class="text-xs text-gray-400 uppercase tracking-wider">Hall</span>
                    <span class="text-sm font-semibold text-gray-900">{{ $allocation->hall->name }}</span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="text-xs text-gray-400 uppercase tracking-wider">System</span>
                    <span class="text-lg font-bold text-indigo-600 font-mono">{{ $allocation->system->system_code }}</span>
                </div>
            </div>

            <!-- QR Code -->
            @if($pass)
            <div class="px-6 py-6 text-center border-t border-gray-100 bg-gray-50">
                <div class="inline-block p-4 bg-white rounded-xl shadow-sm">
                    {!! QrCode::size(180)->errorCorrection('H')->generate($pass->qr_payload) !!}
                </div>
                <p class="text-xs text-gray-400 mt-3">Scan at the exam hall entrance</p>
                <p class="text-xs text-gray-300 mt-1 font-mono">{{ substr($pass->pass_code, 0, 12) }}…</p>
            </div>
            @else
            <div class="px-6 py-6 text-center border-t border-gray-100 bg-amber-50">
                <p class="text-amber-700 text-sm font-medium">Pass not yet generated. Check back later.</p>
            </div>
            @endif

            <!-- Actions -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                <a href="{{ route('student.exam-pass.download', $allocation) }}" class="btn btn-primary w-full">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    {{ $pass?->pdf_path ? 'Download PDF' : 'Prepare PDF' }}
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
