<?php

namespace App\Streaming;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Runtime as IMDb shows it, from the OMDb API. Used for series, where IMDb has the episode length
 * ("1h") and the other APIs have none or only real episode lengths. Free key: 1,000 requests per day.
 *
 * @see https://www.omdbapi.com
 */
final readonly class OmdbRuntime
{
    public function __construct(
        #[Target('omdb.client')]
        private HttpClientInterface $client,
        private LoggerInterface $logger,
        #[Autowire(env: 'OMDB_API_KEY')]
        private string $apiKey,
    ) {
    }

    /** @return ?int minutes, null if unknown or OMDb couldn't be asked */
    public function fetch(string $imdbId): ?int
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

        return preg_match('/^(\d+) min/', $data['Runtime'] ?? '', $m) ? (int) $m[1] : null;
    }
}
