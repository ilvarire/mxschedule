<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountCreatedNotification extends Notification
{
    public function __construct(
        public string $setupUrl,
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
            ->line('Use the secure setup link below to create your password.');

        if ($this->role) {
            $message->line('Role: '.ucwords(str_replace('_', ' ', $this->role)));
        }

        return $message
            ->action('Create Your Password', $this->setupUrl)
            ->line('This link expires in '.config('auth.passwords.users.expire').' minutes. If it expires, use the forgot-password page to request a new one.');
    }
}
