<?php

namespace App\Notifications;

use App\Models\ExamAllocation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentReallocatedNotification extends Notification
{
    public function __construct(
        public ExamAllocation $newAllocation,
        public ExamAllocation $oldAllocation,
        public string $reason = 'Your exam seat has been updated.',
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->newAllocation->loadMissing(['examSession.exam.course', 'hall', 'system']);
        $this->oldAllocation->loadMissing(['hall', 'system']);

        $session = $this->newAllocation->examSession;
        $exam = $session->exam;
        $course = $exam->course;

        return (new MailMessage)
            ->subject("Exam seat changed: {$course->code}")
            ->greeting("Hello {$notifiable->name},")
            ->line($this->reason)
            ->line("Course: {$course->code} - {$course->title}")
            ->line("Date: {$exam->exam_date->format('l, F j, Y')}")
            ->line("Time: {$session->start_time->format('g:i A')} - {$session->end_time->format('g:i A')}")
            ->line("Previous seat: {$this->oldAllocation->hall->name} / {$this->oldAllocation->system->system_code}")
            ->line("New seat: {$this->newAllocation->hall->name} / {$this->newAllocation->system->system_code}")
            ->action('View Updated Exam Pass', route('student.exam-pass.show', $this->newAllocation))
            ->line('Please use the updated exam pass for entry.');
    }

    public function toArray(object $notifiable): array
    {
        $this->newAllocation->loadMissing(['examSession.exam.course', 'hall', 'system']);

        return [
            'exam_id' => $this->newAllocation->examSession->exam_id,
            'allocation_id' => $this->newAllocation->id,
            'old_allocation_id' => $this->oldAllocation->id,
            'course_code' => $this->newAllocation->examSession->exam->course->code,
            'message' => "Your seat for {$this->newAllocation->examSession->exam->course->code} has been changed.",
        ];
    }
}
