<x-layouts.app :title="$hall->name">
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <a href="{{ route('admin.halls.index') }}" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $hall->name }}</h1>
                    <p class="text-sm text-gray-500">Code: {{ $hall->code }} · Capacity: {{ $hall->capacity }} · {{ $hall->location ?? 'No location' }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.halls.edit', $hall) }}" class="btn btn-secondary btn-sm">Edit</a>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Bulk Add Systems -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-sm font-semibold text-gray-900">Add Systems</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.systems.bulk-create', $hall) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="form-label">Number of Systems</label>
                        <input type="number" name="count" value="10" class="form-input-styled" min="1" max="500" required>
                    </div>
                    <div>
                        <label class="form-label">Code Prefix <span class="text-gray-400">(default: {{ $hall->code }})</span></label>
                        <input type="text" name="prefix" class="form-input-styled" placeholder="{{ $hall->code }}" maxlength="10">
                    </div>
                    <button type="submit" class="btn btn-primary w-full">Bulk Create Systems</button>
                </form>
            </div>
        </div>

        <!-- Systems Grid -->
        <div class="card lg:col-span-2">
            <div class="card-header">
                <h3 class="text-sm font-semibold text-gray-900">Systems ({{ $hall->systems->count() }})</h3>
                <div class="flex items-center gap-4 text-xs text-gray-500">
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> {{ $hall->systems->where('status.value', 'active')->count() }} Active</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-gray-300"></span> {{ $hall->systems->where('status.value', 'inactive')->count() }} Inactive</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-red-500"></span> {{ $hall->systems->where('status.value', 'faulty')->count() }} Faulty</span>
                </div>
            </div>
            <div class="card-body">
                @if($hall->systems->isEmpty())
                    <p class="text-center text-gray-400 py-8">No systems yet. Use the form to add systems.</p>
                @else
                    <div class="flex flex-wrap gap-2 mb-6">
                        @foreach($hall->systems->sortBy('system_code') as $system)
                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = !open" class="system-dot {{ $system->status->value }}" title="{{ $system->system_code }}">
                                    {{ (int) preg_replace('/\D/', '', substr($system->system_code, strlen($hall->code))) }}
                                </button>
                                <div x-show="open" @click.away="open = false" x-transition
                                     class="absolute z-10 mt-2 w-56 bg-white rounded-lg shadow-xl border border-gray-100 p-4 -left-20">
                                    <p class="font-semibold text-sm text-gray-900 mb-2">{{ $system->system_code }}</p>
                                    <p class="text-xs text-gray-500 mb-3">Status: <span class="badge badge-{{ $system->status->color() }}">{{ $system->status->label() }}</span></p>
                                    <form action="{{ route('admin.systems.update-status', $system) }}" method="POST" class="space-y-2">
                                        @csrf @method('PATCH')
                                        <select name="status" class="form-input-styled text-xs">
                                            <option value="active" {{ $system->status->value === 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="inactive" {{ $system->status->value === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                            <option value="faulty" {{ $system->status->value === 'faulty' ? 'selected' : '' }}>Faulty</option>
                                        </select>
                                        <input type="text" name="reason" class="form-input-styled text-xs" placeholder="Reason (optional)">
                                        <button type="submit" class="btn btn-primary btn-sm w-full">Update</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Systems Table -->
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr><th>Code</th><th>Status</th><th>Last Used</th><th>Action</th></tr>
                            </thead>
                            <tbody>
                                @foreach($hall->systems->sortBy('system_code') as $system)
                                <tr>
                                    <td class="font-mono font-semibold">{{ $system->system_code }}</td>
                                    <td><span class="badge badge-{{ $system->status->color() }}">{{ $system->status->label() }}</span></td>
                                    <td>{{ $system->last_used_at?->diffForHumans() ?? '—' }}</td>
                                    <td>
                                        <form action="{{ route('admin.systems.update-status', $system) }}" method="POST" class="inline-flex gap-1">
                                            @csrf @method('PATCH')
                                            @if($system->status->value !== 'active')
                                                <input type="hidden" name="status" value="active">
                                                <button class="btn btn-sm btn-success">Activate</button>
                                            @else
                                                <input type="hidden" name="status" value="inactive">
                                                <button class="btn btn-sm btn-secondary">Disable</button>
                                            @endif
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
