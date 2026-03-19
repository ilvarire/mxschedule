<?php

namespace App\Policies;

use App\Models\ExamAllocation;
use App\Models\User;

class ExamPassPolicy
{
    /**
     * Students can only view/download their own pass.
     */
    public function view(User $user, ExamAllocation $allocation): bool
    {
        if ($user->hasRole('student')) {
            return $user->studentProfile?->id === $allocation->student_profile_id;
        }

        return $user->hasAnyRole(['super_admin', 'exam_officer']);
    }

    public function download(User $user, ExamAllocation $allocation): bool
    {
        return $this->view($user, $allocation);
    }
}
