<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Department;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CsvImportService
{
    protected array $errors = [];
    protected int $imported = 0;
    protected int $skipped = 0;
    protected int $updated = 0;

    /**
     * Import students from a CSV file.
     *
     * Expected CSV columns:
     * name, email, matric_number, department_code, level, courses (pipe-separated course codes), academic_session, semester
     *
     * Example row:
     * John Doe,john@uni.edu,CSE/2024/001,CSE,300,CSE301|CSE401,2025/2026,first
     */
    public function importStudents(UploadedFile $file, string $academicSession, string $semester): array
    {
        $rows = $this->parseCsv($file);

        if ($rows->isEmpty()) {
            return $this->results('CSV file is empty or could not be parsed.');
        }

        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                $this->processRow($row, $index + 2, $academicSession, $semester); // +2 for header + 0-index
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->results("Import failed: {$e->getMessage()}");
        }

        return $this->results();
    }

    /**
     * Import course enrollments only (students already exist).
     */
    public function importEnrollments(UploadedFile $file, string $academicSession, string $semester): array
    {
        $rows = $this->parseCsv($file);

        if ($rows->isEmpty()) {
            return $this->results('CSV file is empty or could not be parsed.');
        }

        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                $this->processEnrollmentRow($row, $index + 2, $academicSession, $semester);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->results("Import failed: {$e->getMessage()}");
        }

        return $this->results();
    }

    protected function processRow(array $row, int $line, string $academicSession, string $semester): void
    {
        $validator = Validator::make($row, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'matric_number' => 'required|string|max:30',
            'department_code' => 'required|string|max:10',
            'level' => 'required|integer|min:100|max:900',
        ]);

        if ($validator->fails()) {
            $this->errors[] = "Row {$line}: " . implode(', ', $validator->errors()->all());
            $this->skipped++;
            return;
        }

        // Find department
        $department = Department::where('code', strtoupper($row['department_code']))->first();
        if (! $department) {
            $this->errors[] = "Row {$line}: Department '{$row['department_code']}' not found.";
            $this->skipped++;
            return;
        }

        // Create or update user
        $defaultPassword = 'password';
        $user = User::firstOrCreate(
            ['email' => strtolower(trim($row['email']))],
            [
                'name' => trim($row['name']),
                'password' => Hash::make($defaultPassword),
                'email_verified_at' => now(),
            ]
        );

        if (! $user->hasRole('student')) {
            $user->assignRole('student');
        }

        if ($user->wasRecentlyCreated) {
            $user->notify(new AccountCreatedNotification($defaultPassword, 'student'));
        }

        // Create or update student profile
        $profile = StudentProfile::updateOrCreate(
            ['matric_number' => strtoupper(trim($row['matric_number']))],
            [
                'user_id' => $user->id,
                'department_id' => $department->id,
                'level' => (int) $row['level'],
            ]
        );

        $isNew = $profile->wasRecentlyCreated;

        // Enroll in specified courses
        if (! empty($row['courses'])) {
            $courseCodes = array_map('trim', explode('|', $row['courses']));
            foreach ($courseCodes as $code) {
                $course = Course::where('code', strtoupper($code))->first();
                if ($course) {
                    $this->enroll($profile, $course, $academicSession, $semester);
                }
            }
        }

        $isNew ? $this->imported++ : $this->updated++;
    }

    protected function processEnrollmentRow(array $row, int $line, string $academicSession, string $semester): void
    {
        $validator = Validator::make($row, [
            'matric_number' => 'required|string|max:30',
            'course_code' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            $this->errors[] = "Row {$line}: " . implode(', ', $validator->errors()->all());
            $this->skipped++;
            return;
        }

        $profile = StudentProfile::where('matric_number', strtoupper(trim($row['matric_number'])))->first();
        if (! $profile) {
            $this->errors[] = "Row {$line}: Student '{$row['matric_number']}' not found.";
            $this->skipped++;
            return;
        }

        $course = Course::where('code', strtoupper(trim($row['course_code'])))->first();
        if (! $course) {
            $this->errors[] = "Row {$line}: Course '{$row['course_code']}' not found.";
            $this->skipped++;
            return;
        }

        $this->enroll($profile, $course, $academicSession, $semester);

        $this->imported++;
    }

    protected function parseCsv(UploadedFile $file): Collection
    {
        $rows = collect();
        $handle = fopen($file->getPathname(), 'r');

        if (! $handle) {
            return $rows;
        }

        $headers = fgetcsv($handle);
        if (! $headers) {
            fclose($handle);
            return $rows;
        }

        // Normalize headers (lowercase, trim, replace spaces with underscores)
        $headers = array_map(fn ($h) => strtolower(str_replace(' ', '_', trim($h))), $headers);

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) === count($headers)) {
                // Strip leading formula injection characters
                $data = array_map(fn ($val) => ltrim($val, "=+-@ \t\n\r\0\x0B"), $data);
                
                $rows->push(array_combine($headers, $data));
            }
        }

        fclose($handle);

        return $rows;
    }

    protected function enroll(StudentProfile $profile, Course $course, string $academicSession, string $semester): void
    {
        DB::table('course_student')->updateOrInsert(
            [
                'course_id' => $course->id,
                'student_profile_id' => $profile->id,
                'academic_session' => $academicSession,
                'semester' => $semester,
            ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    protected function results(?string $globalError = null): array
    {
        return [
            'success' => empty($globalError),
            'imported' => $this->imported,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'errors' => $globalError ? [$globalError] : $this->errors,
        ];
    }
}
