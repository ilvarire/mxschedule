<?php

namespace App\Notifications;

use App\Models\Exam;
use App\Models\ExamAllocation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ScheduleReleasedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Exam $exam,
        public ExamAllocation $allocation,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $session = $this->allocation->examSession;
        $course = $this->exam->course;

        return (new MailMessage)
            ->subject("Exam Schedule Released: {$course->code}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your exam schedule for **{$course->code} - {$course->title}** has been released.")
            ->line("**Date:** {$this->exam->exam_date->format('l, F j, Y')}")
            ->line("**Time:** {$session->start_time->format('g:i A')} - {$session->end_time->format('g:i A')}")
            ->line("**Hall:** {$this->allocation->hall->name}")
            ->line("**System:** {$this->allocation->system->system_code}")
            ->action('View Your Exam Pass', url("/student/exam-pass/{$this->allocation->id}"))
            ->line('Please download and print your exam pass before the exam.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'exam_id' => $this->exam->id,
            'allocation_id' => $this->allocation->id,
            'course_code' => $this->exam->course->code,
            'exam_date' => $this->exam->exam_date->toDateString(),
            'message' => "Exam schedule released for {$this->exam->course->code}",
        ];
    }
}
