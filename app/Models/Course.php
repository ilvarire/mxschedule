<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = ['department_id', 'code', 'title', 'credit_units'];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function studentProfiles()
    {
        return $this->belongsToMany(StudentProfile::class, 'course_student')
            ->withPivot('academic_session', 'semester')
            ->withTimestamps();
    }

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }
}
