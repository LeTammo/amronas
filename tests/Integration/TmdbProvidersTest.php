<?php

namespace App\Tests\Integration;

use App\Entity\Availability;
use App\Entity\Title;
use App\Enum\TitleType;
use App\Streaming\StreamingAvailability;
use App\Streaming\TmdbProviders;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Runs against the saved responses in tests/Fixtures/api (API_RECORDINGS=replay), never the network. */
final class TmdbProvidersTest extends KernelTestCase
{
    public function testSubscriptionsOfAllServicesAndRentOrBuyOfTheOthers(): void
    {
        $details = self::getContainer()->get(TmdbProviders::class)->fetch('tt4378376', TitleType::Series, ['de', 'at']);
        $offers = $details->offersByCountry;

        self::assertSame([['wow', 'subscription'], ['skygo', 'subscription'], ['ard', 'free']], array_map(static fn (array $o) => [$o['service'], $o['type']], $offers['de']));
        self::assertSame(['skygo', 'wow'], array_column($offers['at'], 'service'));
        self::assertSame('https://www.themoviedb.org/tv/66980-babylon-berlin/watch?locale=DE', $offers['de'][0]['link']);
    }

    public function testEpisodeRuntimeIsTheMedianOfTheFirstSeason(): void
    {
        $tmdb = self::getContainer()->get(TmdbProviders::class);

        self::assertSame(46, $tmdb->fetch('tt4378376', TitleType::Series, ['de'])->runtime);
        self::assertSame(68, $tmdb->fetch('tt27995115', TitleType::Series, ['de'])->runtime);
    }

    public function testOffersAreMergedWithTheStreamingApi(): void
    {
        $container = self::getContainer();
        $tmdb = $container->get(TmdbProviders::class)->fetch('tt1375666', TitleType::Movie, ['de']);
        $availability = new Availability(new Title('tt1375666', TitleType::Movie, 'Inception'), 'de')
            ->replaceOffers('tmdb', $tmdb->offersByCountry['de'])
            ->replaceOffers('motn', $container->get(StreamingAvailability::class)->fetch('tt1375666', 'de')->offersByCountry['de']);

        self::assertSame(148, $tmdb->runtime);

        $overview = $availability->overview(['rakuten']);

        $included = array_column($overview['included'], 'link', 'service');
        self::assertSame(['hbo', 'prime', 'rtl'], array_keys($included));
        self::assertStringNotContainsString('themoviedb.org', $included['hbo']);
        self::assertStringContainsString('themoviedb.org', $included['prime']);
        self::assertSame(['rakuten', 'apple', 'magenta', 'maxdome', 'youtube'], array_column($overview['rent'], 'service'));
        self::assertNull($overview['rent'][0]['price']);
        self::assertSame(5, $overview['more']);

        $availability->replaceOffers('tmdb', []);
        self::assertSame(['apple', 'prime'], array_column($availability->overview([])['rent'], 'service'));
    }

    public function testUnknownTitleWithoutRecordingReturnsNull(): void
    {
        self::assertNull(self::getContainer()->get(TmdbProviders::class)->fetch('tt0000000', TitleType::Movie, ['de']));
    }
}
