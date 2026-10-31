<?php

namespace App\Streaming;

use App\Enum\TitleType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Movie of the Night "Streaming Availability API" v4. One request returns details plus the streaming offers of all countries.
 *
 * @see https://docs.movieofthenight.com/resource/shows
 */
final readonly class StreamingAvailability
{
    public function __construct(
        #[Target('streaming.client')]
        private HttpClientInterface $client,
        private LoggerInterface $logger,
    ) {
    }

    /** @param ?string $country only return offers for this country (smaller response), null for all countries */
    public function fetch(string $imdbId, ?string $country = null): ?ShowDetails
    {
        try {
            $response = $this->client->request('GET', '/v4/shows/'.$imdbId, [
                'query' => array_filter(['series_granularity' => 'show', 'country' => $country]),
            ]);
            if (200 !== $response->getStatusCode()) {
                $this->logger->warning('Streaming API returned {status} for {id}', ['status' => $response->getStatusCode(), 'id' => $imdbId]);

                return null;
            }

            return $this->map($response->toArray());
        } catch (ExceptionInterface $e) {
            $this->logger->warning('Streaming API request for {id} failed: {message}', ['id' => $imdbId, 'message' => $e->getMessage()]);

            return null;
        }
    }

    /** "9.99" + "EUR" → "9,99 €" */
    private static function formatPrice(array $price): ?string
    {
        if (!isset($price['amount'], $price['currency'])) {
            return $price['formatted'] ?? null;
        }
        $amount = (float) $price['amount'];

        return match ($price['currency']) {
            'EUR' => number_format($amount, 2, ',', '.').' €',
            'USD' => '$'.number_format($amount, 2),
            'GBP' => '£'.number_format($amount, 2),
            default => $price['currency'].' '.number_format($amount, 2),
        };
    }

    /** @param array<string, mixed> $show */
    private function map(array $show): ShowDetails
    {
        $isSeries = 'series' === ($show['showType'] ?? null);
        $offersByCountry = [];
        foreach ($show['streamingOptions'] ?? [] as $country => $options) {
            foreach ($options as $option) {
                $offersByCountry[strtolower($country)][] = [
                    'service' => $option['service']['id'] ?? 'unknown',
                    'name' => $option['addon']['name'] ?? $option['service']['name'] ?? '?',
                    'type' => $option['type'] ?? 'subscription',
                    'link' => $option['link'] ?? null,
                    'price' => isset($option['price']) ? self::formatPrice($option['price']) : null,
                    'amount' => isset($option['price']['amount']) ? (float) $option['price']['amount'] : null,
                    'color' => $option['service']['themeColorCode'] ?? null,
                ];
            }
        }

        return new ShowDetails(
            type: $isSeries ? TitleType::Series : TitleType::Movie,
            title: $show['title'] ?? '',
            originalTitle: $show['originalTitle'] ?? null,
            overview: $show['overview'] ?? null,
            year: $isSeries ? ($show['firstAirYear'] ?? null) : ($show['releaseYear'] ?? null),
            endYear: $show['lastAirYear'] ?? null,
            runtime: $show['runtime'] ?? null,
            seasons: $show['seasonCount'] ?? null,
            genres: array_values(array_filter(array_map(static fn (array $g) => $g['name'] ?? null, $show['genres'] ?? []))),
            rating: isset($show['rating']) ? round($show['rating'] / 10, 1) : null,
            posterUrl: $show['imageSet']['verticalPoster']['w240'] ?? null,
            offersByCountry: $offersByCountry,
        );
    }
}
