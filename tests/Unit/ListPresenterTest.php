<?php

namespace App\Tests\Unit;

use App\Dto\ListFilter;
use App\Entity\Availability;
use App\Entity\ListEntry;
use App\Entity\Title;
use App\Entity\User;
use App\Entity\WatchList;
use App\Enum\Priority;
use App\Enum\SortField;
use App\Enum\TitleType;
use App\Service\ListPresenter;
use PHPUnit\Framework\TestCase;

final class ListPresenterTest extends TestCase
{
    private User $viewer;
    private WatchList $list;

    protected function setUp(): void
    {
        $this->viewer = new User()->setUsername('robin')->setServices(['netflix'])->setSortField(SortField::Name)->setSortDescending(false);
        $this->list = new WatchList($this->viewer);
    }

    public function testGroupsImportantFirstThenWaitingMaybeLastAndSortsWithinGroups(): void
    {
        $entries = [
            $this->entry('Zodiac', priority: Priority::Normal),
            $this->entry('Dune: Part Three', priority: Priority::Waiting),
            $this->entry('Alien', priority: Priority::Maybe),
            $this->entry('Brazil', priority: Priority::Important),
            $this->entry('Amélie', priority: Priority::Normal),
            $this->entry('Casablanca', watched: true),
        ];

        $view = new ListPresenter()->present($entries, new ListFilter(), $this->viewer, 'de');

        self::assertSame(['important', 'waiting', 'normal', 'maybe', 'watched'], array_keys($view['groups']));
        self::assertSame(['Amélie', 'Zodiac'], $this->names($view['groups']['normal']));
        self::assertSame(6, $view['shown']);
        self::assertSame(6, $view['total']);
    }

    public function testWatchedTitlesComeLastWithTheLastWatchedFirstWhateverTheSorting(): void
    {
        $entries = [
            $this->entry('Seen long ago', watched: true, rating: 9.9),
            $this->entry('On the list'),
            $this->entry('Seen yesterday', watched: true, rating: 1.0),
        ];
        $entries[0]->setWatchedAt(new \DateTimeImmutable('-1 year'));
        $entries[2]->setWatchedAt(new \DateTimeImmutable('-1 day'));

        $groups = new ListPresenter()->present($entries, new ListFilter(sort: SortField::Rating, dir: 'desc'), $this->viewer, 'de')['groups'];

        self::assertSame(['normal', 'watched'], array_keys($groups));
        self::assertSame(['Seen yesterday', 'Seen long ago'], $this->names($groups['watched']));
    }

    public function testSortOptionFromQueryWinsAndMissingValuesGoLast(): void
    {
        $entries = [
            $this->entry('A', rating: 6.1),
            $this->entry('B', rating: null),
            $this->entry('C', rating: 8.4),
        ];

        $view = new ListPresenter()->present($entries, new ListFilter(sort: SortField::Rating, dir: 'desc'), $this->viewer, 'de');

        self::assertSame(['C', 'A', 'B'], $this->names($view['groups']['normal']));
    }

    public function testFilters(): void
    {
        $entries = [
            $this->entry('Short movie', runtime: 95, offers: ['netflix' => 'subscription']),
            $this->entry('Long movie', runtime: 170, offers: ['prime' => 'subscription']),
            $this->entry('Series', type: TitleType::Series, offers: ['netflix' => 'rent']),
        ];
        $presenter = new ListPresenter();

        self::assertSame(['Series'], $this->names($presenter->present($entries, new ListFilter(type: TitleType::Series), $this->viewer, 'de')['groups']['normal']));
        $view = $presenter->present($entries, new ListFilter(short: true), $this->viewer, 'de');
        self::assertSame(['Short movie'], $this->names($view['groups']['normal']));
        self::assertSame(3, $view['totals']['normal']);
    }

    public function testSubscriptionsArePillsAndRentOrBuyGoIntoTheDropdown(): void
    {
        $this->viewer->setServices(['apple', 'netflix']);
        $entry = $this->entry('X', offers: ['prime' => 'subscription', 'apple' => 'buy', 'netflix' => 'subscription']);

        $offers = new ListPresenter()->present([$entry], new ListFilter(), $this->viewer, 'de')['groups']['normal'][0]['offers'];

        self::assertSame(['netflix', 'prime'], array_column($offers['included'], 'service'));
        self::assertSame([true, false], array_column($offers['included'], 'preferred'));
        self::assertSame(['apple'], array_column($offers['buy'], 'service'));
        self::assertTrue($offers['buy'][0]['preferred']);
        self::assertSame(1, $offers['more']);
    }

    public function testJustAddedTitlesStayOnTopWhateverTheSorting(): void
    {
        $entries = [
            $this->entry('Old favourite', priority: Priority::Important),
            $this->entry('Brand new', addedAgo: '-30 seconds'),
            $this->entry('Added earlier', addedAgo: '-5 minutes'),
        ];

        $groups = new ListPresenter()->present($entries, new ListFilter(), $this->viewer, 'de')['groups'];

        self::assertSame(['recent', 'important', 'normal'], array_keys($groups));
        self::assertSame(['Brand new'], $this->names($groups['recent']));
    }

    public function testGenresMatchAnyChosenOneAndCountWhatTheOtherFiltersLeave(): void
    {
        $entries = [
            $this->entry('Alien', genres: ['Horror', 'Science Fiction']),
            $this->entry('Arrival', genres: ['Drama', 'Sci-Fi']),
            $this->entry('Dark', type: TitleType::Series, genres: ['Drama', 'Mystery', 'Sci-Fi']),
            $this->entry('Kedi', genres: ['Documentary']),
        ];
        $presenter = new ListPresenter();

        $view = $presenter->present($entries, new ListFilter(genre: ['sci_fi', 'documentary']), $this->viewer, 'de');
        self::assertSame(['Alien', 'Arrival', 'Dark', 'Kedi'], $this->names($view['groups']['normal']));
        self::assertSame(['sci_fi' => 3, 'drama' => 2, 'documentary' => 1, 'horror' => 1, 'mystery' => 1], $view['genres']);

        $view = $presenter->present($entries, new ListFilter(type: TitleType::Movie, genre: ['drama']), $this->viewer, 'de');
        self::assertSame(['Arrival'], $this->names($view['groups']['normal']));
        self::assertSame(['sci_fi' => 2, 'drama' => 1, 'documentary' => 1, 'horror' => 1, 'mystery' => 0], $view['genres']);
    }

    /**
     * @param array<string, string> $offers
     * @param list<string>          $genres
     */
    private function entry(string $name, Priority $priority = Priority::Normal, bool $watched = false, ?float $rating = 7.0, ?int $runtime = 100, TitleType $type = TitleType::Movie, array $offers = [], string $addedAgo = '-1 day', array $genres = []): ListEntry
    {
        $title = new Title('tt'.crc32($name), $type, $name)->setGenres($genres)->setRating($rating)->setRuntime(TitleType::Movie === $type ? $runtime : null)->setDetailsFetchedAt(new \DateTimeImmutable());
        $availability = new Availability($title, 'de')->setOffers(array_map(
            static fn (string $service, string $kind) => ['service' => $service, 'name' => ucfirst($service), 'type' => $kind, 'link' => null, 'price' => null, 'color' => null],
            array_keys($offers),
            $offers,
        ));
        $title->addAvailability($availability);

        return new ListEntry($this->list, $title, $this->viewer)->setAddedAt(new \DateTimeImmutable($addedAgo))->setPriority($priority)->setWatchedAt($watched ? new \DateTimeImmutable() : null);
    }

    /** @param list<array{title: Title}> $rows */
    private function names(array $rows): array
    {
        return array_map(static fn (array $row) => $row['title']->getName(), $rows);
    }
}
