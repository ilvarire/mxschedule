<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\StudentProfile;
use Illuminate\Database\Eloquent\Builder;

class ExamRegistrationService
{
    public function registeredStudentsQuery(Exam $exam): Builder
    {
        return StudentProfile::whereHas('courses', function ($query) use ($exam) {
            $query
                ->where('course_student.course_id', $exam->course_id)
                ->whereRaw('TRIM(course_student.academic_session) = ?', [$this->normalizeAcademicSession($exam->academic_session)])
                ->whereRaw('LOWER(TRIM(course_student.semester)) = ?', [$this->normalizeSemester($exam->semester->value)]);
        });
    }

    public function registeredStudentCount(Exam $exam): int
    {
        return $this->registeredStudentsQuery($exam)->count();
    }

    public function normalizeAcademicSession(?string $value): string
    {
        return trim((string) $value);
    }

    public function normalizeSemester(?string $value): string
    {
        return strtolower(trim((string) $value));
    }
}
