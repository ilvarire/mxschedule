<x-layouts.app :title="'Load Distribution Report'">
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Load Distribution</h1>
                <p class="text-gray-500 mt-1">Student distribution across CBT halls per session</p>
            </div>
            <div class="flex gap-2 w-full sm:w-auto">
                <form action="{{ route('admin.reports.show', 'load-distribution') }}" method="GET" class="flex gap-2 w-full sm:w-auto">
                    <select name="exam_id" class="form-input-styled text-sm py-1.5" onchange="this.form.submit()" aria-label="Select exam for load distribution report">
                        <option value="">Select Exam...</option>
                        @foreach(\App\Models\Exam::with('course')->upcoming()->get() as $e)
                            <option value="{{ $e->id }}" {{ request('exam_id') == $e->id ? 'selected' : '' }}>
                                {{ $e->course->code }} — {{ $e->exam_date->format('M d') }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
    </x-slot>

    @if(!isset($distribution) || $distribution->isEmpty())
        <div class="card p-12 text-center">
            <svg class="w-16 h-16 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <h3 class="text-lg font-medium text-gray-900">No Data Available</h3>
            <p class="text-gray-500 mt-1">Please select an exam with generated sessions to view distribution.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($distribution as $session)
                <div class="card overflow-hidden">
                    <div class="bg-indigo-600 px-4 py-3 flex items-center justify-between">
                        <span class="text-white font-bold">Session {{ $session['session_number'] }}</span>
                        <span class="text-indigo-100 text-sm font-medium">{{ $session['start_time'] }} - {{ $session['end_time'] }}</span>
                    </div>
                    <div class="card-body">
                        <div class="flex items-end justify-between mb-4">
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold">Total Students</p>
                                <h4 class="text-2xl font-bold text-gray-900">{{ $session['total_allocated'] }}</h4>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            @foreach($session['halls'] as $hall)
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-sm font-medium text-gray-700">{{ $hall['hall'] }}</span>
                                    <span class="text-sm font-bold text-gray-900">{{ $hall['count'] }}</span>
                                </div>
                                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                    @php 
                                        $percent = $session['total_allocated'] > 0 ? ($hall['count'] / $session['total_allocated']) * 100 : 0;
                                    @endphp
                                    <div class="h-full bg-indigo-500 rounded-full" style="width: {{ $percent }}%"></div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-layouts.app>
