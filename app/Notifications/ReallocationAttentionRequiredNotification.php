<?php

namespace App\Notifications;

use App\Models\ExamAllocation;
use App\Models\System;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReallocationAttentionRequiredNotification extends Notification
{
    public function __construct(
        public ExamAllocation $allocation,
        public System $faultySystem,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->allocation->loadMissing(['examSession.exam.course', 'studentProfile.user', 'hall', 'system']);
        $exam = $this->allocation->examSession->exam;
        $course = $exam->course;
        $student = $this->allocation->studentProfile->user;

        return (new MailMessage)
            ->subject("Manual reallocation needed: {$course->code}")
            ->greeting("Hello {$notifiable->name},")
            ->line("MXSchedule could not automatically reassign {$student->name} after {$this->faultySystem->system_code} became unavailable.")
            ->line("Course: {$course->code} - {$course->title}")
            ->line("Exam date: {$exam->exam_date->format('l, F j, Y')}")
            ->line("Current hall/system: {$this->allocation->hall->name} / {$this->allocation->system->system_code}")
            ->action('Open Allocations', route('admin.exams.allocations', $exam))
            ->line('Please assign the student to an available system manually.');
    }

    public function toArray(object $notifiable): array
    {
        $this->allocation->loadMissing(['examSession.exam.course', 'studentProfile.user']);

        return [
            'exam_id' => $this->allocation->examSession->exam_id,
            'allocation_id' => $this->allocation->id,
            'student_id' => $this->allocation->student_profile_id,
            'faulty_system_id' => $this->faultySystem->id,
            'course_code' => $this->allocation->examSession->exam->course->code,
            'message' => "Manual reallocation needed for {$this->allocation->studentProfile->user->name}.",
        ];
    }
}
