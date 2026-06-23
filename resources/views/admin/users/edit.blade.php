<x-layouts.app :title="'Edit User'">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.users.index') }}" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></a>
            <h1 class="text-2xl font-bold text-gray-900">Edit {{ $user->name }}</h1>
        </div>
    </x-slot>

    <div class="max-w-6xl space-y-6">
        <div class="card">
            <div class="card-body">
                <form id="update-user-form" action="{{ route('admin.users.update', $user) }}" method="POST" class="space-y-5">
                    @csrf @method('PUT')
                    <x-form-error-summary />

                    <div>
                        <label for="name" class="form-label">Full Name</label>
                        <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" class="form-input-styled" required>
                        @error('name') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="email" class="form-label">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="form-input-styled" required>
                            @error('email') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="phone" class="form-label">Phone</label>
                            <input id="phone" type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="form-input-styled">
                        </div>
                    </div>
                    <div>
                        <label for="role" class="form-label">Role</label>
                        <select id="role" name="role" class="form-input-styled" required>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" {{ $user->hasRole($role->name) ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $role->name)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="is_active" class="text-sm text-gray-700">Account is active</label>
                    </div>
                    <div class="flex justify-between pt-4">
                        <button type="submit" form="delete-user-form" class="btn btn-danger btn-sm" onclick="return confirm('Delete this user?')">Delete</button>
                        <div class="flex gap-3">
                            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </div>
                </form>
                <form id="delete-user-form" action="{{ route('admin.users.destroy', $user) }}" method="POST" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            </div>
        </div>

        @include('admin.users._student-context', [
            'user' => $user,
            'registeredExams' => $registeredExams,
            'examAllocations' => $examAllocations,
        ])
    </div>
</x-layouts.app>
