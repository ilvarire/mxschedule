<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Api;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Invigilator;
use App\Http\Controllers\Student;
use Illuminate\Support\Facades\Route;

// ── Public ──────────────────────────────────────
Route::view('/', 'welcome');

// ── Authenticated (redirects by role) ───────────
Route::middleware(['auth', 'verified'])->group(function () {

    // Default dashboard — redirects by role
    Route::get('/dashboard', function () {
        $user = auth()->user();

        if ($user->hasAnyRole(['super_admin', 'exam_officer', 'ict_admin'])) {
            return redirect()->route('admin.dashboard');
        }
        if ($user->hasRole('invigilator')) {
            return redirect()->route('invigilator.scanner');
        }
        if ($user->hasRole('student')) {
            return redirect()->route('student.dashboard');
        }

        return redirect('/');
    })->name('dashboard');

    // Profile (Breeze default)
    Route::view('profile', 'profile')->name('profile');
    Route::get('profile/password', [PasswordController::class, 'edit'])->name('profile.password.edit');
    Route::patch('profile/password', [PasswordController::class, 'update'])->name('profile.password.update');

    // ── Admin Routes ────────────────────────────
    Route::prefix('admin')
        ->name('admin.')
        ->middleware(['role:super_admin|exam_officer|ict_admin'])
        ->group(function () {

            Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

            // Halls & systems (ICT infrastructure)
            Route::resource('halls', Admin\HallController::class)
                ->middleware('permission:manage_halls');

            Route::post('/halls/{hall}/systems/bulk', [Admin\SystemController::class, 'bulkCreate'])
                ->middleware('permission:manage_systems')->name('systems.bulk-create');
            Route::patch('/systems/{system}/status', [Admin\SystemController::class, 'updateStatus'])
                ->middleware('permission:toggle_system_status')->name('systems.update-status');
            Route::get('/systems', [Admin\SystemController::class, 'index'])
                ->middleware('permission:manage_systems')->name('systems.index');

            // Exams & Courses
            Route::resource('courses', Admin\CourseController::class)
                ->middleware('role:super_admin|exam_officer');
            Route::resource('exams', Admin\ExamController::class)
                ->middleware('role:super_admin|exam_officer');
            Route::post('/exams/{exam}/schedule', [Admin\ScheduleController::class, 'generate'])
                ->middleware('permission:trigger_scheduling')->name('exams.schedule');
            Route::post('/exams/{exam}/reschedule', [Admin\ScheduleController::class, 'reschedule'])
                ->middleware('permission:trigger_scheduling')->name('exams.reschedule');
            Route::get('/exams/{exam}/allocations', [Admin\ScheduleController::class, 'allocations'])
                ->middleware('permission:view_all_allocations')->name('exams.allocations');
            Route::get('/monitoring', fn () => view('admin.monitoring'))
                ->middleware('permission:view_attendance')->name('monitoring');
            // Manual notification resend
            Route::post('/exams/{exam}/notify', [Admin\ScheduleController::class, 'notify'])
                ->middleware('permission:send_notifications')->name('exams.notify');

            // Reallocation
            Route::post('/reallocate', [Admin\ReallocationController::class, 'reassign'])
                ->middleware('permission:reassign_students')->name('reallocate');

            // Reports
            Route::get('/reports/{type}', [Admin\ReportController::class, 'show'])
                ->middleware('permission:view_reports')->name('reports.show');
            Route::get('/reports/{type}/download', [Admin\ReportController::class, 'download'])
                ->middleware('permission:export_reports')->name('reports.download');

            // Users (Super Admin only)
            Route::resource('users', Admin\UserController::class)->middleware('role:super_admin');
            Route::get('/settings', [Admin\SettingController::class, 'index'])
                ->middleware('permission:manage_settings')->name('settings.index');
            Route::put('/settings', [Admin\SettingController::class, 'update'])
                ->middleware('permission:manage_settings')->name('settings.update');
            Route::get('/audit-logs', [Admin\AuditLogController::class, 'index'])
                ->middleware('permission:view_audit_logs')->name('audit-logs.index');
            Route::get('/academic-structure', [Admin\AcademicStructureController::class, 'index'])
                ->middleware('role:super_admin')->name('academic-structure.index');
            Route::post('/faculties', [Admin\AcademicStructureController::class, 'storeFaculty'])
                ->middleware('role:super_admin')->name('faculties.store');
            Route::delete('/faculties/{faculty}', [Admin\AcademicStructureController::class, 'destroyFaculty'])
                ->middleware('role:super_admin')->name('faculties.destroy');
            Route::post('/departments', [Admin\AcademicStructureController::class, 'storeDepartment'])
                ->middleware('role:super_admin')->name('departments.store');
            Route::delete('/departments/{department}', [Admin\AcademicStructureController::class, 'destroyDepartment'])
                ->middleware('role:super_admin')->name('departments.destroy');

            // CSV Import
            Route::get('/import', [Admin\CsvImportController::class, 'index'])
                ->middleware('role:super_admin|exam_officer')->name('import.index');
            Route::post('/import', [Admin\CsvImportController::class, 'importStudents'])
                ->middleware('role:super_admin|exam_officer')->name('import.process');
            Route::get('/import/template/{type}', [Admin\CsvImportController::class, 'downloadTemplate'])
                ->middleware('role:super_admin|exam_officer')->name('import.template');
        });

    // ── Invigilator Routes ──────────────────────
    Route::prefix('invigilator')
        ->name('invigilator.')
        ->middleware(['role:super_admin|invigilator'])
        ->group(function () {
            Route::get('/scanner', [Invigilator\ScannerController::class, 'index'])
                ->middleware('permission:validate_entry')->name('scanner');
            Route::get('/attendance/{examSession}', fn (\App\Models\ExamSession $examSession) => view('invigilator.attendance', compact('examSession')))
                ->middleware('permission:view_attendance')->name('attendance');
        });

    // ── Student Routes ──────────────────────────
    Route::prefix('student')
        ->name('student.')
        ->middleware(['role:super_admin|student'])
        ->group(function () {
        Route::get('/dashboard', [Student\DashboardController::class, 'index'])->name('dashboard');
            Route::get('/exam-pass/{allocation}', [Student\ExamPassController::class, 'show'])->name('exam-pass.show');
            Route::get('/exam-pass/{allocation}/download', [Student\ExamPassController::class, 'download'])->name('exam-pass.download');
            // Notifications
            Route::get('/notifications', [Student\NotificationsController::class, 'index'])->name('notifications.index');
            Route::post('/notifications/{id}/read', [Student\NotificationsController::class, 'markRead'])->name('notifications.read');
            Route::post('/notifications/read-all', [Student\NotificationsController::class, 'markAllRead'])->name('notifications.read-all');
            Route::delete('/notifications/{id}', [Student\NotificationsController::class, 'destroy'])->name('notifications.destroy');
        });
});

// ── API Routes (Web session auth for scanner) ──
Route::middleware(['auth', 'web', 'permission:validate_entry', 'throttle:30,1'])
    ->prefix('api/v1')
    ->group(function () {
        Route::post('/validate-qr', [Api\QrValidationController::class, 'validate'])->name('api.validate-qr');
    });

// ── API Routes (Offline/Sync) ──
Route::prefix('api/v1')
    ->middleware(['auth', 'role:super_admin|invigilator', 'throttle:10,1'])
    ->group(function () {
        Route::get('/offline/keys', [Api\OfflineSyncController::class, 'getPublicKey'])->name('api.offline.keys');
        Route::post('/offline/sync', [Api\OfflineSyncController::class, 'sync'])->name('api.offline.sync');
        Route::get('/offline/schedule/{exam}', [Api\OfflineSyncController::class, 'downloadSchedule'])->name('api.offline.schedule');
    });

require __DIR__.'/auth.php';
