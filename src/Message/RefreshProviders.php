<?php

namespace App\Message;

/** Fetch the streaming offers TMDB knows about (ARD, ZDF, Joyn, …) in the background. */
final readonly class RefreshProviders
{
    /** @param list<string> $countries countries to store streaming offers for */
    public function __construct(
        public int $titleId,
        public array $countries,
    ) {
    }
}
