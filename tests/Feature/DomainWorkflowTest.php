<?php

use App\Enums\ExamStatus;
use App\Enums\SeatStatus;
use App\Enums\SystemStatus;
use App\Models\Course;
use App\Models\Department;
use App\Models\Exam;
use App\Models\ExamAllocation;
use App\Models\ExamSession;
use App\Models\Faculty;
use App\Models\Hall;
use App\Models\StudentProfile;
use App\Models\System;
use App\Models\User;
use App\Services\QrValidationService;
use App\Services\ReallocationService;
use App\Services\SchedulingEngine;
use App\Services\ExamPassService;
use App\Jobs\SendScheduleNotificationsJob;
use App\Notifications\ExamReminderNotification;
use App\Notifications\ReallocationAttentionRequiredNotification;
use App\Notifications\ScheduleReleasedNotification;
use App\Notifications\StudentReallocatedNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

function domainFixture(): array
{
    $faculty = Faculty::create(['name' => 'Engineering', 'code' => 'ENG']);
    $department = Department::create(['faculty_id' => $faculty->id, 'name' => 'Computing', 'code' => 'CMP']);
    $course = Course::create(['department_id' => $department->id, 'code' => 'CMP101', 'title' => 'Computing', 'credit_units' => 3]);
    $hall = Hall::create(['name' => 'CBT Hall', 'code' => 'CBT', 'capacity' => 10, 'is_active' => true]);
    $system = System::create(['hall_id' => $hall->id, 'system_code' => 'CBT01', 'status' => 'active']);
    $replacement = System::create(['hall_id' => $hall->id, 'system_code' => 'CBT02', 'status' => 'active']);
    $user = User::factory()->create();
    $student = StudentProfile::create([
        'user_id' => $user->id,
        'matric_number' => 'CMP/001',
        'department_id' => $department->id,
        'level' => 100,
    ]);
    $student->courses()->attach($course->id, ['academic_session' => '2025/2026', 'semester' => 'first']);

    return compact('course', 'hall', 'system', 'replacement', 'student', 'user');
}

function createAllocation(array $fixture, Carbon $start, Carbon $end): ExamAllocation
{
    $exam = Exam::create([
        'course_id' => $fixture['course']->id,
        'academic_session' => '2025/2026',
        'semester' => 'first',
        'exam_date' => $start->toDateString(),
        'start_time' => $start->format('H:i'),
        'duration_minutes' => $start->diffInMinutes($end),
        'buffer_minutes' => 15,
        'status' => ExamStatus::Scheduled,
    ]);
    $session = ExamSession::create([
        'exam_id' => $exam->id,
        'session_number' => 1,
        'start_time' => $start,
        'end_time' => $end,
        'max_capacity' => 2,
        'allocated_count' => 1,
        'status' => 'pending',
    ]);

    return ExamAllocation::create([
        'exam_session_id' => $session->id,
        'student_profile_id' => $fixture['student']->id,
        'system_id' => $fixture['system']->id,
        'hall_id' => $fixture['hall']->id,
        'seat_status' => SeatStatus::Allocated,
    ]);
}

function domainAdminUser(): User
{
    $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('overlapping exams do not allocate the same computer at the same time', function () {
    $fixture = domainFixture();
    $start = now()->addDay()->startOfDay()->addHours(9);

    foreach ([1, 2] as $index) {
        $exam = Exam::create([
            'course_id' => $fixture['course']->id,
            'academic_session' => '2025/2026',
            'semester' => 'first',
            'exam_date' => $start->toDateString(),
            'start_time' => '09:00',
            'duration_minutes' => 60,
            'buffer_minutes' => 15,
            'status' => ExamStatus::Draft,
        ]);
        app(SchedulingEngine::class)->generateSchedule($exam, $fixture['user']->id);
    }

    $allocations = ExamAllocation::with('examSession')->get();
    expect($allocations)->toHaveCount(2)
        ->and($allocations->pluck('system_id')->unique())->toHaveCount(2);
});

test('reallocation retires the old row and issues a replacement allocation', function () {
    Notification::fake();
    $fixture = domainFixture();
    $allocation = createAllocation($fixture, now()->addHour(), now()->addHours(2));

    $replacement = app(ReallocationService::class)->reassignStudent($allocation, $fixture['replacement']);

    expect($allocation->refresh()->seat_status)->toBe(SeatStatus::Reassigned)
        ->and($replacement->system_id)->toBe($fixture['replacement']->id)
        ->and($replacement->examPass)->not->toBeNull();

    Notification::assertSentTo($fixture['user'], StudentReallocatedNotification::class, function ($notification) use ($allocation, $replacement) {
        return $notification->oldAllocation->is($allocation)
            && $notification->newAllocation->is($replacement);
    });
});

test('system reallocation asks admins for attention when no replacement is available', function () {
    Notification::fake();
    $admin = domainAdminUser();
    $fixture = domainFixture();
    $fixture['replacement']->update(['status' => SystemStatus::Inactive]);
    $allocation = createAllocation($fixture, now()->addHour(), now()->addHours(2));

    $reassigned = app(ReallocationService::class)->reassignFromSystem($fixture['system']);

    expect($reassigned)->toHaveCount(0)
        ->and($allocation->refresh()->seat_status)->toBe(SeatStatus::Allocated);

    Notification::assertSentTo($admin, ReallocationAttentionRequiredNotification::class, function ($notification) use ($allocation) {
        return $notification->allocation->is($allocation);
    });
});

test('offline reconciliation rejects an unsigned payload', function () {
    $fixture = domainFixture();
    $allocation = createAllocation($fixture, now()->subMinutes(5), now()->addHour());

    $result = app(QrValidationService::class)->reconcileOfflineScan(
        '{"aid":'.$allocation->id.'}',
        now(),
        $fixture['user']->id,
    );

    expect($result['valid'])->toBeFalse()
        ->and($allocation->refresh()->seat_status)->toBe(SeatStatus::Allocated);
});

test('live validation accepts a signed pass once and rejects duplicate use', function () {
    $fixture = domainFixture();
    $allocation = createAllocation($fixture, now()->subMinutes(5), now()->addHour());
    $pass = app(ExamPassService::class)->generateForAllocation($allocation);

    $first = app(QrValidationService::class)->validate($pass->qr_payload, $fixture['user']->id);
    $second = app(QrValidationService::class)->validate($pass->qr_payload, $fixture['user']->id);

    expect($first['valid'])->toBeTrue()
        ->and($second['valid'])->toBeFalse()
        ->and($second['result']->value)->toBe('duplicate')
        ->and($allocation->refresh()->seat_status)->toBe(SeatStatus::CheckedIn);
});

test('offline reconciliation accepts an authentic signed pass', function () {
    $fixture = domainFixture();
    $allocation = createAllocation($fixture, now()->subMinutes(5), now()->addHour());
    $pass = app(ExamPassService::class)->generateForAllocation($allocation);

    $result = app(QrValidationService::class)->reconcileOfflineScan(
        $pass->qr_payload,
        now(),
        $fixture['user']->id,
    );

    expect($result['valid'])->toBeTrue()
        ->and($allocation->refresh()->seat_status)->toBe(SeatStatus::CheckedIn);
});

test('lifecycle synchronization completes attendance and marks no shows', function () {
    $fixture = domainFixture();
    $allocation = createAllocation($fixture, now()->subHours(2), now()->subHour());

    $this->artisan('exam:sync-lifecycle')->assertSuccessful();

    expect($allocation->refresh()->seat_status)->toBe(SeatStatus::NoShow)
        ->and($allocation->examSession->refresh()->status->value)->toBe('completed')
        ->and($allocation->examSession->exam->refresh()->status)->toBe(ExamStatus::Completed);
});

test('schedule notification job sends exam emails', function () {
    Notification::fake();
    $fixture = domainFixture();
    $allocation = createAllocation($fixture, now()->addHour(), now()->addHours(2));

    (new SendScheduleNotificationsJob($allocation->examSession->exam))->handle();

    Notification::assertSentTo($fixture['user'], ScheduleReleasedNotification::class, function ($notification) use ($allocation) {
        return $notification->allocation->is($allocation)
            && $notification->exam->is($allocation->examSession->exam);
    });
});

test('scheduled reminder command sends each reminder once', function () {
    Notification::fake();
    $fixture = domainFixture();
    $allocation = createAllocation($fixture, now()->addHours(23), now()->addHours(24));

    $this->artisan('exam:send-reminders')->assertSuccessful();
    $this->artisan('exam:send-reminders')->assertSuccessful();

    Notification::assertSentToTimes($fixture['user'], ExamReminderNotification::class, 1);
    Notification::assertSentTo($fixture['user'], ExamReminderNotification::class, function ($notification) use ($allocation) {
        return $notification->allocation->is($allocation)
            && $notification->hoursBefore === 24;
    });

    expect(DB::table('exam_reminder_logs')
        ->where('exam_allocation_id', $allocation->id)
        ->where('hours_before', 24)
        ->count())->toBe(1);
});

test('scheduled reminder dry run does not reserve reminder logs', function () {
    Notification::fake();
    $fixture = domainFixture();
    $allocation = createAllocation($fixture, now()->addMinutes(45), now()->addHours(2));

    $this->artisan('exam:send-reminders --dry-run')->assertSuccessful();

    Notification::assertNothingSent();
    expect(DB::table('exam_reminder_logs')
        ->where('exam_allocation_id', $allocation->id)
        ->count())->toBe(0);
});
