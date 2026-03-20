<x-layouts.app :title="'Create Hall'">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.halls.index') }}" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></a>
            <h1 class="text-2xl font-bold text-gray-900">Create Hall</h1>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.halls.store') }}" method="POST" class="space-y-5">
                    @csrf
                    <div>
                        <label class="form-label">Hall Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-input-styled" placeholder="e.g. Computer Lab A" required>
                        @error('name') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Hall Code</label>
                            <input type="text" name="code" value="{{ old('code') }}" class="form-input-styled" placeholder="e.g. HA" required maxlength="10">
                            @error('code') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Max Capacity</label>
                            <input type="number" name="capacity" value="{{ old('capacity', 50) }}" class="form-input-styled" min="1" required>
                            @error('capacity') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Location <span class="text-gray-400">(optional)</span></label>
                        <input type="text" name="location" value="{{ old('location') }}" class="form-input-styled" placeholder="e.g. Building 3, Ground Floor">
                    </div>
                    <div class="flex justify-end gap-3 pt-4">
                        <a href="{{ route('admin.halls.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Create Hall</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>
