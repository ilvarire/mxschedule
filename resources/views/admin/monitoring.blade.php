<x-layouts.app :title="'Live Attendance Monitoring'">
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Live Monitor</h1>
            <div class="flex items-center gap-2">
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                </span>
                <span class="text-xs font-medium text-emerald-700">System Live</span>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        @livewire('admin.attendance-monitoring')
    </div>
</x-layouts.app>
