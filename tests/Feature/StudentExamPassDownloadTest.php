<?php

use App\Enums\ExamStatus;
use App\Enums\SeatStatus;
use App\Jobs\GenerateExamPassPdfJob;
use App\Models\Course;
use App\Models\Department;
use App\Models\Exam;
use App\Models\ExamAllocation;
use App\Models\ExamPass;
use App\Models\ExamSession;
use App\Models\Faculty;
use App\Models\Hall;
use App\Models\StudentProfile;
use App\Models\System as ComputerSystem;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

function studentExamPassFixture(): array
{
    $studentRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($studentRole);

    $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $department = Department::create(['faculty_id' => $faculty->id, 'name' => 'Computer Science', 'code' => 'CSE']);
    $course = Course::create(['department_id' => $department->id, 'code' => 'CSE301', 'title' => 'Data Structures', 'credit_units' => 3]);
    $profile = StudentProfile::create([
        'user_id' => $user->id,
        'matric_number' => 'CSE/2026/001',
        'department_id' => $department->id,
        'level' => 300,
    ]);
    $exam = Exam::create([
        'course_id' => $course->id,
        'academic_session' => '2025/2026',
        'semester' => 'first',
        'exam_date' => now()->addWeek()->toDateString(),
        'start_time' => '09:00',
        'duration_minutes' => 60,
        'buffer_minutes' => 15,
        'status' => ExamStatus::Scheduled,
    ]);
    $session = ExamSession::create([
        'exam_id' => $exam->id,
        'session_number' => 1,
        'start_time' => now()->addWeek()->setTime(9, 0),
        'end_time' => now()->addWeek()->setTime(10, 0),
        'max_capacity' => 50,
        'allocated_count' => 1,
        'status' => 'pending',
    ]);
    $hall = Hall::create(['name' => 'Hall C', 'code' => 'HC', 'capacity' => 50, 'is_active' => true]);
    $system = ComputerSystem::create(['hall_id' => $hall->id, 'system_code' => 'HC01', 'status' => 'active']);
    $allocation = ExamAllocation::create([
        'exam_session_id' => $session->id,
        'student_profile_id' => $profile->id,
        'system_id' => $system->id,
        'hall_id' => $hall->id,
        'seat_status' => SeatStatus::Allocated,
    ]);

    return compact('allocation', 'exam', 'profile', 'user');
}

test('student download queues pdf generation when cached pdf is missing', function () {
    Queue::fake();
    Storage::fake('public');
    $fixture = studentExamPassFixture();

    ExamPass::create([
        'exam_allocation_id' => $fixture['allocation']->id,
        'pass_code' => 'pass-code',
        'qr_payload' => 'signed-payload',
        'expires_at' => now()->addWeek(),
    ]);

    $this
        ->actingAs($fixture['user'])
        ->get(route('student.exam-pass.download', $fixture['allocation']))
        ->assertRedirect()
        ->assertSessionHas('success');

    Queue::assertPushed(GenerateExamPassPdfJob::class);
});

test('student can download an existing cached exam pass pdf', function () {
    Queue::fake();
    Storage::fake('public');
    $fixture = studentExamPassFixture();
    Storage::disk('public')->put('exam-passes/pass.pdf', 'PDF content');

    ExamPass::create([
        'exam_allocation_id' => $fixture['allocation']->id,
        'pass_code' => 'pass-code',
        'qr_payload' => 'signed-payload',
        'pdf_path' => 'exam-passes/pass.pdf',
        'expires_at' => now()->addWeek(),
    ]);

    $this
        ->actingAs($fixture['user'])
        ->get(route('student.exam-pass.download', $fixture['allocation']))
        ->assertOk()
        ->assertDownload('exam-pass-CSE-2026-001.pdf');

    Queue::assertNothingPushed();
});
