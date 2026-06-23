<?php

use App\Enums\ExamStatus;
use App\Models\Course;
use App\Models\Department;
use App\Models\Exam;
use App\Models\Faculty;
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
