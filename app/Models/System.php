<?php

namespace App\Models;

use App\Enums\SystemStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class System extends Model
{
    use HasFactory;

    protected $fillable = [
        'hall_id',
        'system_code',
        'label',
        'status',
        'status_note',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SystemStatus::class,
            'last_used_at' => 'datetime',
        ];
    }

    public function hall()
    {
        return $this->belongsTo(Hall::class);
    }

    public function examAllocations()
    {
        return $this->hasMany(ExamAllocation::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(SystemStatusLog::class);
    }

    /**
     * Scope to only active systems in active halls.
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', SystemStatus::Active)
            ->whereHas('hall', fn ($q) => $q->where('is_active', true));
    }

    public function isActive(): bool
    {
        return $this->status === SystemStatus::Active;
    }
}
