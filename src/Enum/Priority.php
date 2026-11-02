<?php

namespace App\Enum;

enum Priority: string
{
    case Important = 'important';
    /** Not released yet: details and offers can be fetched again once it is out. */
    case Waiting = 'waiting';
    case Normal = 'normal';
    case Maybe = 'maybe';

    /** Important titles are listed first, then the ones waiting for their release, maybes last. */
    public function rank(): int
    {
        return match ($this) {
            self::Important => 0,
            self::Waiting => 1,
            self::Normal => 2,
            self::Maybe => 3,
        };
    }
}
