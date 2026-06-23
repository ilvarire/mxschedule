<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountCreationOtpNotification extends Notification
{
    public function __construct(
        public string $code,
        public int $expiresInMinutes = 10,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your MXSchedule account verification code')
            ->greeting('Hello,')
            ->line('Use this one-time code to finish creating your MXSchedule account:')
            ->line($this->code)
            ->line("This code expires in {$this->expiresInMinutes} minutes.")
            ->line('If you did not request this account, you can safely ignore this email.');
    }
}
