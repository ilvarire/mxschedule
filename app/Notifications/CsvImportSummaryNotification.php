<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class CsvImportSummaryNotification extends Notification
{
    /**
     * @param  array{success: bool, imported: int, updated: int, skipped: int, errors: array<int, string>}  $results
     */
    public function __construct(
        public array $results,
        public string $importType,
        public string $academicSession,
        public string $semester,
        public ?User $uploadedBy = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label = Str::headline($this->importType);
        $hasIssues = ! $this->results['success']
            || $this->results['skipped'] > 0
            || count($this->results['errors']) > 0;
        $status = $hasIssues ? 'needs attention' : 'completed';

        $message = (new MailMessage)
            ->subject("CSV import {$status}: {$label}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your {$label} CSV import for {$this->academicSession} ({$this->semester} semester) {$status}.")
            ->line("Imported: {$this->results['imported']}")
            ->line("Updated: {$this->results['updated']}")
            ->line("Skipped: {$this->results['skipped']}");

        if ($this->uploadedBy) {
            $message->line("Uploaded by: {$this->uploadedBy->name} ({$this->uploadedBy->email})");
        }

        foreach (array_slice($this->results['errors'], 0, 5) as $error) {
            $message->line("Issue: {$error}");
        }

        if (count($this->results['errors']) > 5) {
            $remaining = count($this->results['errors']) - 5;
            $message->line("There are {$remaining} more issue(s). Check the import page for the full list.");
        }

        return $message->action('View Import Page', route('admin.import.index'));
    }
}
