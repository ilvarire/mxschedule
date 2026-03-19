<?php

namespace App\Models;

use App\Enums\SystemStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hall extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'location', 'capacity', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'capacity' => 'integer',
        ];
    }

    public function systems()
    {
        return $this->hasMany(System::class);
    }

    public function activeSystems()
    {
        return $this->hasMany(System::class)->where('status', SystemStatus::Active);
    }

    public function examAllocations()
    {
        return $this->hasMany(ExamAllocation::class);
    }

    /**
     * Get count of currently active systems.
     */
    public function getActiveSystemCountAttribute(): int
    {
        return $this->activeSystems()->count();
    }
}
