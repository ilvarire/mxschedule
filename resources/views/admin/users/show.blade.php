<x-layouts.app :title="'User Details'">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $user->email }}</p>
    </x-slot>

    <div class="card max-w-2xl">
        <div class="card-body space-y-3">
            <p><strong>Role:</strong> {{ $user->roles->first()?->name ?? 'Unassigned' }}</p>
            <p><strong>Status:</strong> {{ $user->is_active ? 'Active' : 'Inactive' }}</p>
            <p><strong>Phone:</strong> {{ $user->phone ?? 'Not provided' }}</p>
            @if($user->studentProfile)
                <p><strong>Matric number:</strong> {{ $user->studentProfile->matric_number }}</p>
            @endif
            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary">Edit User</a>
        </div>
    </div>
</x-layouts.app>
