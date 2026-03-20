<x-layouts.app :title="'Systems'">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">All Systems</h1>
        <p class="text-sm text-gray-500 mt-1">Overview of all computer systems across halls</p>
    </x-slot>

    <div class="card">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr><th>System Code</th><th>Hall</th><th>Status</th><th>Last Used</th><th>Action</th></tr>
                </thead>
                <tbody>
                    @forelse($systems as $system)
                    <tr>
                        <td class="font-mono font-semibold">{{ $system->system_code }}</td>
                        <td>
                            <a href="{{ route('admin.halls.show', $system->hall) }}" class="text-indigo-600 hover:underline">{{ $system->hall->name }}</a>
                        </td>
                        <td><span class="badge badge-{{ $system->status->color() }}">{{ $system->status->label() }}</span></td>
                        <td>{{ $system->last_used_at?->diffForHumans() ?? '—' }}</td>
                        <td>
                            <form action="{{ route('admin.systems.update-status', $system) }}" method="POST" class="inline-flex items-center gap-2">
                                @csrf @method('PATCH')
                                <select name="status" class="form-input-styled text-xs py-1 w-24">
                                    <option value="active" {{ $system->status->value === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ $system->status->value === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    <option value="faulty" {{ $system->status->value === 'faulty' ? 'selected' : '' }}>Faulty</option>
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary">Update</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-gray-400 py-8">No systems configured. <a href="{{ route('admin.halls.index') }}" class="text-indigo-600 hover:underline">Add systems to a hall</a></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $systems->links() }}</div>
</x-layouts.app>
