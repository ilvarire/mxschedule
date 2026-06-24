<?php

namespace App\Models;

use App\Services\ExamPassService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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
        'pdf_generation_requested_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_used' => 'boolean',
            'used_at' => 'datetime',
            'pdf_generation_requested_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function allocation()
    {
        return $this->belongsTo(ExamAllocation::class, 'exam_allocation_id');
    }

    public function isPdfReady(): bool
    {
        return $this->pdf_path
            && str_starts_with($this->pdf_path, 'exam-passes/' . ExamPassService::PDF_TEMPLATE_VERSION . '_')
            && Storage::disk('public')->exists($this->pdf_path);
    }

    public function needsPdfPreparation(): bool
    {
        return ! $this->isPdfReady();
    }

    public function isPdfGenerationPending(): bool
    {
        return $this->pdf_generation_requested_at !== null && $this->needsPdfPreparation();
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
