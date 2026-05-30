<x-layouts.app :title="'Manage Courses'">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Manage Courses</h1>
                <p class="text-sm text-gray-500 mt-1">Add, edit, or delete university courses</p>
            </div>
            <a href="{{ route('admin.courses.create') }}" class="btn btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Add Course
            </a>
        </div>
    </x-slot>

    <div class="card">
        <div class="card-body p-0 overflow-x-auto">
            <table class="table min-w-full">
                <thead>
                    <tr>
                        <th class="w-1/4">Course Code</th>
                        <th class="w-1/3">Title</th>
                        <th class="w-1/6">Department</th>
                        <th class="w-1/6">Credits</th>
                        <th class="w-1/6">Exams</th>
                        <th class="w-24 relative">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($courses as $course)
                        <tr>
                            <td>
                                <span class="font-mono font-medium text-gray-900">{{ $course->code }}</span>
                            </td>
                            <td>
                                <span class="text-sm text-gray-700 font-medium">{{ $course->title }}</span>
                            </td>
                            <td>
                                <span class="text-sm text-gray-600">{{ $course->department->name ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <span class="text-sm text-gray-600">{{ $course->credit_units }} Units</span>
                            </td>
                            <td>
                                @if($course->exams_count > 0)
                                    <span class="badge badge-success">{{ $course->exams_count }} Exam(s)</span>
                                @else
                                    <span class="badge badge-gray">None</span>
                                @endif
                            </td>
                            <td class="text-right space-x-2">
                                <a href="{{ route('admin.courses.edit', $course) }}" class="text-indigo-600 hover:text-indigo-900 font-medium text-sm">Edit</a>
                                <form action="{{ route('admin.courses.destroy', $course) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this course?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 font-medium text-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-8 text-gray-500">
                                No courses found. <a href="{{ route('admin.courses.create') }}" class="text-indigo-600 hover:underline">Create the first course</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
