<?php

namespace App\Streaming;

/** What TMDB adds to a title: offers of the services the Streaming Availability API doesn't cover, the runtime, and basic data as a last fallback. */
final readonly class TmdbDetails
{
    /**
     * @param array<string, list<array{service: string, name: string, type: string, link: ?string, price: null, color: null}>> $offersByCountry
     * @param ?int                                                                                                              $runtime         minutes; for series the average episode
     */
    public function __construct(
        public array $offersByCountry,
        public ?int $runtime,
        public ?TitleFacts $facts = null,
    ) {
    }
}
