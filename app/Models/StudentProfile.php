<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentProfile extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'matric_number', 'department_id', 'level'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_student')
            ->withPivot('academic_session', 'semester')
            ->withTimestamps();
    }

    public function examAllocations()
    {
        return $this->hasMany(ExamAllocation::class);
    }

    /**
     * Get the full display name including matric number.
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->user->name} ({$this->matric_number})";
    }
}
