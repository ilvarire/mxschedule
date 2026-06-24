<?php

use App\Enums\ExamStatus;
use App\Models\Course;
use App\Models\Department;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Faculty;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('invigilator scanner shows scheduled exams for offline download', function () {
    $permission = Permission::firstOrCreate(['name' => 'validate_entry', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'invigilator', 'guard_name' => 'web']);
    $role->givePermissionTo($permission);
    $invigilator = User::factory()->create();
    $invigilator->assignRole($role);

    $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $department = Department::create(['faculty_id' => $faculty->id, 'name' => 'Computer Science', 'code' => 'CSE']);
    $course = Course::create(['department_id' => $department->id, 'code' => 'CSE301', 'title' => 'Data Structures', 'credit_units' => 3]);
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
    ExamSession::create([
        'exam_id' => $exam->id,
        'session_number' => 1,
        'start_time' => now()->addWeek()->setTime(9, 0),
        'end_time' => now()->addWeek()->setTime(10, 0),
        'max_capacity' => 50,
        'allocated_count' => 12,
        'status' => 'pending',
    ]);

    $this
        ->actingAs($invigilator)
        ->get(route('invigilator.scanner'))
        ->assertOk()
        ->assertSee('Select a scheduled exam')
        ->assertSee('CSE301 - Data Structures')
        ->assertSee('12 student(s)');
});
