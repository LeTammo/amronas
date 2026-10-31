<?php

namespace App\Message;

/** Fetch details (genres, runtime, rating, …) and streaming offers of a title in the background. */
final readonly class RefreshTitle
{
    /** @param list<string> $countries countries to store streaming offers for */
    public function __construct(
        public int $titleId,
        public array $countries,
    ) {
    }
}
