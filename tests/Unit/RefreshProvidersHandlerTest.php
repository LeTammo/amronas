<?php

namespace App\Tests\Unit;

use App\Entity\Title;
use App\Enum\DetailsSource;
use App\Enum\TitleType;
use App\Message\RefreshProviders;
use App\MessageHandler\RefreshProvidersHandler;
use App\Repository\TitleRepository;
use App\Streaming\Omdb;
use App\Streaming\TmdbProviders;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

final class RefreshProvidersHandlerTest extends TestCase
{
    private const IMDB = ['Year' => '2026–', 'Runtime' => '45 min', 'Genre' => 'Drama, Sci-Fi', 'imdbRating' => '7.2', 'totalSeasons' => '1', 'Plot' => 'Space.', 'Response' => 'True'];

    private const TMDB = [
        'genres' => [['name' => 'Action & Adventure'], ['name' => 'Drama']], 'vote_average' => 6.84, 'vote_count' => 120,
        'first_air_date' => '2026-03-01', 'status' => 'Returning Series', 'number_of_seasons' => 1, 'overview' => 'Raumschiff.',
        'episode_run_time' => [50], 'watch/providers' => ['results' => []],
    ];

    public function testTitlesTheStreamingApiDoesntKnowGetImdbsData(): void
    {
        $title = new Title('tt39219651', TitleType::Series, 'New Show')->setYear(2026);

        $this->handle($title, self::IMDB, self::TMDB);

        self::assertSame(DetailsSource::Imdb, $title->getDetailsSource());
        self::assertTrue($title->hasFallbackDetails());
        self::assertSame(['Drama', 'Sci-Fi'], $title->getGenres());
        self::assertSame([7.2, 1, 45, 'Space.'], [$title->getRating(), $title->getSeasons(), $title->getRuntime(), $title->getPlot()]);
        self::assertNotNull($title->getDetailsFetchedAt());
    }

    public function testWithoutImdbDataTmdbsIsUsed(): void
    {
        $title = new Title('tt39219651', TitleType::Series, 'New Show');

        $this->handle($title, null, self::TMDB);

        self::assertSame(DetailsSource::Tmdb, $title->getDetailsSource());
        self::assertSame(['Action', 'Adventure', 'Drama'], $title->getGenres());
        self::assertSame([6.8, 2026, 50], [$title->getRating(), $title->getYear(), $title->getRuntime()]);
    }

    public function testDataFromTheStreamingApiIsKeptButImdbsRuntimeWins(): void
    {
        $title = new Title('tt1375666', TitleType::Movie, 'Inception')->setGenres(['Science Fiction'])->setRating(8.6)->setRuntime(148)
            ->setDetailsSource(DetailsSource::Streaming);

        $this->handle($title, ['Runtime' => '148 min', 'Genre' => 'Action', 'imdbRating' => '8.8'] + self::IMDB, null);

        self::assertSame([['Science Fiction'], 8.6, 148], [$title->getGenres(), $title->getRating(), $title->getRuntime()]);
        self::assertSame(DetailsSource::Streaming, $title->getDetailsSource());
    }

    /** @param ?array<string, mixed> $omdb  OMDb's answer, null for none
     *  @param ?array<string, mixed> $tmdb  TMDB's tv details, null for none */
    private function handle(Title $title, ?array $omdb, ?array $tmdb): void
    {
        $omdbClient = new MockHttpClient(static fn () => new JsonMockResponse($omdb ?? ['Response' => 'False']));
        $tmdbClient = new MockHttpClient(static fn (string $method, string $url) => match (true) {
            null === $tmdb => new MockResponse('', ['http_code' => 404]),
            str_contains($url, '/find/') => new JsonMockResponse(['movie_results' => [], 'tv_results' => [['id' => 5]]]),
            default => new JsonMockResponse($tmdb),
        });

        $titles = $this->createStub(TitleRepository::class);
        $titles->method('find')->willReturn($title);

        $handler = new RefreshProvidersHandler(
            $titles,
            new TmdbProviders($tmdbClient, new NullLogger(), 'key'),
            new Omdb($omdbClient, new NullLogger(), 'key'),
            $this->createStub(EntityManagerInterface::class),
        );
        $handler(new RefreshProviders(1, ['de']));
    }
}
