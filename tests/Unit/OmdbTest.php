<?php

namespace App\Tests\Unit;

use App\Streaming\Omdb;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;

final class OmdbTest extends TestCase
{
    public function testReadsWhatImdbShows(): void
    {
        $client = new MockHttpClient(static function (string $method, string $url) {
            self::assertStringContainsString('i=tt27995115', $url);

            return new JsonMockResponse([
                'Title' => "The Devil's Plan", 'Year' => '2023–2025', 'Runtime' => '60 min', 'Genre' => 'Game-Show, Reality-TV',
                'Plot' => 'Twelve players compete.', 'imdbRating' => '7.9', 'totalSeasons' => '2', 'Response' => 'True',
            ]);
        }, 'https://www.omdbapi.com');

        $facts = new Omdb($client, new NullLogger(), 'key')->fetch('tt27995115');

        self::assertSame(60, $facts->runtime);
        self::assertSame(['Game-Show', 'Reality-TV'], $facts->genres);
        self::assertSame(7.9, $facts->rating);
        self::assertSame([2023, 2025], [$facts->year, $facts->endYear]);
        self::assertSame(2, $facts->seasons);
        self::assertSame('Twelve players compete.', $facts->plot);
    }

    public function testMissingValuesAndUnknownTitles(): void
    {
        $client = new MockHttpClient([
            new JsonMockResponse(['Year' => '2027–', 'Runtime' => 'N/A', 'Genre' => 'N/A', 'imdbRating' => 'N/A', 'Plot' => 'N/A', 'Response' => 'True']),
            new JsonMockResponse(['Response' => 'False', 'Error' => 'Incorrect IMDb ID.']),
        ]);
        $omdb = new Omdb($client, new NullLogger(), 'key');

        $facts = $omdb->fetch('tt1');
        self::assertSame([2027, null, null, null, [], null], [$facts->year, $facts->endYear, $facts->runtime, $facts->rating, $facts->genres, $facts->plot]);
        self::assertNull($omdb->fetch('tt2'));
    }

    public function testNoRequestWithoutKey(): void
    {
        $client = new MockHttpClient(static fn () => self::fail('No request expected'));

        self::assertNull(new Omdb($client, new NullLogger(), '')->fetch('tt1'));
    }
}
