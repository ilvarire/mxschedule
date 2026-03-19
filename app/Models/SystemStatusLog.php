<?php

namespace App\Models;

use App\Enums\SystemStatus;
use Illuminate\Database\Eloquent\Model;

class SystemStatusLog extends Model
{
    protected $fillable = [
        'system_id',
        'previous_status',
        'new_status',
        'changed_by',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'previous_status' => SystemStatus::class,
            'new_status' => SystemStatus::class,
        ];
    }

    public function system()
    {
        return $this->belongsTo(System::class);
    }

    public function changedByUser()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
