<x-layouts.app :title="'Academic Structure'">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">Academic Structure</h1>
        <p class="text-gray-500 mt-1">Manage faculties and departments used for students and courses.</p>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <form method="POST" action="{{ route('admin.faculties.store') }}" class="card">
            @csrf
            <div class="card-body space-y-4">
                <h2 class="font-bold text-gray-900">Add Faculty</h2>
                <x-form-error-summary />

                <div>
                    <label for="faculty_name" class="form-label">Faculty Name</label>
                    <input id="faculty_name" name="name" class="form-input-styled" placeholder="e.g. Engineering" required>
                    @error('name') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="faculty_code" class="form-label">Faculty Code</label>
                    <input id="faculty_code" name="code" class="form-input-styled uppercase" placeholder="e.g. ENG" required>
                    @error('code') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="btn btn-primary w-full sm:w-auto">Create Faculty</button>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.departments.store') }}" class="card">
            @csrf
            <div class="card-body space-y-4">
                <h2 class="font-bold text-gray-900">Add Department</h2>
                <x-form-error-summary />

                <div>
                    <label for="department_faculty_id" class="form-label">Faculty</label>
                    <select id="department_faculty_id" name="faculty_id" class="form-input-styled" required>
                        <option value="">Select faculty...</option>
                        @foreach($faculties as $faculty)
                            <option value="{{ $faculty->id }}">{{ $faculty->name }} ({{ $faculty->code }})</option>
                        @endforeach
                    </select>
                    @error('faculty_id') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="department_name" class="form-label">Department Name</label>
                    <input id="department_name" name="name" class="form-input-styled" placeholder="e.g. Computer Science" required>
                    @error('name') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="department_code" class="form-label">Department Code</label>
                    <input id="department_code" name="code" class="form-input-styled uppercase" placeholder="e.g. CSE" required>
                    @error('code') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="btn btn-primary w-full sm:w-auto">Create Department</button>
            </div>
        </form>
    </div>

    <div class="space-y-4 mt-6">
        @forelse($faculties as $faculty)
            <div class="card">
                <div class="card-body">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="font-bold text-gray-900">{{ $faculty->name }} ({{ $faculty->code }})</h2>
                        <form method="POST" action="{{ route('admin.faculties.destroy', $faculty) }}" onsubmit="return confirm('Delete this faculty?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Delete Faculty</button>
                        </form>
                    </div>

                    <div class="mt-4 divide-y divide-gray-100">
                        @forelse($faculty->departments as $department)
                            <div class="py-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <span class="text-sm text-gray-700">{{ $department->name }} ({{ $department->code }})</span>
                                <form method="POST" action="{{ route('admin.departments.destroy', $department) }}" onsubmit="return confirm('Delete this department?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-700">Delete</button>
                                </form>
                            </div>
                        @empty
                            <p class="py-3 text-sm text-gray-400">No departments.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @empty
            <div class="card">
                <div class="card-body text-sm text-gray-500">
                    No faculties have been created yet.
                </div>
            </div>
        @endforelse
    </div>
</x-layouts.app>
