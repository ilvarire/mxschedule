<?php

namespace App\Notifications;

use App\Models\Exam;
use App\Models\ExamAllocation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExamReminderNotification extends Notification
{
    public function __construct(
        public Exam $exam,
        public ExamAllocation $allocation,
        public int $hoursBefore,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->allocation->loadMissing(['examSession', 'hall', 'system']);
        $this->exam->loadMissing('course');

        $session = $this->allocation->examSession;
        $course = $this->exam->course;
        $when = $this->hoursBefore === 1 ? 'in about 1 hour' : "in about {$this->hoursBefore} hours";

        return (new MailMessage)
            ->subject("Exam reminder: {$course->code}")
            ->greeting("Hello {$notifiable->name},")
            ->line("This is a reminder that your {$course->code} - {$course->title} exam starts {$when}.")
            ->line("Date: {$this->exam->exam_date->format('l, F j, Y')}")
            ->line("Time: {$session->start_time->format('g:i A')} - {$session->end_time->format('g:i A')}")
            ->line("Hall: {$this->allocation->hall->name}")
            ->line("System: {$this->allocation->system->system_code}")
            ->action('View Your Exam Pass', route('student.exam-pass.show', $this->allocation))
            ->line('Please arrive early and bring your valid exam pass.');
    }

    public function toArray(object $notifiable): array
    {
        $this->exam->loadMissing('course');

        return [
            'exam_id' => $this->exam->id,
            'allocation_id' => $this->allocation->id,
            'course_code' => $this->exam->course->code,
            'hours_before' => $this->hoursBefore,
            'exam_date' => $this->exam->exam_date->toDateString(),
            'message' => "Reminder: {$this->exam->course->code} starts in about {$this->hoursBefore} hour(s).",
        ];
    }
}
