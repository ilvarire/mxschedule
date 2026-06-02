<x-layouts.app :title="'Halls'">
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Exam Halls</h1>
                <p class="text-sm text-gray-500 mt-1">Manage halls and their computer systems</p>
            </div>
            @can('manage_halls')
            <a href="{{ route('admin.halls.create') }}" class="btn btn-primary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Hall
            </a>
            @endcan
        </div>
    </x-slot>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($halls as $hall)
        <a href="{{ route('admin.halls.show', $hall) }}" class="card hover:shadow-lg transition-shadow group">
            <div class="card-body">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors">{{ $hall->name }}</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Code: {{ $hall->code }}</p>
                    </div>
                    <span class="badge {{ $hall->is_active ? 'badge-green' : 'badge-gray' }}">
                        {{ $hall->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                @if($hall->location)
                    <p class="text-sm text-gray-500 mt-2">{{ $hall->location }}</p>
                @endif
                <div class="mt-4 flex items-center gap-4 text-sm">
                    <div>
                        <span class="text-2xl font-bold text-gray-900">{{ $hall->active_systems_count }}</span>
                        <span class="text-gray-500 text-xs">/ {{ $hall->systems_count }} systems</span>
                    </div>
                    <div class="flex-1 bg-gray-200 rounded-full h-2">
                        <div class="bg-emerald-500 h-2 rounded-full transition-all duration-500"
                             style="width: {{ $hall->systems_count > 0 ? ($hall->active_systems_count / $hall->systems_count) * 100 : 0 }}%"></div>
                    </div>
                </div>
            </div>
        </a>
        @empty
        <div class="col-span-full text-center py-16">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            <p class="text-gray-500 mb-4">No halls created yet</p>
            <a href="{{ route('admin.halls.create') }}" class="btn btn-primary">Add Your First Hall</a>
        </div>
        @endforelse
    </div>
</x-layouts.app>
