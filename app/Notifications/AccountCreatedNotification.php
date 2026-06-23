<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountCreatedNotification extends Notification
{
    public function __construct(
        public string $plainPassword,
        public ?string $role = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Your MXSchedule account has been created')
            ->greeting("Hello {$notifiable->name},")
            ->line('An MXSchedule account has been created for you.')
            ->line("Email: {$notifiable->email}")
            ->line("Temporary password: {$this->plainPassword}");

        if ($this->role) {
            $message->line('Role: '.ucwords(str_replace('_', ' ', $this->role)));
        }

        return $message
            ->action('Log in to MXSchedule', route('login'))
            ->line('Please log in and change your password from your profile page.');
    }
}
