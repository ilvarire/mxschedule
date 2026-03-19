<?php

namespace App\Enums;

enum ScanResult: string
{
    case Valid = 'valid';
    case InvalidPass = 'invalid_pass';
    case WrongSlot = 'wrong_slot';
    case Expired = 'expired';
    case Duplicate = 'duplicate';
    case Early = 'early';
    case Late = 'late';

    public function label(): string
    {
        return match ($this) {
            self::Valid => '✅ Valid',
            self::InvalidPass => '❌ Invalid Pass',
            self::WrongSlot => '❌ Wrong Time Slot',
            self::Expired => '❌ Pass Expired',
            self::Duplicate => '⚠️ Already Scanned',
            self::Early => '⚠️ Too Early',
            self::Late => '⚠️ Too Late',
        };
    }

    public function isSuccessful(): bool
    {
        return $this === self::Valid;
    }
}
