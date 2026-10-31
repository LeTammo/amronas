<?php

namespace App\Enum;

enum ListType: string
{
    case Movies = 'movies';
    case Series = 'series';
    case Mixed = 'mixed';

    /** The only title type this list accepts, or null if it accepts both. */
    public function titleType(): ?TitleType
    {
        return match ($this) {
            self::Movies => TitleType::Movie,
            self::Series => TitleType::Series,
            self::Mixed => null,
        };
    }

    public function accepts(TitleType $type): bool
    {
        return null === $this->titleType() || $this->titleType() === $type;
    }
}
