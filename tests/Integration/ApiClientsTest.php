<?php

namespace App\Tests\Integration;

use App\Entity\Availability;
use App\Entity\Title;
use App\Enum\TitleType;
use App\Imdb\ImdbSearch;
use App\Streaming\StreamingAvailability;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Runs against the saved responses in tests/Fixtures/api (API_RECORDINGS=replay), never the network. */
final class ApiClientsTest extends KernelTestCase
{
    public function testSearchMapsAndFiltersImdbSuggestions(): void
    {
        $search = self::getContainer()->get(ImdbSearch::class);

        $all = $search->search('Dune');
        $series = $search->search('dune', TitleType::Series);

        self::assertSame('tt1160419', $all[0]->imdbId);
        self::assertSame(2021, $all[0]->year);
        self::assertStringContainsString('m.media-amazon.com', $all[0]->posterUrl);
        self::assertNotEmpty($series);
        self::assertSame([], array_filter($series, static fn ($r) => TitleType::Series !== $r->type));
        self::assertContains('tt10466872', array_map(static fn ($r) => $r->imdbId, $series));
    }

    public function testStreamingDetailsAreMapped(): void
    {
        $details = self::getContainer()->get(StreamingAvailability::class)->fetch('tt1375666', 'de');

        self::assertNotNull($details);
        self::assertSame(TitleType::Movie, $details->type);
        self::assertSame(148, $details->runtime);
        self::assertSame(8.6, $details->rating);
        self::assertSame(['Adventure', 'Science Fiction', 'Thriller'], $details->genres);

        $offers = $details->offersByCountry['de'];
        self::assertSame(['prime', 'prime', 'prime', 'prime', 'apple', 'apple', 'hbo', 'rtl'], array_column($offers, 'service'));
        self::assertSame('9,99 €', $offers[0]['price']);
        self::assertSame(3.99, $offers[3]['amount']);
    }

    public function testOverviewGroupsOffersAndKeepsTheCheapestPrice(): void
    {
        $details = self::getContainer()->get(StreamingAvailability::class)->fetch('tt1375666', 'de');
        $availability = new Availability(new Title('tt1375666', TitleType::Movie, 'Inception'), 'de')->replaceOffers('motn', $details->offersByCountry['de']);

        $overview = $availability->overview(['apple', 'hbo']);
        $services = static fn (array $offers) => array_map(static fn (array $o) => $o['service'].($o['price'] ? ' '.$o['price'] : ''), $offers);

        self::assertSame(['hbo', 'rtl'], $services($overview['included']));
        self::assertTrue($overview['included'][0]['preferred']);
        self::assertSame(['apple 3,99 €', 'prime 3,99 €'], $services($overview['rent']));
        self::assertSame(['apple 9,99 €', 'prime 9,99 €'], $services($overview['buy']));
        self::assertSame(2, $overview['more']);
        self::assertTrue($overview['rent'][0]['preferred']);
    }

    public function testUnknownTitleWithoutRecordingReturnsNull(): void
    {
        self::assertNull(self::getContainer()->get(StreamingAvailability::class)->fetch('tt0000000'));
    }
}
