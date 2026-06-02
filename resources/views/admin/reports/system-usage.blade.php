<x-layouts.app :title="'System Usage Report'">
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">System Usage Report</h1>
                <p class="text-gray-500 mt-1">Real-time health and availability of CBT infrastructure</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.reports.download', ['type' => 'system-usage', 'format' => 'pdf']) }}" class="btn btn-secondary btn-sm">
                    ↓ PDF Report
                </a>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Summaries --}}
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">Total Systems</p>
                    <h3 class="stat-value">{{ $usage['total'] }}</h3>
                </div>
                <div class="p-3 bg-indigo-50 rounded-xl text-indigo-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">Active</p>
                    <h3 class="stat-value text-green-600">{{ $usage['active'] }}</h3>
                </div>
                <div class="p-3 bg-green-50 rounded-xl text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
            </div>
            <p class="text-[10px] text-gray-400 mt-2 uppercase tracking-wider font-semibold">Ready for deployment</p>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">Faulty</p>
                    <h3 class="stat-value text-red-600">{{ $usage['faulty'] }}</h3>
                </div>
                <div class="p-3 bg-red-50 rounded-xl text-red-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
            </div>
            <p class="text-[10px] text-gray-400 mt-2 uppercase tracking-wider font-semibold">Requires maintenance</p>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">Availability</p>
                    @php 
                        $rate = $usage['total'] > 0 ? round(($usage['active'] / $usage['total']) * 100) : 0;
                    @endphp
                    <h3 class="stat-value {{ $rate > 90 ? 'text-green-600' : ($rate > 70 ? 'text-orange-600' : 'text-red-600') }}">
                        {{ $rate }}%
                    </h3>
                </div>
                <div class="p-3 bg-blue-50 rounded-xl text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Hall Breakdown --}}
        <div class="lg:col-span-2">
            <div class="card overflow-hidden">
                <div class="card-header border-b-0">
                    <h3 class="text-lg font-semibold text-gray-900">Per-Hall Availability</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Hall Name</th>
                                <th>Total Systems</th>
                                <th>Active</th>
                                <th>Faulty/Inactive</th>
                                <th>Efficiency</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($usage['halls'] as $hall)
                            <tr>
                                <td>
                                    <div class="font-semibold text-gray-900">{{ $hall->name }}</div>
                                    <div class="text-xs text-gray-500 uppercase">{{ $hall->code }}</div>
                                </td>
                                <td>{{ $hall->systems_count }}</td>
                                <td>
                                    <span class="text-green-600 font-medium">{{ $hall->active_systems_count }}</span>
                                </td>
                                <td>
                                    <span class="text-red-500 font-medium">{{ $hall->systems_count - $hall->active_systems_count }}</span>
                                </td>
                                <td class="w-48">
                                    @php 
                                        $hallRate = $hall->systems_count > 0 ? round(($hall->active_systems_count / $hall->systems_count) * 100) : 0;
                                    @endphp
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                                            <div class="h-full bg-indigo-500 rounded-full" style="width: {{ $hallRate }}%"></div>
                                        </div>
                                        <span class="text-xs font-bold text-gray-700">{{ $hallRate }}%</span>
                                    </div>
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.halls.show', $hall) }}" class="text-indigo-600 hover:text-indigo-900 text-sm font-semibold">View Hall</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Faulty Systems Log (Optional/Placeholder) --}}
        <div>
            <div class="card">
                <div class="card-header border-b-0">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Status Changes</h3>
                </div>
                <div class="card-body p-0">
                    <div class="divide-y divide-gray-100 max-h-[500px] overflow-y-auto">
                        @forelse($usage['recent_changes'] ?? [] as $log)
                        <div class="p-4 hover:bg-gray-50 transition-colors">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-bold text-gray-900">{{ $log->system->system_code }}</span>
                                <span class="status-badge status-{{ strtolower($log->new_status->value) }}">
                                    {{ $log->new_status->label() }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 line-clamp-1 mb-2">{{ $log->reason ?? 'No reason provided' }}</p>
                            <div class="flex items-center justify-between text-[10px] text-gray-400">
                                <span>By {{ $log->changedByUser->name ?? 'System' }}</span>
                                <span>{{ $log->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                        @empty 
                        <div class="p-8 text-center">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="text-gray-500 text-sm">No recent status changes recorded</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
