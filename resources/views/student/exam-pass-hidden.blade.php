<x-layouts.app :title="'Pass Not Available'">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('student.dashboard') }}" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></a>
            <h1 class="text-2xl font-bold text-gray-900">Exam Pass</h1>
        </div>
    </x-slot>

    <div class="max-w-md mx-auto text-center py-16">
        <div class="w-20 h-20 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h2 class="text-xl font-bold text-gray-900 mb-2">Pass Not Available Yet</h2>
        <p class="text-gray-500 mb-4">Your exam pass will be visible at:</p>
        <p class="text-2xl font-bold text-indigo-600">{{ $revealTime->format('g:i A') }}</p>
        <p class="text-sm text-gray-400">{{ $revealTime->format('l, F j, Y') }}</p>
        <a href="{{ route('student.dashboard') }}" class="btn btn-secondary mt-8">Back to Dashboard</a>
    </div>
</x-layouts.app>
