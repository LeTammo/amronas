<?php

namespace App\DataFixtures;

use App\Entity\Availability;
use App\Entity\ListEntry;
use App\Entity\Title;
use App\Entity\User;
use App\Entity\WatchList;
use App\Enum\ListType;
use App\Enum\Priority;
use App\Enum\TitleType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Example data for development: `php bin/console doctrine:fixtures:load`.
 *
 * Users (password for all: "watchlist-dev"): robin (admin), klara, erik.
 * Streaming offers are made-up examples, not real availability.
 */
class AppFixtures extends Fixture
{
    public const PASSWORD = 'watchlist-dev';

    private const IMG = 'https://m.media-amazon.com/images/M/%s._V1_.jpg';

    /** imdbId => [type, name, year, endYear, runtime, seasons, genres, rating, poster id, cast, offers] */
    private const TITLES = [
        'tt2243973' => [TitleType::Series, 'Hannibal', 2013, 2015, null, 3, ['Crime', 'Drama', 'Horror'], 8.5, 'MV5BNjBmYjdiMzItNTEwNi00YTI2LTg4YjctOTRjNDYxNTUxNGQ1XkEyXkFqcGc@', 'Hugh Dancy, Mads Mikkelsen', ['prime' => 'subscription', 'wow' => 'subscription']],
        'tt12593682' => [TitleType::Movie, 'Bullet Train', 2022, null, 127, null, ['Action', 'Comedy', 'Thriller'], 7.3, 'MV5BODUyZjkxZDMtZGI3ZC00ZmEwLTgwMTUtYTU4OTQ5YjU4ZjRlXkEyXkFqcGc@', 'Brad Pitt, Joey King', ['netflix' => 'subscription', 'prime' => 'rent']],
        'tt21800162' => [TitleType::Movie, 'There\'s Still Tomorrow', 2023, null, 118, null, ['Comedy', 'Drama'], 7.8, 'MV5BMmZhOWMzNzQtNjY0OC00MTYzLTllODUtZjZjY2MzNDc5ODdmXkEyXkFqcGc@', 'Paola Cortellesi, Valerio Mastandrea', ['prime' => 'rent', 'apple' => 'buy']],
        'tt13651794' => [TitleType::Movie, 'May December', 2023, null, 117, null, ['Drama'], 6.6, 'MV5BMjA0ODViOWUtYzFjYy00ZDZlLWFlMmMtMjU2NzFmNWNlZmFhXkEyXkFqcGc@', 'Natalie Portman, Julianne Moore', ['netflix' => 'subscription']],
        'tt4420704' => [TitleType::Movie, 'Kedi', 2016, null, 79, null, ['Documentary'], 7.6, 'MV5BMjEwNTM2NDE0Nl5BMl5BanBnXkFtZTgwMzMzNTc4MDI@', 'Bülent Üstün', ['prime' => 'subscription', 'mubi' => 'subscription']],
        'tt26699529' => [TitleType::Movie, 'Marguerite\'s Theorem', 2023, null, 112, null, ['Drama'], 6.8, 'MV5BNmZiMTZjNjQtYjU1Ni00MjA4LTg1ZGEtYThmN2I4NDA5MzVmXkEyXkFqcGc@', 'Ella Rumpf, Jean-Pierre Darroussin', []],
        'tt11225626' => [TitleType::Movie, 'Am I OK?', 2022, null, 86, null, ['Comedy', 'Drama', 'Romance'], 6.2, 'MV5BYTU5MDg1MDctM2QxMi00ZDVlLThlMWMtMmNlNmExZGJmNThlXkEyXkFqcGc@', 'Dakota Johnson, Sonoya Mizuno', ['wow' => 'subscription']],
        'tt3234122' => [TitleType::Movie, 'Widow Clicquot', 2023, null, 90, null, ['Biography', 'Drama'], 5.9, 'MV5BOWYyZDg2N2YtYmU3YS00MTI0LTliM2QtZjcxNTJhMGRiNTBiXkEyXkFqcGc@', 'Haley Bennett, Tom Sturridge', ['disney' => 'subscription']],
        'tt0114594' => [TitleType::Movie, 'Swimming with Sharks', 1994, null, 93, null, ['Comedy', 'Crime'], 6.6, 'MV5BOTVjYWM1MWItMzhhMi00OWVhLTlhNTUtZWNiMzcxMzQyOGY2XkEyXkFqcGc@', 'Kevin Spacey, Frank Whaley', ['prime' => 'rent']],
        'tt15239678' => [TitleType::Movie, 'Dune: Part Two', 2024, null, 166, null, ['Action', 'Adventure', 'Drama'], 8.5, 'MV5BNTc0YmQxMjEtODI5MC00NjFiLTlkMWUtOGQ5NjFmYWUyZGJhXkEyXkFqcGc@', 'Timothée Chalamet, Zendaya', ['netflix' => 'subscription', 'wow' => 'subscription', 'prime' => 'rent']],
        'tt10466872' => [TitleType::Series, 'Dune: Prophecy', 2024, null, null, 1, ['Drama', 'Science Fiction'], 7.1, 'MV5BOTc2YTFiOTItZmRiNi00OWE5LThhOTEtMmZhMTkzYmRiNjIxXkEyXkFqcGc@', 'Emily Watson, Olivia Williams', ['wow' => 'subscription']],
        'tt19231492' => [TitleType::Series, 'Dark Matter', 2024, null, null, 1, ['Drama', 'Science Fiction', 'Thriller'], 7.4, 'MV5BN2U4YWMyZTMtMmEzMy00Yzk0LThiNTctNjE0YTc5ZDZhZTk5XkEyXkFqcGc@', 'Joel Edgerton, Jennifer Connelly', ['apple' => 'subscription']],
    ];

    private const LINKS = [
        'netflix' => ['Netflix', 'https://www.netflix.com/'],
        'prime' => ['Prime Video', 'https://www.primevideo.com/'],
        'disney' => ['Disney+', 'https://www.disneyplus.com/'],
        'apple' => ['Apple TV', 'https://tv.apple.com/'],
        'wow' => ['WOW', 'https://www.wowtv.de/'],
        'mubi' => ['MUBI', 'https://mubi.com/'],
    ];

    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $robin = $this->user($manager, 'robin', true, ['netflix', 'prime']);
        $klara = $this->user($manager, 'klara', false, ['netflix', 'prime', 'disney']);
        $erik = $this->user($manager, 'erik', false, ['crunchyroll'], 'en', setupCompleted: false);

        $titles = [];
        foreach (self::TITLES as $imdbId => [$type, $name, $year, $endYear, $runtime, $seasons, $genres, $rating, $poster, $cast, $offers]) {
            $title = new Title($imdbId, $type, $name)
                ->setYear($year)->setEndYear($endYear)->setRuntime($runtime)->setSeasons($seasons)
                ->setGenres($genres)->setRating($rating)->setCast($cast)
                ->setPosterUrl(\sprintf(self::IMG, $poster))
                ->setDetailsFetchedAt(new \DateTimeImmutable('-2 days'));
            $availability = new Availability($title, 'de')->setOffers(array_map(
                static fn (string $service, string $kind) => [
                    'service' => $service,
                    'name' => self::LINKS[$service][0],
                    'type' => $kind,
                    'link' => self::LINKS[$service][1],
                    'price' => 'subscription' === $kind ? null : ('rent' === $kind ? '3,99 €' : '9,99 €'),
                    'color' => null,
                ],
                array_keys($offers),
                $offers,
            ));
            $title->addAvailability($availability);
            $manager->persist($title);
            $manager->persist($availability);
            $titles[$imdbId] = $title;
        }

        // List: mixed, Klara may edit
        $ours = new WatchList($robin)->setName('Unsere Filmliste')->setIcon('movie')->setType(ListType::Mixed);
        $ours->addMember($klara, true);
        $manager->persist($ours);
        $entries = [
            'tt2243973' => [Priority::Important, '-30 days', $klara],
            'tt21800162' => [Priority::Important, '-3 days', $robin],
            'tt12593682' => [Priority::Normal, '-20 days', $robin],
            'tt13651794' => [Priority::Normal, '-12 days', $klara],
            'tt11225626' => [Priority::Normal, '-8 days', $klara],
            'tt26699529' => [Priority::Waiting, '-2 days', $robin],
            'tt15239678' => [Priority::Normal, '-1 day', $klara],
            'tt3234122' => [Priority::Maybe, '-15 days', $klara],
            'tt0114594' => [Priority::Maybe, '-40 days', $robin],
            'tt4420704' => [Priority::Normal, '-60 days', $klara, '-10 days'],
        ];
        foreach ($entries as $imdbId => [$priority, $added, $by]) {
            $entry = new ListEntry($ours, $titles[$imdbId], $by)->setPriority($priority)->setAddedAt(new \DateTimeImmutable($added));
            if (isset($entries[$imdbId][3])) {
                $entry->setWatchedAt(new \DateTimeImmutable($entries[$imdbId][3]));
            }
            $manager->persist($entry);
        }

        // Klara's series list: Robin may only view it
        $series = new WatchList($klara)->setName('Klaras Serien')->setIcon('device-tv')->setType(ListType::Series);
        $series->addMember($robin, false);
        $manager->persist($series);
        foreach (['tt19231492', 'tt10466872', 'tt2243973'] as $i => $imdbId) {
            $manager->persist(new ListEntry($series, $titles[$imdbId], $klara)->setAddedAt(new \DateTimeImmutable(\sprintf('-%d days', $i + 1))));
        }

        // Erik hasn't done the first-login setup yet and has an own list
        $manager->persist(new WatchList($erik)->setName('Anime')->setIcon('brand-funimation')->setType(ListType::Series));

        $manager->flush();
    }

    /** @param list<string> $services */
    private function user(ObjectManager $manager, string $name, bool $admin, array $services, string $locale = 'de', bool $setupCompleted = true): User
    {
        $user = new User()
            ->setUsername($name)
            ->setEmail($name.'@example.test')
            ->setAdmin($admin)
            ->setLocale($locale)
            ->setServices($services)
            ->setSetupCompleted($setupCompleted);
        $user->setPassword($this->hasher->hashPassword($user, self::PASSWORD));
        $manager->persist($user);

        return $user;
    }
}
