<x-layouts.app :title="'Add Course'">
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.courses.index') }}" class="text-gray-400 hover:text-gray-900">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Add Course</h1>
                <p class="text-sm text-gray-500 mt-1">Register a new course in the system</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.courses.store') }}" method="POST" class="space-y-6">
                    @csrf
                    
                    <div>
                        <x-input-label for="department_id" value="Department" />
                        <select id="department_id" name="department_id" class="form-input-styled mt-1" required>
                            <option value="">Select a department...</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }} ({{ $dept->code }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('department_id')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="code" value="Course Code" />
                            <x-text-input id="code" name="code" type="text" class="mt-1 block w-full uppercase" :value="old('code')" placeholder="e.g. CSE301" required />
                            <x-input-error :messages="$errors->get('code')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="credit_units" value="Credit Units" />
                            <x-text-input id="credit_units" name="credit_units" type="number" min="1" max="10" class="mt-1 block w-full" :value="old('credit_units')" placeholder="e.g. 3" required />
                            <x-input-error :messages="$errors->get('credit_units')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="title" value="Course Title" />
                        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title')" placeholder="e.g. Introduction to Software Engineering" required />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>

                    <div class="pt-4 border-t border-gray-100 flex justify-end gap-3">
                        <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Course</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>
