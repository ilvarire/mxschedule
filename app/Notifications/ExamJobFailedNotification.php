<?php

namespace App\Notifications;

use App\Models\Exam;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExamJobFailedNotification extends Notification
{
    public function __construct(
        public Exam $exam,
        public string $jobName,
        public string $errorMessage,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->exam->loadMissing('course');
        $course = $this->exam->course;

        return (new MailMessage)
            ->subject("MXSchedule alert: {$this->jobName} failed")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->jobName} failed for {$course->code} - {$course->title}.")
            ->line("Exam date: {$this->exam->exam_date->format('l, F j, Y')}")
            ->line("Error: {$this->errorMessage}")
            ->action('Open Exam', route('admin.exams.show', $this->exam))
            ->line('Please review the exam and retry the workflow after resolving the issue.');
    }

    public function toArray(object $notifiable): array
    {
        $this->exam->loadMissing('course');

        return [
            'exam_id' => $this->exam->id,
            'course_code' => $this->exam->course->code,
            'job_name' => $this->jobName,
            'message' => "{$this->jobName} failed for {$this->exam->course->code}.",
        ];
    }
}
