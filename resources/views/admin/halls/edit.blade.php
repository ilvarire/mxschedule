<x-layouts.app :title="'Edit ' . $hall->name">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.halls.show', $hall) }}" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></a>
            <h1 class="text-2xl font-bold text-gray-900">Edit {{ $hall->name }}</h1>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <div class="card">
            <div class="card-body">
                <form id="update-hall-form" action="{{ route('admin.halls.update', $hall) }}" method="POST" class="space-y-5">
                    @csrf @method('PUT')
                    <div>
                        <label for="name" class="form-label">Hall Name</label>
                        <input id="name" type="text" name="name" value="{{ old('name', $hall->name) }}" class="form-input-styled" required>
                        @error('name') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="code" class="form-label">Hall Code</label>
                            <input id="code" type="text" name="code" value="{{ old('code', $hall->code) }}" class="form-input-styled" required maxlength="10">
                            @error('code') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="capacity" class="form-label">Max Capacity</label>
                            <input id="capacity" type="number" name="capacity" value="{{ old('capacity', $hall->capacity) }}" class="form-input-styled" min="1" required>
                            @error('capacity') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label for="location" class="form-label">Location</label>
                        <input id="location" type="text" name="location" value="{{ old('location', $hall->location) }}" class="form-input-styled" placeholder="e.g. Building 3, Ground Floor">
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $hall->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="is_active" class="text-sm text-gray-700">Hall is active</label>
                    </div>
                </form>

                <div class="flex justify-between pt-4">
                    <form action="{{ route('admin.halls.destroy', $hall) }}" method="POST" onsubmit="return confirm('Delete this hall and all its systems?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Delete Hall</button>
                    </form>

                    <div class="flex gap-3">
                        <a href="{{ route('admin.halls.show', $hall) }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" form="update-hall-form" class="btn btn-primary">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
