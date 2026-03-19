<?php

namespace App\Models;

use App\Enums\ScanResult;
use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    protected $fillable = [
        'exam_allocation_id',
        'scanned_by',
        'scan_result',
        'scanned_at',
        'device_info',
        'ip_address',
        'synced_from_offline',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scan_result' => ScanResult::class,
            'scanned_at' => 'datetime',
            'synced_from_offline' => 'boolean',
        ];
    }

    public function allocation()
    {
        return $this->belongsTo(ExamAllocation::class, 'exam_allocation_id');
    }

    public function scanner()
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }
}
