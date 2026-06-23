<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class AdminNotificationService
{
    /**
     * Notify active operational admins about events that need attention.
     */
    public function notify(Notification $notification): void
    {
        $admins = User::role(['super_admin', 'exam_officer', 'ict_admin'])
            ->where('is_active', true)
            ->get();

        if ($admins->isEmpty()) {
            return;
        }

        NotificationFacade::send($admins, $notification);
    }
}
