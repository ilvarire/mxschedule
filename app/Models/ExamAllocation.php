<?php

namespace App\Models;

use App\Enums\SeatStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_session_id',
        'student_profile_id',
        'system_id',
        'hall_id',
        'seat_status',
        'checked_in_at',
        'checked_in_by',
        'reassigned_from_id',
    ];

    protected function casts(): array
    {
        return [
            'seat_status' => SeatStatus::class,
            'checked_in_at' => 'datetime',
        ];
    }

    public function examSession()
    {
        return $this->belongsTo(ExamSession::class);
    }

    public function studentProfile()
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function system()
    {
        return $this->belongsTo(System::class);
    }

    public function hall()
    {
        return $this->belongsTo(Hall::class);
    }

    public function checkedInByUser()
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function reassignedFrom()
    {
        return $this->belongsTo(self::class, 'reassigned_from_id');
    }

    public function examPass()
    {
        return $this->hasOne(ExamPass::class);
    }

    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class);
    }

    /**
     * Quick access to parent exam via session.
     */
    public function getExamAttribute()
    {
        return $this->examSession?->exam;
    }
}
