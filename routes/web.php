<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Student;
use App\Http\Controllers\Api;
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

    // ── Admin Routes ────────────────────────────
    Route::prefix('admin')
        ->name('admin.')
        ->middleware(['role:super_admin|exam_officer|ict_admin'])
        ->group(function () {

            Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

            // Halls
            Route::resource('halls', Admin\HallController::class);

            // Systems
            Route::post('/halls/{hall}/systems/bulk', [Admin\SystemController::class, 'bulkCreate'])->name('systems.bulk-create');
            Route::patch('/systems/{system}/status', [Admin\SystemController::class, 'updateStatus'])->name('systems.update-status');
            Route::get('/systems', [Admin\SystemController::class, 'index'])->name('systems.index');

            // Exams
            Route::resource('exams', Admin\ExamController::class);
            Route::post('/exams/{exam}/schedule', [Admin\ScheduleController::class, 'generate'])->name('exams.schedule');
            Route::post('/exams/{exam}/reschedule', [Admin\ScheduleController::class, 'reschedule'])->name('exams.reschedule');
            Route::get('/exams/{exam}/allocations', [Admin\ScheduleController::class, 'allocations'])->name('exams.allocations');

            // Reallocation
            Route::post('/reallocate', [Admin\ReallocationController::class, 'reassign'])->name('reallocate');

            // Reports
            Route::get('/reports/{type}', [Admin\ReportController::class, 'show'])->name('reports.show');
            Route::get('/reports/{type}/download', [Admin\ReportController::class, 'download'])->name('reports.download');

            // Users (Super Admin only)
            Route::resource('users', Admin\UserController::class)->middleware('role:super_admin');

            // CSV Import
            Route::get('/import', [Admin\CsvImportController::class, 'index'])->name('import.index');
            Route::post('/import', [Admin\CsvImportController::class, 'importStudents'])->name('import.process');
            Route::get('/import/template/{type}', [Admin\CsvImportController::class, 'downloadTemplate'])->name('import.template');
        });

    // ── Invigilator Routes ──────────────────────
    Route::prefix('invigilator')
        ->name('invigilator.')
        ->middleware(['role:super_admin|invigilator'])
        ->group(function () {
            Route::get('/scanner', fn () => view('invigilator.scanner'))->name('scanner');
            Route::get('/attendance/{examSession}', fn () => view('invigilator.attendance'))->name('attendance');
        });

    // ── Student Routes ──────────────────────────
    Route::prefix('student')
        ->name('student.')
        ->middleware(['role:super_admin|student'])
        ->group(function () {
            Route::get('/dashboard', [Student\DashboardController::class, 'index'])->name('dashboard');
            Route::get('/exam-pass/{allocation}', [Student\ExamPassController::class, 'show'])->name('exam-pass.show');
            Route::get('/exam-pass/{allocation}/download', [Student\ExamPassController::class, 'download'])->name('exam-pass.download');
        });
});

// ── API Routes (Web session auth for scanner) ──
Route::middleware(['auth', 'web'])
    ->prefix('api/v1')
    ->group(function () {
        Route::post('/validate-qr', [Api\QrValidationController::class, 'validate'])->name('api.validate-qr');
    });

// ── API Routes (Offline/Sync) ──
Route::prefix('api/v1')
    ->middleware(['auth', 'role:super_admin|exam_officer|ict_admin|invigilator'])
    ->group(function () {
        Route::post('/offline/sync', [Api\OfflineSyncController::class, 'sync'])->name('api.offline.sync');
        Route::get('/offline/schedule/{exam}', [Api\OfflineSyncController::class, 'downloadSchedule'])->name('api.offline.schedule');
    });

require __DIR__.'/auth.php';
