<?php

use App\Enums\ExamStatus;
use App\Jobs\GenerateExamPassPdfJob;
use App\Jobs\GenerateExamScheduleJob;
use App\Models\Course;
use App\Models\Department;
use App\Models\Exam;
use App\Models\Faculty;
use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use App\Notifications\CsvImportSummaryNotification;
use App\Notifications\ExamJobFailedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

function createSuperAdminUser(): User
{
    $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('admin-created users receive their login details by email', function () {
    Notification::fake();
    createSuperAdminUser();
    Role::firstOrCreate(['name' => 'exam_officer', 'guard_name' => 'web']);

    $admin = User::role('super_admin')->first();

    $response = $this
        ->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Exam Officer',
            'email' => 'officer@mxschedule.test',
            'phone' => '08000000000',
            'role' => 'exam_officer',
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
        ]);

    $response->assertRedirect(route('admin.users.index'));

    $user = User::where('email', 'officer@mxschedule.test')->firstOrFail();

    expect(Hash::check('StrongPass123', $user->password))->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull();

    Notification::assertSentTo($user, AccountCreatedNotification::class, function ($notification) {
        return $notification->plainPassword === 'StrongPass123'
            && $notification->role === 'exam_officer';
    });
});

test('csv-created students receive default password email', function () {
    Notification::fake();
    $admin = createSuperAdminUser();
    Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

    $faculty = Faculty::create(['name' => 'Engineering', 'code' => 'ENG']);
    $department = Department::create(['faculty_id' => $faculty->id, 'name' => 'Computer Science', 'code' => 'CSE']);
    Course::create(['department_id' => $department->id, 'code' => 'CSE301', 'title' => 'Data Structures', 'credit_units' => 3]);

    $csv = "name,email,matric_number,department_code,level,courses\n"
        ."CSV Student,csv.student@mxschedule.test,CSE/2026/010,CSE,300,CSE301\n";

    $response = $this
        ->actingAs($admin)
        ->post(route('admin.import.process'), [
            'import_type' => 'students',
            'academic_session' => '2025/2026',
            'semester' => 'first',
            'csv_file' => UploadedFile::fake()->createWithContent('students.csv', $csv),
        ]);

    $response->assertRedirect();

    $user = User::where('email', 'csv.student@mxschedule.test')->firstOrFail();

    expect($user->hasRole('student'))->toBeTrue()
        ->and(Hash::check('password', $user->password))->toBeTrue();

    Notification::assertSentTo($user, AccountCreatedNotification::class, function ($notification) {
        return $notification->plainPassword === 'password'
            && $notification->role === 'student';
    });

    Notification::assertSentTo($admin, CsvImportSummaryNotification::class, function ($notification) {
        return $notification->importType === 'students'
            && $notification->results['imported'] === 1
            && $notification->results['skipped'] === 0;
    });
});

test('admins receive schedule generation failure alerts', function () {
    Notification::fake();
    $admin = createSuperAdminUser();

    $faculty = Faculty::create(['name' => 'Engineering', 'code' => 'ENG']);
    $department = Department::create(['faculty_id' => $faculty->id, 'name' => 'Computer Science', 'code' => 'CSE']);
    $course = Course::create(['department_id' => $department->id, 'code' => 'CSE401', 'title' => 'Algorithms', 'credit_units' => 3]);
    $exam = Exam::create([
        'course_id' => $course->id,
        'academic_session' => '2025/2026',
        'semester' => 'first',
        'exam_date' => now()->addDay()->toDateString(),
        'start_time' => '09:00',
        'duration_minutes' => 60,
        'buffer_minutes' => 15,
        'status' => ExamStatus::Scheduling,
    ]);

    (new GenerateExamScheduleJob($exam, $admin->id))->failed(new RuntimeException('capacity exhausted'));

    Notification::assertSentTo($admin, ExamJobFailedNotification::class, function ($notification) use ($exam) {
        return $notification->exam->is($exam)
            && $notification->jobName === 'Schedule generation'
            && str_contains($notification->errorMessage, 'capacity exhausted');
    });
});

test('admins receive exam pass pdf failure alerts', function () {
    Notification::fake();
    $admin = createSuperAdminUser();

    $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $department = Department::create(['faculty_id' => $faculty->id, 'name' => 'Mathematics', 'code' => 'MTH']);
    $course = Course::create(['department_id' => $department->id, 'code' => 'MTH401', 'title' => 'Numerical Analysis', 'credit_units' => 3]);
    $exam = Exam::create([
        'course_id' => $course->id,
        'academic_session' => '2025/2026',
        'semester' => 'first',
        'exam_date' => now()->addDay()->toDateString(),
        'start_time' => '11:00',
        'duration_minutes' => 60,
        'buffer_minutes' => 15,
        'status' => ExamStatus::Scheduled,
    ]);

    (new GenerateExamPassPdfJob($exam))->failed(new RuntimeException('storage unavailable'));

    Notification::assertSentTo($admin, ExamJobFailedNotification::class, function ($notification) use ($exam) {
        return $notification->exam->is($exam)
            && $notification->jobName === 'Exam pass PDF generation'
            && str_contains($notification->errorMessage, 'storage unavailable');
    });
});
