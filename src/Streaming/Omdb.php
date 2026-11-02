<?php

namespace App\Streaming;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * IMDb's data from the OMDb API: the runtime as IMDb shows it (for series the episode length, "1h"), and genres,
 * rating, seasons and plot for titles the Streaming Availability API doesn't know. Free key: 1,000 requests per day.
 *
 * @see https://www.omdbapi.com
 */
final readonly class Omdb
{
    public function __construct(
        #[Target('omdb.client')]
        private HttpClientInterface $client,
        private LoggerInterface $logger,
        #[Autowire(env: 'OMDB_API_KEY')]
        private string $apiKey,
    ) {
    }

    /** @return ?TitleFacts null if OMDb doesn't know the title or couldn't be asked */
    public function fetch(string $imdbId): ?TitleFacts
    {
        if ('' === $this->apiKey) {
            return null;
        }

        try {
            $response = $this->client->request('GET', '/', ['query' => ['i' => $imdbId]]);
            $data = 200 === $response->getStatusCode() ? $response->toArray(false) : [];
        } catch (ExceptionInterface $e) {
            $this->logger->warning('OMDb request for {id} failed: {message}', ['id' => $imdbId, 'message' => $e->getMessage()]);

            return null;
        }
        if ('True' !== ($data['Response'] ?? null)) {
            return null;
        }

        // Missing values are "N/A"; years look like "2019", "2019–2023" or "2024–"
        $value = static fn (string $key): ?string => isset($data[$key]) && 'N/A' !== $data[$key] ? $data[$key] : null;
        preg_match('/^(\d{4})(?:\D+(\d{4}))?/', $value('Year') ?? '', $years);

        return new TitleFacts(
            genres: $value('Genre') ? array_map(trim(...), explode(',', $value('Genre'))) : [],
            rating: is_numeric($value('imdbRating')) ? (float) $value('imdbRating') : null,
            year: isset($years[1]) ? (int) $years[1] : null,
            endYear: isset($years[2]) ? (int) $years[2] : null,
            seasons: is_numeric($value('totalSeasons')) ? (int) $value('totalSeasons') : null,
            plot: $value('Plot'),
            runtime: preg_match('/^(\d+) min/', $value('Runtime') ?? '', $m) ? (int) $m[1] : null,
        );
    }
}
