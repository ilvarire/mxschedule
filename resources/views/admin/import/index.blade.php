<x-layouts.app :title="'CSV Import'">
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">Import Data</h1>
        <p class="text-gray-500 mt-1">Bulk import students and course enrollments via CSV</p>
    </x-slot>

    {{-- Import Results --}}
    @if(session('import_results'))
        @php $r = session('import_results'); @endphp
        <div class="card mb-6">
            <div class="card-body">
                <h3 class="text-lg font-semibold mb-3 {{ $r['success'] ? 'text-green-700' : 'text-red-700' }}">
                    {{ $r['success'] ? '✓ Import Complete' : '✗ Import Failed' }}
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                    <div class="text-center p-3 bg-green-50 rounded-lg">
                        <div class="text-2xl font-bold text-green-700">{{ $r['imported'] }}</div>
                        <div class="text-xs text-green-600">New Records</div>
                    </div>
                    <div class="text-center p-3 bg-blue-50 rounded-lg">
                        <div class="text-2xl font-bold text-blue-700">{{ $r['updated'] }}</div>
                        <div class="text-xs text-blue-600">Updated</div>
                    </div>
                    <div class="text-center p-3 bg-red-50 rounded-lg">
                        <div class="text-2xl font-bold text-red-700">{{ $r['skipped'] }}</div>
                        <div class="text-xs text-red-600">Skipped</div>
                    </div>
                </div>
                @if(count($r['errors']) > 0)
                    <details class="mt-3">
                        <summary class="cursor-pointer text-sm font-medium text-red-600 hover:text-red-700">
                            {{ count($r['errors']) }} error(s) — click to expand
                        </summary>
                        <ul class="mt-2 text-sm text-red-600 space-y-1 max-h-48 overflow-y-auto">
                            @foreach($r['errors'] as $error)
                                <li class="flex items-start gap-2">
                                    <span class="text-red-400 mt-0.5">•</span>
                                    <span>{{ $error }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Upload Form --}}
        <div class="card">
            <div class="card-header">
                <h2 class="text-lg font-semibold text-gray-900">Upload CSV</h2>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.import.process') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                    @csrf
                    <x-form-error-summary />

                    {{-- Import Type --}}
                    <div>
                        <label for="import_type" class="form-label">Import Type</label>
                        <select id="import_type" name="import_type" class="form-input-styled" onchange="toggleInfo(this.value)">
                            <option value="students">Students (full import)</option>
                            <option value="enrollments">Course Enrollments (existing students)</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="academic_session" class="form-label">Academic Session</label>
                            <input id="academic_session" type="text" name="academic_session" value="{{ old('academic_session', \App\Models\Setting::getValue('academic_session', '2025/2026')) }}" class="form-input-styled" required>
                        </div>
                        <div>
                            <label for="semester" class="form-label">Semester</label>
                            <select id="semester" name="semester" class="form-input-styled" required>
                                <option value="first" {{ old('semester', \App\Models\Setting::getValue('current_semester', 'first')) === 'first' ? 'selected' : '' }}>First Semester</option>
                                <option value="second" {{ old('semester', \App\Models\Setting::getValue('current_semester', 'first')) === 'second' ? 'selected' : '' }}>Second Semester</option>
                            </select>
                        </div>
                    </div>

                    {{-- File Upload Zone --}}
                    <div>
                        <label for="csvFile" class="form-label">CSV File</label>
                        <div id="dropZone"
                             class="relative border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-indigo-400 hover:bg-indigo-50/50 transition-all cursor-pointer"
                             onclick="document.getElementById('csvFile').click()">
                            <svg class="w-10 h-10 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            <p class="text-sm font-medium text-gray-600" id="fileLabel">
                                Drop your CSV file here or <span class="text-indigo-600 font-semibold">browse</span>
                            </p>
                            <p class="text-xs text-gray-400 mt-1">CSV files up to 10MB</p>
                            <input type="file" name="csv_file" id="csvFile" accept=".csv,.txt" required class="hidden">
                        </div>
                        @error('csv_file')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-full">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Import Data
                    </button>
                </form>
            </div>
        </div>

        {{-- CSV Format Guide --}}
        <div class="card">
            <div class="card-header">
                <h2 class="text-lg font-semibold text-gray-900">CSV Format Guide</h2>
            </div>
            <div class="card-body space-y-6">
                {{-- Students Template --}}
                <div id="info-students">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-gray-800">Students Import</h3>
                        <a href="{{ route('admin.import.template', 'students') }}" class="btn btn-sm btn-secondary">
                            ↓ Download Template
                        </a>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 font-mono text-xs overflow-x-auto">
                        <div class="text-gray-500 mb-1">name,email,matric_number,department_code,level,courses</div>
                        <div class="text-gray-800">John Doe,john@uni.edu,CSE/2024/001,CSE,300,CSE301|CSE401</div>
                        <div class="text-gray-800">Jane Doe,jane@uni.edu,EEE/2024/002,EEE,200,EEE201</div>
                    </div>
                    <ul class="mt-3 text-xs text-gray-500 space-y-1">
                        <li>• <strong>courses</strong> column is optional — use pipe | to separate multiple course codes</li>
                        <li>• <strong>department_code</strong> must match an existing department code</li>
                        <li>• Existing students (by email) will be updated, not duplicated</li>
                        <li>• New students receive a secure password setup link by email</li>
                    </ul>
                </div>

                {{-- Enrollments Template --}}
                <div id="info-enrollments" style="display: none;">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-gray-800">Enrollment Import</h3>
                        <a href="{{ route('admin.import.template', 'enrollments') }}" class="btn btn-sm btn-secondary">
                            ↓ Download Template
                        </a>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 font-mono text-xs overflow-x-auto">
                        <div class="text-gray-500 mb-1">matric_number,course_code</div>
                        <div class="text-gray-800">CSE/2024/001,CSE301</div>
                        <div class="text-gray-800">CSE/2024/001,CSE401</div>
                    </div>
                    <ul class="mt-3 text-xs text-gray-500 space-y-1">
                        <li>• Students must already exist in the system</li>
                        <li>• Course codes must match existing courses</li>
                        <li>• Duplicate enrollments are safely ignored</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        // File label update
        document.getElementById('csvFile').addEventListener('change', function(e) {
            const label = document.getElementById('fileLabel');
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                const size = (file.size / 1024).toFixed(1);
                label.innerHTML = `<span class="text-indigo-600 font-semibold">${file.name}</span> <span class="text-gray-400">(${size} KB)</span>`;
            }
        });

        // Drag and drop
        const zone = document.getElementById('dropZone');
        ['dragover', 'dragenter'].forEach(ev => {
            zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.add('border-indigo-400', 'bg-indigo-50'); });
        });
        ['dragleave', 'drop'].forEach(ev => {
            zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.remove('border-indigo-400', 'bg-indigo-50'); });
        });
        zone.addEventListener('drop', e => {
            const dt = e.dataTransfer;
            if (dt.files.length > 0) {
                document.getElementById('csvFile').files = dt.files;
                document.getElementById('csvFile').dispatchEvent(new Event('change'));
            }
        });

        // Toggle info panels
        function toggleInfo(type) {
            document.getElementById('info-students').style.display = type === 'students' ? '' : 'none';
            document.getElementById('info-enrollments').style.display = type === 'enrollments' ? '' : 'none';
        }
    </script>
</x-layouts.app>
