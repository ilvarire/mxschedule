<?php

namespace App\Enums;

enum ExamStatus: string
{
    case Draft = 'draft';
    case Scheduling = 'scheduling';
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Scheduling => 'Scheduling…',
            self::Scheduled => 'Scheduled',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Scheduling => 'yellow',
            self::Scheduled => 'blue',
            self::InProgress => 'orange',
            self::Completed => 'green',
            self::Cancelled => 'red',
        };
    }
}
