<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_active',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ─────────────────────────────

    public function studentProfile()
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function attendanceLogsScanned()
    {
        return $this->hasMany(AttendanceLog::class, 'scanned_by');
    }

    // ── Helpers ───────────────────────────────────

    public function isStudent(): bool
    {
        return $this->hasRole('student');
    }

    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['super_admin', 'exam_officer', 'ict_admin']);
    }
}
