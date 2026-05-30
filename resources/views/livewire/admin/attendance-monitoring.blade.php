<div wire:poll.5s>
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Live Attendance Monitor</h1>
            <p class="text-sm text-gray-500">Real-time check-in tracking and hall occupancy</p>
        </div>

        <div class="flex flex-wrap gap-3">
            <select wire:model.live="examId" class="form-input-styled text-sm">
                <option value="">Select Exam</option>
                @foreach($exams as $exam)
                    <option value="{{ $exam->id }}">{{ $exam->course->code }} - {{ $exam->exam_date->format('M d, Y') }}</option>
                @endforeach
            </select>

            <select wire:model.live="sessionId" class="form-input-styled text-sm">
                <option value="">Select Session</option>
                @foreach($sessions as $session)
                    <option value="{{ $session->id }}">Session {{ $session->session_number }} ({{ $session->start_time->format('H:i') }})</option>
                @endforeach
            </select>
        </div>
    </div>

    @if($stats)
        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="card p-4 flex flex-col items-center justify-center">
                <span class="text-3xl font-bold text-indigo-600">{{ $stats['checked_in'] }}</span>
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Checked In</span>
            </div>
            <div class="card p-4 flex flex-col items-center justify-center">
                <span class="text-3xl font-bold text-gray-900">{{ $stats['absent'] }}</span>
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Remaining</span>
            </div>
            <div class="card p-4 flex flex-col items-center justify-center">
                <span class="text-3xl font-bold text-emerald-600">{{ $stats['rate'] }}%</span>
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Attendance Rate</span>
            </div>
            <div class="card p-4 flex flex-col items-center justify-center">
                <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                    <div class="bg-indigo-500 h-full transition-all duration-500" style="width: {{ $stats['rate'] }}%"></div>
                </div>
                <span class="text-[10px] mt-2 text-gray-400">{{ $stats['checked_in'] }} / {{ $stats['total'] }} Students</span>
            </div>
        </div>

        @if(count($scanRateChart) > 0)
        <!-- Hourly Scan Rate Chart -->
        <div class="card mb-8 overflow-hidden">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900 text-sm">Hourly Check-In Rate</h3>
            </div>
            <div class="p-4">
                @php $maxCount = max(array_merge(array_column($scanRateChart, 'count'), [1])); @endphp
                <div class="flex items-end gap-2 h-24">
                    @foreach($scanRateChart as $bucket)
                        @php $pct = round(($bucket['count'] / $maxCount) * 100); @endphp
                        <div class="flex-1 flex flex-col items-center gap-1">
                            <span class="text-[10px] text-gray-500 font-medium">{{ $bucket['count'] > 0 ? $bucket['count'] : '' }}</span>
                            <div class="w-full bg-indigo-500 rounded-t-sm transition-all duration-500 hover:bg-indigo-600"
                                 style="height: {{ max($pct, 2) }}%;"
                                 title="{{ $bucket['count'] }} scans at {{ $bucket['label'] }}"></div>
                        </div>
                    @endforeach
                </div>
                <div class="flex gap-2 mt-1">
                    @foreach($scanRateChart as $bucket)
                        <div class="flex-1 text-center text-[9px] text-gray-400">{{ $bucket['label'] }}</div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Occupancy Maps -->
            <div class="lg:col-span-2 space-y-8">
                @foreach($hallData as $hallId => $data)
                    <div class="card overflow-hidden">
                        <div class="bg-gray-50 px-4 py-3 border-b border-gray-100 flex justify-between items-center">
                            <h3 class="font-semibold text-gray-900">{{ $data['name'] }}</h3>
                            <span class="text-xs text-gray-500">{{ $data['allocations']->where('seat_status', 'checked_in')->count() }} / {{ $data['allocations']->count() }}</span>
                        </div>
                        <div class="p-4">
                            <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-2">
                                @foreach($data['allocations'] as $allocation)
                                    <div 
                                        class="aspect-square rounded flex items-center justify-center text-[10px] font-medium transition-all cursor-help
                                        {{ $allocation->seat_status === 'checked_in' ? 'bg-emerald-500 text-white shadow-sm shadow-emerald-200' : 'bg-gray-100 text-gray-400' }}"
                                        title="{{ $allocation->studentProfile->user->name }} ({{ $allocation->system->system_code }})"
                                    >
                                        {{ $loop->iteration }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Real-time Feed -->
            <div class="space-y-4">
                <h3 class="text-sm font-semibold text-gray-900 flex items-center">
                    <span class="relative flex h-2 w-2 mr-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                    </span>
                    Live Activity Feed
                </h3>
                <div class="space-y-3">
                    @forelse($recentLogs as $log)
                        <div class="flex items-start gap-3 p-3 bg-white rounded-lg border border-gray-100 shadow-sm animate-in fade-in slide-in-from-right-4 duration-300">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                <span class="text-[10px] font-bold text-indigo-600">{{ substr($log->allocation->studentProfile->user->name, 0, 1) }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-gray-900 truncate">{{ $log->allocation->studentProfile->user->name }}</p>
                                <p class="text-[10px] text-gray-500">{{ $log->allocation->system->system_code }} · {{ $log->scanned_at->diffForHumans() }}</p>
                            </div>
                            <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[9px] font-bold">IN</span>
                        </div>
                    @empty
                        <div class="text-center py-8 card border-dashed border-2">
                            <p class="text-xs text-gray-400">Waiting for scans...</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @else
        <div class="card p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <h3 class="text-lg font-medium text-gray-900">No Session Selected</h3>
            <p class="text-sm text-gray-500 max-w-xs mx-auto mt-1">Please select an exam and session to view live monitoring data.</p>
        </div>
    @endif
</div>
