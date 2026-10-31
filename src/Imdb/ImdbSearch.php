<?php

namespace App\Imdb;

use App\Enum\TitleType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Title search via IMDb's public suggestion endpoint (the same data the imdb8 RapidAPI returned, ~10x faster, no key).
 */
final readonly class ImdbSearch
{
    private const TYPES = [
        'movie' => TitleType::Movie,
        'tvMovie' => TitleType::Movie,
        'tvSeries' => TitleType::Series,
        'tvMiniSeries' => TitleType::Series,
    ];

    public function __construct(
        #[Target('imdb.client')]
        private HttpClientInterface $client,
        private CacheInterface $cache,
        private LoggerInterface $logger,
    ) {
    }

    /** @return list<SearchResult> */
    public function search(string $query, ?TitleType $type = null): array
    {
        $query = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $query)));
        if (mb_strlen($query) < 2) {
            return [];
        }

        $results = $this->cache->get('imdb_search_'.md5($query), function (ItemInterface $item) use ($query): array {
            $item->expiresAfter(86400);

            return $this->fetch($query);
        });

        return array_values(array_filter($results, static fn (SearchResult $r) => null === $type || $r->type === $type));
    }

    /** @return list<SearchResult> */
    private function fetch(string $query): array
    {
        try {
            $data = $this->client->request('GET', '/suggestion/x/'.rawurlencode($query).'.json')->toArray();
        } catch (ExceptionInterface $e) {
            $this->logger->warning('IMDb search failed: {message}', ['message' => $e->getMessage()]);

            return [];
        }

        $results = [];
        foreach ($data['d'] ?? [] as $item) {
            $type = self::TYPES[$item['qid'] ?? ''] ?? null;
            if (!$type || !str_starts_with($item['id'] ?? '', 'tt')) {
                continue;
            }
            $results[] = new SearchResult(
                imdbId: $item['id'],
                type: $type,
                name: $item['l'],
                year: $item['y'] ?? null,
                posterUrl: $item['i']['imageUrl'] ?? null,
                cast: ($item['s'] ?? '') ?: null,
            );
        }

        return $results;
    }
}
