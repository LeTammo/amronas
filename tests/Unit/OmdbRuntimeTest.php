<?php

namespace App\Tests\Unit;

use App\Streaming\OmdbRuntime;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;

final class OmdbRuntimeTest extends TestCase
{
    public function testReadsTheRuntimeImdbShows(): void
    {
        $client = new MockHttpClient(static function (string $method, string $url) {
            self::assertStringContainsString('i=tt27995115', $url);

            return new JsonMockResponse(['Title' => "The Devil's Plan", 'Runtime' => '60 min', 'Response' => 'True']);
        }, 'https://www.omdbapi.com');

        self::assertSame(60, new OmdbRuntime($client, new NullLogger(), 'key')->fetch('tt27995115'));
    }

    public function testUnknownRuntimeOrTitle(): void
    {
        $client = new MockHttpClient([
            new JsonMockResponse(['Runtime' => 'N/A', 'Response' => 'True']),
            new JsonMockResponse(['Response' => 'False', 'Error' => 'Incorrect IMDb ID.']),
        ]);
        $omdb = new OmdbRuntime($client, new NullLogger(), 'key');

        self::assertNull($omdb->fetch('tt1'));
        self::assertNull($omdb->fetch('tt2'));
    }

    public function testNoRequestWithoutKey(): void
    {
        $client = new MockHttpClient(static fn () => self::fail('No request expected'));

        self::assertNull(new OmdbRuntime($client, new NullLogger(), '')->fetch('tt1'));
    }
}
