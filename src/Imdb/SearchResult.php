<?php

namespace App\Imdb;

use App\Enum\TitleType;

final readonly class SearchResult
{
    public function __construct(
        public string $imdbId,
        public TitleType $type,
        public string $name,
        public ?int $year,
        public ?string $posterUrl,
        public ?string $cast,
    ) {
    }
}
