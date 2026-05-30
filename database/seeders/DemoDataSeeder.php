<?php

namespace Database\Seeders;

use App\Enums\ExamStatus;
use App\Models\Course;
use App\Models\Department;
use App\Models\Exam;
use App\Models\Faculty;
use App\Models\Hall;
use App\Models\StudentProfile;
use App\Models\System;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed demo data for development and testing.
     */
    public function run(): void
    {
        // ── Faculties ──
        $engineering = Faculty::firstOrCreate(['code' => 'ENG'], ['name' => 'Faculty of Engineering']);
        $science = Faculty::firstOrCreate(['code' => 'SCI'], ['name' => 'Faculty of Science']);

        // ── Departments ──
        $cse = Department::firstOrCreate(['code' => 'CSE'], ['name' => 'Computer Science & Engineering', 'faculty_id' => $engineering->id]);
        $eee = Department::firstOrCreate(['code' => 'EEE'], ['name' => 'Electrical & Electronics Eng.', 'faculty_id' => $engineering->id]);
        $phy = Department::firstOrCreate(['code' => 'PHY'], ['name' => 'Physics', 'faculty_id' => $science->id]);

        // ── Courses ──
        $courses = [
            Course::firstOrCreate(['code' => 'CSE301'], ['title' => 'Data Structures & Algorithms', 'credit_units' => 3, 'department_id' => $cse->id]),
            Course::firstOrCreate(['code' => 'CSE401'], ['title' => 'Operating Systems', 'credit_units' => 3, 'department_id' => $cse->id]),
            Course::firstOrCreate(['code' => 'EEE201'], ['title' => 'Circuit Theory', 'credit_units' => 3, 'department_id' => $eee->id]),
            Course::firstOrCreate(['code' => 'PHY101'], ['title' => 'General Physics I', 'credit_units' => 4, 'department_id' => $phy->id]),
        ];

        // ── Staff Users ──
        $examOfficer = User::firstOrCreate(['email' => 'examofficer@mxschedule.test'], [
            'name' => 'Dr. Exam Officer',
            'password' => Hash::make('password'), 'email_verified_at' => now(),
        ]);
        if (!$examOfficer->hasRole('exam_officer')) $examOfficer->assignRole('exam_officer');

        $ictAdmin = User::firstOrCreate(['email' => 'ictadmin@mxschedule.test'], [
            'name' => 'ICT Administrator',
            'password' => Hash::make('password'), 'email_verified_at' => now(),
        ]);
        if (!$ictAdmin->hasRole('ict_admin')) $ictAdmin->assignRole('ict_admin');

        $invigilator = User::firstOrCreate(['email' => 'invigilator@mxschedule.test'], [
            'name' => 'Dr. Invigilator',
            'password' => Hash::make('password'), 'email_verified_at' => now(),
        ]);
        if (!$invigilator->hasRole('invigilator')) $invigilator->assignRole('invigilator');

        // ── Student Users ──
        $students = [];
        $names = [
            'Adebayo Olumide', 'Chinelo Nwankwo', 'Fatima Hassan',
            'Ibrahim Yusuf', 'Jennifer Okon', 'Kelechi Eze',
            'Lateef Adeyemi', 'Mercy Adeola', 'Ngozi Okoro',
            'Obinna Chukwu', 'Peter Emeka', 'Rashidat Bello',
            'Samuel Akpan', 'Temitope Adegoke', 'Usman Mohammed',
            'Victoria Ibe', 'Wale Adenuga', 'Xander Obi',
            'Yetunde Oladipo', 'Zainab Abdullahi',
            'Amaka Igwe', 'Bola Ajayi', 'Chidi Nnamdi',
            'Damilola Ojo', 'Emeka Okwuosa', 'Funmi Akinsola',
            'Grace Udoh', 'Henry Okafor', 'Ifeoma Nwosu',
            'James Onyekachi',
        ];

        foreach ($names as $i => $name) {
            $user = User::firstOrCreate(['email' => 'student' . ($i + 1) . '@mxschedule.test'], [
                'name' => $name,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
            if (!$user->hasRole('student')) $user->assignRole('student');

            $dept = $i < 15 ? $cse : ($i < 22 ? $eee : $phy);
            $level = collect([200, 300, 400])->random();

            $profile = StudentProfile::firstOrCreate(['user_id' => $user->id], [
                'department_id' => $dept->id,
                'matric_number' => strtoupper($dept->code) . '/' . (2021 + ($i % 3)) . '/' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'level' => $level,
            ]);

            $students[] = $profile;
        }

        // ── Enroll students in courses ──
        $academicSession = '2025/2026';
        foreach ($students as $profile) {
            $deptCourses = collect($courses)->filter(fn ($c) => $c->department_id === $profile->department_id);
            // Also enroll in the general physics course
            $general = collect($courses)->firstWhere('code', 'PHY101');

            foreach ($deptCourses as $course) {
                if (!$profile->courses()->where('course_id', $course->id)->exists()) {
                    $profile->courses()->attach($course->id, [
                        'academic_session' => $academicSession,
                        'semester' => 'first',
                    ]);
                }
            }

            if ($general && $profile->level <= 200 && $profile->department_id !== $phy->id) {
                if (!$profile->courses()->where('course_id', $general->id)->exists()) {
                    $profile->courses()->attach($general->id, [
                        'academic_session' => $academicSession,
                        'semester' => 'first',
                    ]);
                }
            }
        }

        // ── Halls & Systems ──
        $hallA = Hall::firstOrCreate(['code' => 'HA'], ['name' => 'CBT Hall Alpha', 'capacity' => 50, 'location' => 'Block A, Ground Floor', 'is_active' => true]);
        $hallB = Hall::firstOrCreate(['code' => 'HB'], ['name' => 'CBT Hall Beta', 'capacity' => 40, 'location' => 'Block B, First Floor', 'is_active' => true]);

        if (System::where('hall_id', $hallA->id)->count() === 0) {
            foreach (range(1, 50) as $n) {
                System::create([
                    'hall_id' => $hallA->id,
                    'system_code' => 'HA' . str_pad($n, 3, '0', STR_PAD_LEFT),
                    'status' => $n <= 45 ? 'active' : ($n <= 48 ? 'inactive' : 'faulty'),
                ]);
            }
        }

        if (System::where('hall_id', $hallB->id)->count() === 0) {
            foreach (range(1, 40) as $n) {
                System::create([
                    'hall_id' => $hallB->id,
                    'system_code' => 'HB' . str_pad($n, 3, '0', STR_PAD_LEFT),
                    'status' => $n <= 37 ? 'active' : 'faulty',
                ]);
            }
        }

        // ── Exams ──
        Exam::firstOrCreate(['course_id' => $courses[0]->id, 'academic_session' => $academicSession, 'semester' => 'first'], [
            'exam_date' => now()->addDays(3)->toDateString(),
            'start_time' => '09:00',
            'duration_minutes' => 60,
            'buffer_minutes' => 15,
            'total_registered_students' => 15,
            'status' => ExamStatus::Draft,
        ]);

        Exam::firstOrCreate(['course_id' => $courses[3]->id, 'academic_session' => $academicSession, 'semester' => 'first'], [
            'exam_date' => now()->addDays(5)->toDateString(),
            'start_time' => '10:00',
            'duration_minutes' => 90,
            'buffer_minutes' => 15,
            'total_registered_students' => 8,
            'status' => ExamStatus::Draft,
        ]);

        $this->command->info('Demo data seeded (idempotent): 2 faculties, 3 departments, 4 courses, 30 students, 2 halls (90 systems), 2 exams.');
    }
}
