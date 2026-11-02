<?php

namespace App\Message;

/** Fetch the streaming offers TMDB knows about (ARD, ZDF, Joyn, …) and IMDb's runtime in the background; fills in genres and rating if the streaming API doesn't know the title. */
final readonly class RefreshProviders
{
    /** @param list<string> $countries countries to store streaming offers for */
    public function __construct(
        public int $titleId,
        public array $countries,
    ) {
    }
}
