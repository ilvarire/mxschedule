<?php

namespace App\Enums;

enum SystemStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Faulty = 'faulty';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Faulty => 'Faulty',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Inactive => 'gray',
            self::Faulty => 'red',
        };
    }
}
