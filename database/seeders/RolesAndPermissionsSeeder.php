<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Define all permissions ─────────────────
        $permissions = [
            // User management
            'manage_users',
            'manage_roles',

            // Hall & System management
            'manage_halls',
            'manage_systems',
            'toggle_system_status',

            // Exam management
            'create_exams',
            'edit_exams',
            'delete_exams',
            'trigger_scheduling',
            'modify_allocations',
            'reassign_students',

            // Viewing
            'view_all_allocations',
            'view_own_schedule',
            'view_dashboard',

            // Validation & Attendance
            'validate_entry',
            'view_attendance',
            'manage_attendance',

            // Offline
            'download_offline_schedule',
            'sync_offline_data',

            // Exam Pass
            'download_exam_pass',

            // Reports
            'view_reports',
            'export_reports',

            // Settings & Audit
            'manage_settings',
            'view_audit_logs',

            // Notifications
            'send_notifications',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // ── Create Roles & Assign Permissions ──────

        // Super Admin — gets ALL permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // Exam Officer
        $examOfficer = Role::firstOrCreate(['name' => 'exam_officer', 'guard_name' => 'web']);
        $examOfficer->syncPermissions([
            'create_exams',
            'edit_exams',
            'trigger_scheduling',
            'modify_allocations',
            'reassign_students',
            'view_all_allocations',
            'view_attendance',
            'view_dashboard',
            'view_reports',
            'export_reports',
            'send_notifications',
        ]);

        // ICT Admin
        $ictAdmin = Role::firstOrCreate(['name' => 'ict_admin', 'guard_name' => 'web']);
        $ictAdmin->syncPermissions([
            'manage_halls',
            'manage_systems',
            'toggle_system_status',
            'view_all_allocations',
            'view_dashboard',
        ]);

        // Invigilator
        $invigilator = Role::firstOrCreate(['name' => 'invigilator', 'guard_name' => 'web']);
        $invigilator->syncPermissions([
            'validate_entry',
            'view_all_allocations',
            'view_attendance',
            'manage_attendance',
            'download_offline_schedule',
            'sync_offline_data',
            'view_dashboard',
        ]);

        // Student
        $student = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $student->syncPermissions([
            'view_own_schedule',
            'download_exam_pass',
        ]);
    }
}
