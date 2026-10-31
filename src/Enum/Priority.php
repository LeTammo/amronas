<?php

namespace App\Enum;

enum Priority: string
{
    case Important = 'important';
    case Normal = 'normal';
    case Maybe = 'maybe';

    /** Important titles are listed first, maybes last. */
    public function rank(): int
    {
        return match ($this) {
            self::Important => 0,
            self::Normal => 1,
            self::Maybe => 2,
        };
    }
}
