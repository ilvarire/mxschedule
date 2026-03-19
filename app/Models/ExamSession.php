<?php

namespace App\Models;

use App\Enums\SessionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'session_number',
        'start_time',
        'end_time',
        'max_capacity',
        'allocated_count',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'status' => SessionStatus::class,
            'session_number' => 'integer',
            'max_capacity' => 'integer',
            'allocated_count' => 'integer',
        ];
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function allocations()
    {
        return $this->hasMany(ExamAllocation::class);
    }

    /**
     * Get remaining capacity.
     */
    public function getRemainingCapacityAttribute(): int
    {
        return $this->max_capacity - $this->allocated_count;
    }

    /**
     * Check if session is currently active (within time window).
     */
    public function isWithinWindow(int $windowMinutes = 15): bool
    {
        $now = now();
        return $now->between(
            $this->start_time->subMinutes($windowMinutes),
            $this->end_time->addMinutes($windowMinutes)
        );
    }
}
