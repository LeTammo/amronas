<?php

namespace App\Streaming;

use App\Enum\TitleType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Streaming offers from TMDB's watch providers (data by JustWatch), for the services the Streaming Availability API
 * doesn't cover (ARD, ZDF, Joyn, …), plus the runtime (for series: of an episode, which the other APIs don't have).
 * Two requests per title: IMDb id → TMDB id, then the details with the providers of all countries.
 * TMDB has no prices and no links to the services, only one link to its own watch page.
 *
 * @see https://developer.themoviedb.org/reference/movie-watch-providers
 */
final readonly class TmdbProviders
{
    private const TYPES = ['flatrate' => 'subscription', 'free' => 'free', 'ads' => 'free', 'rent' => 'rent', 'buy' => 'buy'];

    public function __construct(
        #[Target('tmdb.client')]
        private HttpClientInterface $client,
        private LoggerInterface $logger,
        #[Autowire(env: 'TMDB_API_KEY')]
        private string $apiKey,
    ) {
    }

    /**
     * @param list<string> $countries
     *
     * @return ?TmdbDetails null if TMDB couldn't be asked
     */
    public function fetch(string $imdbId, TitleType $type, array $countries): ?TmdbDetails
    {
        if ('' === $this->apiKey) {
            return null;
        }

        try {
            $found = $this->get('/3/find/'.$imdbId, ['external_source' => 'imdb_id']);
            if (null === $found) {
                return null;
            }
            $kinds = TitleType::Series === $type ? ['tv', 'movie'] : ['movie', 'tv'];
            $kind = array_find($kinds, static fn (string $k) => !empty($found[$k.'_results']));
            if (!$kind) {
                return new TmdbDetails(array_fill_keys($countries, []), null);
            }

            $append = 'tv' === $kind ? 'watch/providers,season/1' : 'watch/providers';
            $details = $this->get(\sprintf('/3/%s/%d', $kind, $found[$kind.'_results'][0]['id']), ['append_to_response' => $append]);
            if (null === $details) {
                return null;
            }
        } catch (ExceptionInterface $e) {
            $this->logger->warning('TMDB request for {id} failed: {message}', ['id' => $imdbId, 'message' => $e->getMessage()]);

            return null;
        }

        $offers = [];
        foreach ($countries as $country) {
            $result = $details['watch/providers']['results'][strtoupper($country)] ?? [];
            $offers[$country] = [];
            foreach (self::TYPES as $key => $offerType) {
                foreach ($result[$key] ?? [] as $provider) {
                    $id = $provider['provider_id'];
                    $known = ServiceCatalog::TMDB_PROVIDERS[$id] ?? null;
                    if (!$known && isset(ServiceCatalog::TMDB_SUBSCRIPTIONS[$id]) && \in_array($offerType, ['subscription', 'free'], true)) {
                        $known = ['service' => ServiceCatalog::TMDB_SUBSCRIPTIONS[$id], 'name' => ServiceCatalog::name(ServiceCatalog::TMDB_SUBSCRIPTIONS[$id])];
                    }
                    if ($known) {
                        $offers[$country][] = [...$known, 'type' => $offerType, 'link' => $result['link'] ?? null, 'price' => null, 'color' => null];
                    }
                }
            }
        }

        return new TmdbDetails($offers, self::runtime($details), self::facts($details));
    }

    /** Genres, rating, years and seasons, for titles nobody else knows. TMDB's rating is its own users', so only with enough votes. */
    private static function facts(array $details): TitleFacts
    {
        $year = static fn (?string $date): ?int => $date ? (int) substr($date, 0, 4) : null;
        // TV genres combine two ("Action & Adventure", "Sci-Fi & Fantasy")
        $genres = array_merge(...array_map(static fn (array $g) => explode(' & ', $g['name']), $details['genres'] ?? []));

        return new TitleFacts(
            genres: array_values(array_unique($genres)),
            rating: ($details['vote_count'] ?? 0) >= 20 ? round((float) $details['vote_average'], 1) : null,
            year: $year($details['release_date'] ?? $details['first_air_date'] ?? null),
            endYear: 'Ended' === ($details['status'] ?? null) ? $year($details['last_air_date'] ?? null) : null,
            seasons: $details['number_of_seasons'] ?? null,
            plot: ($details['overview'] ?? '') ?: null,
            runtime: self::runtime($details),
        );
    }

    /**
     * Movies have a runtime. For series: the typical episode runtimes (often empty nowadays), else the median of the
     * first season's episodes (a long finale doesn't count), else the last episode's.
     */
    private static function runtime(array $details): ?int
    {
        if (isset($details['runtime'])) {
            return $details['runtime'] ?: null;
        }
        $runtimes = array_values(array_filter($details['episode_run_time'] ?? []));
        if ($runtimes) {
            return (int) round(array_sum($runtimes) / \count($runtimes));
        }
        $episodes = array_values(array_filter(array_column($details['season/1']['episodes'] ?? [], 'runtime')));
        if ($episodes) {
            sort($episodes);

            return $episodes[intdiv(\count($episodes), 2)];
        }

        return $details['last_episode_to_air']['runtime'] ?? null;
    }

    /** @return ?array<string, mixed> */
    private function get(string $path, array $query = []): ?array
    {
        $response = $this->client->request('GET', $path, ['query' => $query]);
        if (200 !== $response->getStatusCode()) {
            $this->logger->warning('TMDB returned {status} for {path}', ['status' => $response->getStatusCode(), 'path' => $path]);

            return null;
        }

        return $response->toArray();
    }
}
