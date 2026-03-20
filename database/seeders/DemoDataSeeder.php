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
        $engineering = Faculty::create(['name' => 'Faculty of Engineering', 'code' => 'ENG']);
        $science = Faculty::create(['name' => 'Faculty of Science', 'code' => 'SCI']);

        // ── Departments ──
        $cse = Department::create(['name' => 'Computer Science & Engineering', 'code' => 'CSE', 'faculty_id' => $engineering->id]);
        $eee = Department::create(['name' => 'Electrical & Electronics Eng.', 'code' => 'EEE', 'faculty_id' => $engineering->id]);
        $phy = Department::create(['name' => 'Physics', 'code' => 'PHY', 'faculty_id' => $science->id]);

        // ── Courses ──
        $courses = [
            Course::create(['code' => 'CSE301', 'title' => 'Data Structures & Algorithms', 'credit_units' => 3, 'department_id' => $cse->id]),
            Course::create(['code' => 'CSE401', 'title' => 'Operating Systems', 'credit_units' => 3, 'department_id' => $cse->id]),
            Course::create(['code' => 'EEE201', 'title' => 'Circuit Theory', 'credit_units' => 3, 'department_id' => $eee->id]),
            Course::create(['code' => 'PHY101', 'title' => 'General Physics I', 'credit_units' => 4, 'department_id' => $phy->id]),
        ];

        // ── Staff Users ──
        $examOfficer = User::create([
            'name' => 'Dr. Exam Officer', 'email' => 'examofficer@mxschedule.test',
            'password' => Hash::make('password'), 'email_verified_at' => now(),
        ]);
        $examOfficer->assignRole('exam_officer');

        $ictAdmin = User::create([
            'name' => 'ICT Administrator', 'email' => 'ictadmin@mxschedule.test',
            'password' => Hash::make('password'), 'email_verified_at' => now(),
        ]);
        $ictAdmin->assignRole('ict_admin');

        $invigilator = User::create([
            'name' => 'Dr. Invigilator', 'email' => 'invigilator@mxschedule.test',
            'password' => Hash::make('password'), 'email_verified_at' => now(),
        ]);
        $invigilator->assignRole('invigilator');

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
            $user = User::create([
                'name' => $name,
                'email' => 'student' . ($i + 1) . '@mxschedule.test',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
            $user->assignRole('student');

            $dept = $i < 15 ? $cse : ($i < 22 ? $eee : $phy);
            $level = collect([200, 300, 400])->random();

            $profile = StudentProfile::create([
                'user_id' => $user->id,
                'department_id' => $dept->id,
                'matric_number' => strtoupper($dept->code) . '/' . (2021 + ($i % 3)) . '/' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'level' => $level,
            ]);

            $students[] = $profile;
        }

        // ── Enroll students in courses ──
        $session = '2025/2026';
        foreach ($students as $profile) {
            $deptCourses = collect($courses)->filter(fn ($c) => $c->department_id === $profile->department_id);
            // Also enroll in the general physics course
            $general = collect($courses)->firstWhere('code', 'PHY101');

            foreach ($deptCourses as $course) {
                $profile->courses()->attach($course->id, [
                    'academic_session' => $session,
                    'semester' => 'first',
                ]);
            }

            if ($general && $profile->level <= 200 && $profile->department_id !== $phy->id) {
                $profile->courses()->attach($general->id, [
                    'academic_session' => $session,
                    'semester' => 'first',
                ]);
            }
        }

        // ── Halls & Systems ──
        $hallA = Hall::create(['name' => 'CBT Hall Alpha', 'code' => 'HA', 'capacity' => 50, 'location' => 'Block A, Ground Floor', 'is_active' => true]);
        $hallB = Hall::create(['name' => 'CBT Hall Beta', 'code' => 'HB', 'capacity' => 40, 'location' => 'Block B, First Floor', 'is_active' => true]);

        foreach (range(1, 50) as $n) {
            System::create([
                'hall_id' => $hallA->id,
                'system_code' => 'HA' . str_pad($n, 3, '0', STR_PAD_LEFT),
                'status' => $n <= 45 ? 'active' : ($n <= 48 ? 'inactive' : 'faulty'),
            ]);
        }

        foreach (range(1, 40) as $n) {
            System::create([
                'hall_id' => $hallB->id,
                'system_code' => 'HB' . str_pad($n, 3, '0', STR_PAD_LEFT),
                'status' => $n <= 37 ? 'active' : 'faulty',
            ]);
        }

        // ── Exams ──
        Exam::create([
            'course_id' => $courses[0]->id, // CSE301
            'academic_session' => $session,
            'semester' => 'first',
            'exam_date' => now()->addDays(3)->toDateString(),
            'start_time' => '09:00',
            'duration_minutes' => 60,
            'buffer_minutes' => 15,
            'total_registered_students' => 15,
            'status' => ExamStatus::Draft,
        ]);

        Exam::create([
            'course_id' => $courses[3]->id, // PHY101
            'academic_session' => $session,
            'semester' => 'first',
            'exam_date' => now()->addDays(5)->toDateString(),
            'start_time' => '10:00',
            'duration_minutes' => 90,
            'buffer_minutes' => 15,
            'total_registered_students' => 8,
            'status' => ExamStatus::Draft,
        ]);

        $this->command->info('Demo data seeded: 2 faculties, 3 departments, 4 courses, 30 students, 2 halls (90 systems), 2 exams.');
    }
}
