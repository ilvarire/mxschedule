<?php

namespace App\Enums;

enum SeatStatus: string
{
    case Allocated = 'allocated';
    case CheckedIn = 'checked_in';
    case Completed = 'completed';
    case NoShow = 'no_show';
    case Reassigned = 'reassigned';

    public function label(): string
    {
        return match ($this) {
            self::Allocated => 'Allocated',
            self::CheckedIn => 'Checked In',
            self::Completed => 'Completed',
            self::NoShow => 'No Show',
            self::Reassigned => 'Reassigned',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Allocated => 'blue',
            self::CheckedIn => 'green',
            self::Completed => 'gray',
            self::NoShow => 'red',
            self::Reassigned => 'yellow',
        };
    }
}
