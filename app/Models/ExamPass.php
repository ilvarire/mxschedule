<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamPass extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_allocation_id',
        'pass_code',
        'qr_payload',
        'is_used',
        'used_at',
        'pdf_path',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_used' => 'boolean',
            'used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function allocation()
    {
        return $this->belongsTo(ExamAllocation::class, 'exam_allocation_id');
    }

    /**
     * Check if pass is still valid (not used and not expired).
     */
    public function isValid(): bool
    {
        return ! $this->is_used && $this->expires_at->isFuture();
    }

    /**
     * Mark as used.
     */
    public function markAsUsed(): void
    {
        $this->update([
            'is_used' => true,
            'used_at' => now(),
        ]);
    }
}
