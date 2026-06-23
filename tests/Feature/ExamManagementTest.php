<?php

use App\Enums\ExamStatus;
use App\Jobs\GenerateExamScheduleJob;
use App\Jobs\SendScheduleNotificationsJob;
use App\Models\Course;
use App\Models\Department;
use App\Models\Exam;
use App\Models\Faculty;
use App\Models\Hall;
use App\Models\StudentProfile;
use App\Models\System as ComputerSystem;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function examManagementAdmin(): User
{
    $permission = Permission::firstOrCreate(['name' => 'edit_exams', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'exam_officer', 'guard_name' => 'web']);
    $role->syncPermissions([$permission]);

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function examManagementCourse(): Course
{
    $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $department = Department::create(['faculty_id' => $faculty->id, 'name' => 'Mathematics', 'code' => 'MTH']);

    return Course::create([
        'department_id' => $department->id,
        'code' => 'MTH101',
        'title' => 'Calculus',
        'credit_units' => 3,
    ]);
}

test('exam edit validation errors are visible after failed save', function () {
    $admin = examManagementAdmin();
    $course = examManagementCourse();
    $exam = Exam::create([
        'course_id' => $course->id,
        'academic_session' => '2025/2026',
        'semester' => 'first',
        'exam_date' => now()->addWeek()->toDateString(),
        'start_time' => '09:00',
        'duration_minutes' => 60,
        'buffer_minutes' => 15,
        'status' => ExamStatus::Draft,
    ]);

    $response = $this
        ->actingAs($admin)
        ->from(route('admin.exams.edit', $exam))
        ->put(route('admin.exams.update', $exam), [
            'course_id' => $course->id,
            'academic_session' => '',
            'semester' => 'first',
            'exam_date' => now()->addWeek()->toDateString(),
            'start_time' => '09:00',
            'duration_minutes' => 60,
            'buffer_minutes' => 15,
        ]);

    $response
        ->assertRedirect(route('admin.exams.edit', $exam))
        ->assertSessionHasErrors('academic_session');

    $this
        ->actingAs($admin)
        ->get(route('admin.exams.edit', $exam))
        ->assertSee('Please fix the following issue')
        ->assertSee('academic session field is required', false);
});

test('exam edit accepts database-style start time with seconds', function () {
    $admin = examManagementAdmin();
    $course = examManagementCourse();
    $exam = Exam::create([
        'course_id' => $course->id,
        'academic_session' => '2025/2026',
        'semester' => 'first',
        'exam_date' => now()->addWeek()->toDateString(),
        'start_time' => '09:00:00',
        'duration_minutes' => 60,
        'buffer_minutes' => 15,
        'status' => ExamStatus::Draft,
    ]);

    $response = $this
        ->actingAs($admin)
        ->put(route('admin.exams.update', $exam), [
            'course_id' => $course->id,
            'academic_session' => '2025/2026',
            'semester' => 'first',
            'exam_date' => now()->addWeek()->toDateString(),
            'start_time' => '09:00:00',
            'duration_minutes' => 90,
            'buffer_minutes' => 15,
        ]);

    $response
        ->assertRedirect(route('admin.exams.show', $exam))
        ->assertSessionHasNoErrors();

    expect($exam->refresh()->start_time)->toBe('09:00');
});

test('exam creation counts students enrolled for the same course session and semester', function () {
    $admin = examManagementAdmin();
    $course = examManagementCourse();
    $studentUser = User::factory()->create();
    $profile = StudentProfile::create([
        'user_id' => $studentUser->id,
        'matric_number' => 'MTH/2026/001',
        'department_id' => $course->department_id,
        'level' => 300,
    ]);
    $profile->courses()->attach($course->id, [
        'academic_session' => '2025/2026',
        'semester' => 'second',
    ]);

    $response = $this
        ->actingAs($admin)
        ->post(route('admin.exams.store'), [
            'course_id' => $course->id,
            'academic_session' => ' 2025/2026 ',
            'semester' => ' second ',
            'exam_date' => now()->addWeek()->toDateString(),
            'start_time' => '09:00',
            'duration_minutes' => 60,
            'buffer_minutes' => 15,
        ]);

    $exam = Exam::latest('id')->first();

    $response
        ->assertRedirect(route('admin.exams.show', $exam))
        ->assertSessionHasNoErrors();

    expect($exam->total_registered_students)->toBe(1)
        ->and($exam->academic_session)->toBe('2025/2026')
        ->and($exam->semester->value)->toBe('second');
});

test('schedule generation immediately marks exam as scheduling while queued', function () {
    Queue::fake();

    $permission = Permission::firstOrCreate(['name' => 'trigger_scheduling', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'exam_officer', 'guard_name' => 'web']);
    $role->givePermissionTo($permission);
    $admin = User::factory()->create();
    $admin->assignRole($role);

    $course = examManagementCourse();
    $studentUser = User::factory()->create();
    $profile = StudentProfile::create([
        'user_id' => $studentUser->id,
        'matric_number' => 'MTH/2026/002',
        'department_id' => $course->department_id,
        'level' => 300,
    ]);
    $profile->courses()->attach($course->id, [
        'academic_session' => '2025/2026',
        'semester' => 'first',
    ]);

    $hall = Hall::create(['name' => 'Main CBT', 'code' => 'MCBT', 'capacity' => 10, 'is_active' => true]);
    ComputerSystem::create(['hall_id' => $hall->id, 'system_code' => 'MCBT01', 'status' => 'active']);

    $exam = Exam::create([
        'course_id' => $course->id,
        'academic_session' => '2025/2026',
        'semester' => 'first',
        'exam_date' => now()->addWeek()->toDateString(),
        'start_time' => '09:00',
        'duration_minutes' => 60,
        'buffer_minutes' => 15,
        'status' => ExamStatus::Draft,
        'total_registered_students' => 1,
    ]);

    $this
        ->actingAs($admin)
        ->post(route('admin.exams.schedule', $exam))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($exam->refresh()->status)->toBe(ExamStatus::Scheduling);

    Queue::assertPushed(GenerateExamScheduleJob::class);

    $this
        ->actingAs($admin)
        ->get(route('admin.exams.show', $exam))
        ->assertOk()
        ->assertSee('Schedule generation is running or waiting in the queue')
        ->assertSee('Schedule Generation In Progress');
});

test('authorized admins can resend schedule notifications for scheduled exams', function () {
    Queue::fake();

    $permission = Permission::firstOrCreate(['name' => 'send_notifications', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'exam_officer', 'guard_name' => 'web']);
    $role->givePermissionTo($permission);
    $admin = User::factory()->create();
    $admin->assignRole($role);

    $course = examManagementCourse();
    $exam = Exam::create([
        'course_id' => $course->id,
        'academic_session' => '2025/2026',
        'semester' => 'first',
        'exam_date' => now()->addWeek()->toDateString(),
        'start_time' => '09:00',
        'duration_minutes' => 60,
        'buffer_minutes' => 15,
        'status' => ExamStatus::Scheduled,
        'total_registered_students' => 1,
    ]);

    $this
        ->actingAs($admin)
        ->post(route('admin.exams.notify', $exam))
        ->assertRedirect()
        ->assertSessionHas('success');

    Queue::assertPushed(SendScheduleNotificationsJob::class);
});
