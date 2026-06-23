<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\User;

class ExamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'exam_officer']);
    }

    public function view(User $user, Exam $exam): bool
    {
        return $user->hasAnyRole(['super_admin', 'exam_officer', 'ict_admin', 'invigilator']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_exams');
    }

    public function update(User $user, Exam $exam): bool
    {
        return $user->hasPermissionTo('edit_exams') && $exam->canBeModified();
    }

    public function delete(User $user, Exam $exam): bool
    {
        return $user->hasPermissionTo('delete_exams') && $exam->canBeModified();
    }

    public function schedule(User $user, Exam $exam): bool
    {
        return $user->hasPermissionTo('trigger_scheduling') && $exam->canBeScheduled();
    }

    public function reschedule(User $user, Exam $exam): bool
    {
        return $user->hasPermissionTo('trigger_scheduling')
            && $exam->status === \App\Enums\ExamStatus::Scheduled;
    }

    public function sendNotifications(User $user, Exam $exam): bool
    {
        return $user->hasPermissionTo('send_notifications')
            && $exam->status === \App\Enums\ExamStatus::Scheduled;
    }
}
