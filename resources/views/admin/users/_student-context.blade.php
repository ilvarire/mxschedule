@php
    $profile = $user->studentProfile;
@endphp

<div class="card">
    <div class="card-body space-y-6">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Student Information</h2>
            <p class="text-sm text-gray-500 mt-1">Academic profile, course registrations, and exam activity for this account.</p>
        </div>

        @if(! $profile)
            <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-500">
                This user does not have a student profile attached to the account.
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Matric Number</p>
                    <p class="mt-1 font-semibold text-gray-900">{{ $profile->matric_number }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Department</p>
                    <p class="mt-1 font-semibold text-gray-900">{{ $profile->department?->name ?? 'Not assigned' }}</p>
                    @if($profile->department?->code)
                        <p class="text-xs text-gray-500">{{ $profile->department->code }}</p>
                    @endif
                </div>
                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Faculty</p>
                    <p class="mt-1 font-semibold text-gray-900">{{ $profile->department?->faculty?->name ?? 'Not assigned' }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Level</p>
                    <p class="mt-1 font-semibold text-gray-900">{{ $profile->level }} Level</p>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between gap-3 mb-3">
                    <h3 class="font-semibold text-gray-900">Course Registrations</h3>
                    <span class="badge badge-blue">{{ $profile->courses->count() }} course(s)</span>
                </div>

                @if($profile->courses->isEmpty())
                    <p class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-500">
                        No course registration has been recorded for this student.
                    </p>
                @else
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Department</th>
                                    <th>Session</th>
                                    <th>Semester</th>
                                    <th>Units</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($profile->courses->sortBy('code') as $course)
                                    <tr>
                                        <td>
                                            <div class="font-medium text-gray-900">{{ $course->code }}</div>
                                            <div class="text-xs text-gray-500">{{ $course->title }}</div>
                                        </td>
                                        <td>{{ $course->department?->name ?? 'N/A' }}</td>
                                        <td>{{ $course->pivot->academic_session }}</td>
                                        <td>{{ ucfirst($course->pivot->semester) }}</td>
                                        <td>{{ $course->credit_units }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div>
                <div class="flex items-center justify-between gap-3 mb-3">
                    <h3 class="font-semibold text-gray-900">Registered Exams</h3>
                    <span class="badge badge-blue">{{ $registeredExams->count() }} exam(s)</span>
                </div>

                @if($registeredExams->isEmpty())
                    <p class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-500">
                        No exam currently matches this student's registered courses, session, and semester.
                    </p>
                @else
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Exam</th>
                                    <th>Date & Time</th>
                                    <th>Session</th>
                                    <th>Status</th>
                                    <th>Allocation</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($registeredExams as $exam)
                                    @php
                                        $allocation = $examAllocations->first(fn ($item) => $item->examSession?->exam_id === $exam->id);
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="font-medium text-gray-900">{{ $exam->course->code }}</div>
                                            <div class="text-xs text-gray-500">{{ $exam->course->title }}</div>
                                        </td>
                                        <td>
                                            <div>{{ $exam->exam_date->format('M j, Y') }}</div>
                                            <div class="text-xs text-gray-500">{{ $exam->start_time }}</div>
                                        </td>
                                        <td>
                                            <div>{{ $exam->academic_session }}</div>
                                            <div class="text-xs text-gray-500">{{ $exam->semester->label() }}</div>
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $exam->status->color() }}">{{ $exam->status->label() }}</span>
                                        </td>
                                        <td>
                                            @if($allocation)
                                                <div class="text-sm text-gray-900">{{ $allocation->hall?->name ?? 'Hall pending' }}</div>
                                                <div class="text-xs text-gray-500">
                                                    {{ $allocation->system?->system_code ?? 'System pending' }}
                                                    · Session {{ $allocation->examSession?->session_number ?? 'N/A' }}
                                                </div>
                                            @else
                                                <span class="text-sm text-gray-400">Not allocated yet</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div>
                <div class="flex items-center justify-between gap-3 mb-3">
                    <h3 class="font-semibold text-gray-900">Allocation History</h3>
                    <span class="badge badge-blue">{{ $examAllocations->count() }} allocation(s)</span>
                </div>

                @if($examAllocations->isEmpty())
                    <p class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-500">
                        This student has not been allocated to any exam session yet.
                    </p>
                @else
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Session Time</th>
                                    <th>Seat</th>
                                    <th>Attendance</th>
                                    <th>Pass</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($examAllocations as $allocation)
                                    <tr>
                                        <td>
                                            <div class="font-medium text-gray-900">{{ $allocation->examSession?->exam?->course?->code ?? 'N/A' }}</div>
                                            <div class="text-xs text-gray-500">{{ $allocation->examSession?->exam?->course?->title ?? 'Exam unavailable' }}</div>
                                        </td>
                                        <td>
                                            @if($allocation->examSession)
                                                <div>{{ $allocation->examSession->start_time->format('M j, Y') }}</div>
                                                <div class="text-xs text-gray-500">
                                                    {{ $allocation->examSession->start_time->format('g:i A') }} - {{ $allocation->examSession->end_time->format('g:i A') }}
                                                </div>
                                            @else
                                                <span class="text-sm text-gray-400">Session unavailable</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div>{{ $allocation->hall?->name ?? 'N/A' }}</div>
                                            <div class="text-xs text-gray-500">{{ $allocation->system?->system_code ?? 'N/A' }}</div>
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $allocation->seat_status->color() }}">{{ $allocation->seat_status->label() }}</span>
                                            @if($allocation->checked_in_at)
                                                <div class="text-xs text-gray-500 mt-1">{{ $allocation->checked_in_at->format('M j, Y g:i A') }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($allocation->examPass)
                                                <span class="badge {{ $allocation->examPass->is_used ? 'badge-green' : 'badge-blue' }}">
                                                    {{ $allocation->examPass->is_used ? 'Used' : 'Generated' }}
                                                </span>
                                            @else
                                                <span class="text-sm text-gray-400">Not generated</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
