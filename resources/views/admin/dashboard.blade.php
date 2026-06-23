<x-layouts.app :title="'Admin Dashboard'">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-sm text-gray-500 mt-1">Overview of your exam scheduling system</p>
    </x-slot>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div class="stat-icon bg-indigo-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
            </div>
            <div class="stat-value">{{ number_format($stats['total_students']) }}</div>
            <div class="stat-label">Registered Students</div>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div class="stat-icon bg-emerald-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
            </div>
            <div class="stat-value">{{ number_format($stats['active_systems']) }}</div>
            <div class="stat-label">Active Systems</div>
            @if($stats['faulty_systems'] > 0)
                <p class="text-xs text-red-500 mt-1">{{ $stats['faulty_systems'] }} faulty</p>
            @endif
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div class="stat-icon bg-amber-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
            </div>
            <div class="stat-value">{{ $stats['upcoming_exams'] }}</div>
            <div class="stat-label">Upcoming Exams</div>
            @if($stats['today_exams'] > 0)
                <p class="text-xs text-indigo-600 mt-1">{{ $stats['today_exams'] }} today</p>
            @endif
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div class="stat-icon bg-cyan-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="stat-value">{{ $stats['recent_attendance_rate'] }}%</div>
            <div class="stat-label">Attendance Rate (7d)</div>
        </div>
    </div>

    <!-- Quick Actions + System Health -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-sm font-semibold text-gray-900">Quick Actions</h3>
            </div>
            <div class="card-body space-y-3">
                @can('create_exams')
                <a href="{{ route('admin.exams.create') }}" class="btn btn-primary w-full">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Create New Exam
                </a>
                @endcan
                @can('manage_halls')
                <a href="{{ route('admin.halls.create') }}" class="btn btn-secondary w-full">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Add Hall
                </a>
                @endcan
                <a href="{{ route('admin.reports.show', 'attendance') }}" class="btn btn-secondary w-full">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    View Reports
                </a>
            </div>
        </div>

        <!-- System Health -->
        <div class="card lg:col-span-2">
            <div class="card-header">
                <h3 class="text-sm font-semibold text-gray-900">System Health</h3>
                <div class="flex items-center gap-4 text-xs text-gray-500">
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Active</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-gray-300"></span> Inactive</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span> Faulty</span>
                </div>
            </div>
            <div class="card-body">
                @php
                    $halls = \App\Models\Hall::where('is_active', true)->with('systems')->get();
                @endphp
                @forelse($halls as $hall)
                    <div class="mb-4 last:mb-0">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-sm font-semibold text-gray-700">{{ $hall->name }} <span class="text-gray-400">({{ $hall->code }})</span></h4>
                            <span class="text-xs text-gray-500">{{ $hall->systems->where('status.value', 'active')->count() }}/{{ $hall->systems->count() }} active</span>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach(\App\Models\System::naturalSort($hall->systems) as $sys)
                                <div class="system-dot {{ $sys->status->value }}" title="{{ $sys->system_code }} — {{ $sys->status->label() }}">
                                    {{ (int) preg_replace('/\D/', '', substr($sys->system_code, strlen($hall->code))) }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 text-center py-8">No halls configured yet. <a href="{{ route('admin.halls.create') }}" class="text-indigo-600 hover:underline">Add a hall</a></p>
                @endforelse
            </div>
        </div>
    </div>
</x-layouts.app>
