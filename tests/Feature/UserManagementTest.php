<?php

use App\Enums\ExamStatus;
use App\Enums\SeatStatus;
use App\Models\Course;
use App\Models\Department;
use App\Models\Exam;
use App\Models\ExamAllocation;
use App\Models\ExamSession;
use App\Models\Faculty;
use App\Models\Hall;
use App\Models\StudentProfile;
use App\Models\System as ComputerSystem;
use App\Models\User;
use Spatie\Permission\Models\Role;

function userManagementSuperAdmin(): User
{
    $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('super admin user edit page shows student academic and exam context', function () {
    $admin = userManagementSuperAdmin();
    $studentRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

    $faculty = Faculty::create(['name' => 'Engineering', 'code' => 'ENG']);
    $department = Department::create(['faculty_id' => $faculty->id, 'name' => 'Computer Science', 'code' => 'CSE']);
    $course = Course::create(['department_id' => $department->id, 'code' => 'CSE301', 'title' => 'Data Structures', 'credit_units' => 3]);
    $studentUser = User::factory()->create(['name' => 'Student Account', 'email' => 'student@mxschedule.test']);
    $studentUser->assignRole($studentRole);

    $profile = StudentProfile::create([
        'user_id' => $studentUser->id,
        'matric_number' => 'CSE/2026/001',
        'department_id' => $department->id,
        'level' => 300,
    ]);
    $profile->courses()->attach($course->id, ['academic_session' => '2025/2026', 'semester' => 'first']);

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
    $system = ComputerSystem::create(['hall_id' => $hall->id, 'system_code' => 'HC11', 'status' => 'active']);

    ExamAllocation::create([
        'exam_session_id' => $session->id,
        'student_profile_id' => $profile->id,
        'system_id' => $system->id,
        'hall_id' => $hall->id,
        'seat_status' => SeatStatus::Allocated,
    ]);

    $this
        ->actingAs($admin)
        ->get(route('admin.users.edit', $studentUser))
        ->assertOk()
        ->assertSee('Student Information')
        ->assertSee('CSE/2026/001')
        ->assertSee('Computer Science')
        ->assertSee('Engineering')
        ->assertSee('300 Level')
        ->assertSee('CSE301')
        ->assertSee('Data Structures')
        ->assertSee('Registered Exams')
        ->assertSee('Hall C')
        ->assertSee('HC11');
});
