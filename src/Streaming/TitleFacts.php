<?php

namespace App\Streaming;

/** Basic data of a title from OMDb (IMDb) or TMDB, used when the Streaming Availability API doesn't know it. */
final readonly class TitleFacts
{
    /** @param list<string> $genres */
    public function __construct(
        public array $genres = [],
        public ?float $rating = null,
        public ?int $year = null,
        public ?int $endYear = null,
        public ?int $seasons = null,
        public ?string $plot = null,
        public ?int $runtime = null,
    ) {
    }

    public function isEmpty(): bool
    {
        return !$this->genres && null === $this->rating && null === $this->year && null === $this->seasons && null === $this->plot;
    }
}
