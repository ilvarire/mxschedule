<x-layouts.app :title="'Users'">
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Users</h1>
                <p class="text-sm text-gray-500 mt-1">Manage system users and role assignments</p>
            </div>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add User
            </a>
        </div>
    </x-slot>

    <div class="card">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr>
                        <td class="font-medium">{{ $user->name }}</td>
                        <td class="text-gray-500">{{ $user->email }}</td>
                        <td>
                            @foreach($user->roles as $role)
                                <span class="badge badge-blue">{{ str_replace('_', ' ', $role->name) }}</span>
                            @endforeach
                        </td>
                        <td>
                            <span class="badge {{ $user->is_active ? 'badge-green' : 'badge-red' }}">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-sm text-gray-500">{{ $user->created_at->format('M j, Y') }}</td>
                        <td><a href="{{ route('admin.users.edit', $user) }}" class="text-indigo-600 hover:underline text-sm">Edit</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
</x-layouts.app>
