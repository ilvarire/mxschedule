<?php

namespace App\Models;

use App\Enums\ExamStatus;
use App\Enums\Semester;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exam extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'course_id',
        'academic_session',
        'semester',
        'exam_date',
        'duration_minutes',
        'buffer_minutes',
        'start_time',
        'total_registered_students',
        'status',
        'scheduled_at',
        'scheduled_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => ExamStatus::class,
            'semester' => Semester::class,
            'exam_date' => 'date',
            'scheduled_at' => 'datetime',
            'duration_minutes' => 'integer',
            'buffer_minutes' => 'integer',
            'total_registered_students' => 'integer',
        ];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function scheduler()
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }

    public function sessions()
    {
        return $this->hasMany(ExamSession::class)->orderBy('session_number');
    }

    public function allocations()
    {
        return $this->hasManyThrough(ExamAllocation::class, ExamSession::class);
    }

    protected function startTime(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? substr($value, 0, 5) : null,
        );
    }

    /**
     * Scope for upcoming exams.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('exam_date', '>=', now()->toDateString());
    }

    /**
     * Scope for scheduled exams.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', ExamStatus::Scheduled);
    }

    /**
     * Check if the exam can be scheduled.
     */
    public function canBeScheduled(): bool
    {
        return in_array($this->status, [ExamStatus::Draft, ExamStatus::Cancelled]);
    }

    /**
     * Check if the exam schedule can be modified.
     */
    public function canBeModified(): bool
    {
        return in_array($this->status, [ExamStatus::Draft, ExamStatus::Scheduled]);
    }
}
