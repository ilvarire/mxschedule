<?php

use App\Enums\ExamStatus;
use App\Models\Course;
use App\Models\Department;
use App\Models\Exam;
use App\Models\Faculty;
use App\Models\StudentProfile;
use App\Models\User;
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
