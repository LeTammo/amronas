<?php

namespace App\Streaming;

use App\Enum\TitleType;

/** Details and streaming offers of one title, as returned by the Streaming Availability API. */
final readonly class ShowDetails
{
    /**
     * @param list<string>                                                                                  $genres
     * @param array<string, list<array{service: string, name: string, type: string, link: ?string, price: ?string, color: ?string}>> $offersByCountry
     */
    public function __construct(
        public TitleType $type,
        public string $title,
        public ?string $originalTitle,
        public ?string $overview,
        public ?int $year,
        public ?int $endYear,
        public ?int $runtime,
        public ?int $seasons,
        public array $genres,
        public ?float $rating,
        public ?string $posterUrl,
        public array $offersByCountry,
    ) {
    }
}
