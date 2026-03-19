<?php

namespace App\Policies;

use App\Models\Hall;
use App\Models\User;

class HallPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'ict_admin', 'exam_officer']);
    }

    public function view(User $user, Hall $hall): bool
    {
        return $user->hasAnyRole(['super_admin', 'ict_admin', 'exam_officer', 'invigilator']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_halls');
    }

    public function update(User $user, Hall $hall): bool
    {
        return $user->hasPermissionTo('manage_halls');
    }

    public function delete(User $user, Hall $hall): bool
    {
        return $user->hasPermissionTo('manage_halls');
    }
}
