<x-layouts.app :title="'Academic Structure'">
    <x-slot name="header"><h1 class="text-2xl font-bold text-gray-900">Academic Structure</h1></x-slot>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <form method="POST" action="{{ route('admin.faculties.store') }}" class="card"><div class="card-body space-y-3">
            @csrf <h2 class="font-bold">Add Faculty</h2>
            <input name="name" class="form-input-styled" placeholder="Faculty name" required>
            <input name="code" class="form-input-styled" placeholder="Code" required>
            <button class="btn btn-primary">Create Faculty</button>
        </div></form>
        <form method="POST" action="{{ route('admin.departments.store') }}" class="card"><div class="card-body space-y-3">
            @csrf <h2 class="font-bold">Add Department</h2>
            <select name="faculty_id" class="form-input-styled" required><option value="">Select faculty...</option>@foreach($faculties as $faculty)<option value="{{ $faculty->id }}">{{ $faculty->name }}</option>@endforeach</select>
            <input name="name" class="form-input-styled" placeholder="Department name" required>
            <input name="code" class="form-input-styled" placeholder="Code" required>
            <button class="btn btn-primary">Create Department</button>
        </div></form>
    </div>
    <div class="space-y-4 mt-6">
        @foreach($faculties as $faculty)
        <div class="card"><div class="card-body">
            <div class="flex justify-between"><h2 class="font-bold">{{ $faculty->name }} ({{ $faculty->code }})</h2><form method="POST" action="{{ route('admin.faculties.destroy', $faculty) }}">@csrf @method('DELETE')<button class="text-red-600 text-sm">Delete</button></form></div>
            <div class="mt-3 space-y-2">@forelse($faculty->departments as $department)<div class="flex justify-between border-t pt-2"><span>{{ $department->name }} ({{ $department->code }})</span><form method="POST" action="{{ route('admin.departments.destroy', $department) }}">@csrf @method('DELETE')<button class="text-red-600 text-sm">Delete</button></form></div>@empty<p class="text-sm text-gray-400">No departments.</p>@endforelse</div>
        </div></div>
        @endforeach
    </div>
</x-layouts.app>
