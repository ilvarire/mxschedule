<x-layouts.app :title="'User Details'">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $user->email }}</p>
    </x-slot>

    <div class="max-w-6xl space-y-6">
        <div class="card">
            <div class="card-body space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Role</p>
                        <p class="mt-1 font-semibold text-gray-900">{{ $user->roles->pluck('name')->map(fn ($role) => ucwords(str_replace('_', ' ', $role)))->join(', ') ?: 'Unassigned' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Status</p>
                        <p class="mt-1">
                            <span class="badge {{ $user->is_active ? 'badge-green' : 'badge-red' }}">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Phone</p>
                        <p class="mt-1 font-semibold text-gray-900">{{ $user->phone ?? 'Not provided' }}</p>
                    </div>
                </div>

                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary">Edit User</a>
            </div>
        </div>

        @include('admin.users._student-context', [
            'user' => $user,
            'registeredExams' => $registeredExams,
            'examAllocations' => $examAllocations,
        ])
    </div>
</x-layouts.app>
