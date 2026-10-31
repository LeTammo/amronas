<?php

namespace App\Legacy;

use App\Entity\ListEntry;
use App\Entity\Title;
use App\Entity\User;
use App\Entity\WatchList;
use App\Enum\ListType;
use App\Enum\TitleType;
use App\Imdb\ImdbSearch;
use App\Message\RefreshTitle;
use App\Repository\ListEntryRepository;
use App\Repository\TitleRepository;
use App\Repository\UserRepository;
use App\Repository\WatchListRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

/**
 * One-time import of the old amronas MySQL dump. Delete src/Legacy/ once the migration is done.
 *
 * - Users: only the ones given with --user (old passwords keep working).
 * - Lists: those with at least one imported maintainer. Maintainers become editors, subscribers view-only members.
 *   The owner is --owner if they maintained the list, otherwise the first imported maintainer.
 * - Entries: titles are looked up on IMDb again (old posters are gone), one per --delay seconds.
 *   Details and streaming offers are then fetched by the worker, one request per title (see --details).
 */
#[AsCommand('app:import-legacy', 'Imports users, lists and entries from the old amronas MySQL dump')]
final readonly class ImportLegacyCommand
{
    private const USERS = ['robin', 'annmarii', 'McMarekek', 'erik'];

    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $users,
        private WatchListRepository $lists,
        private TitleRepository $titles,
        private ListEntryRepository $entries,
        private ImdbSearch $imdb,
        private MessageBusInterface $bus,
        #[Autowire(env: 'API_RECORDINGS')]
        private string $recordings,
    ) {
    }

    /**
     * @param list<string> $user
     */
    public function __invoke(
        SymfonyStyle $io,
        #[Argument('Path to the SQL dump')] string $file,
        #[Option('Usernames to import')] array $user = self::USERS,
        #[Option('Preferred owner of lists with several maintainers')] string $owner = 'robin',
        #[Option('Fetch details and streaming offers for "unwatched" titles, "all" or "none" (one API request per title)')] string $details = 'unwatched',
        #[Option('Seconds to wait between IMDb lookups')] float $delay = 1.0,
        #[Option('Only import this many entries (for a test run)')] ?int $limit = null,
    ): int {
        if ('off' !== $this->recordings) {
            $io->error('Run the import with API_RECORDINGS=off (e.g. APP_ENV=prod), otherwise every IMDb lookup is saved to tests/Fixtures.');

            return Command::FAILURE;
        }
        if (!\in_array($details, ['unwatched', 'all', 'none'], true)) {
            $io->error('--details must be "unwatched", "all" or "none".');

            return Command::FAILURE;
        }

        $dump = new MysqlDumpReader($file);
        $io->title('Importing '.basename($file));

        $userMap = [];
        foreach ($dump->rows('user') as $row) {
            if (!\in_array($row['username'], $user, true)) {
                continue;
            }
            $existing = $this->users->findOneBy(['username' => $row['username']]);
            $userMap[$row['id']] = $existing ?? $this->createUser($row);
            $io->writeln(\sprintf(' %s user <info>%s</info>', $existing ? 'Existing' : 'New', $row['username']));
        }
        $this->em->flush();

        $maintainers = $this->groupBy($dump->rows('movie_list_maintainer'));
        $subscribers = $this->groupBy($dump->rows('movie_list_subscriber'));
        $listMap = [];
        foreach ($dump->rows('movie_list') as $row) {
            $listMaintainers = array_values(array_filter(array_map(static fn ($id) => $userMap[$id] ?? null, $maintainers[$row['id']] ?? [])));
            if (!$listMaintainers) {
                continue;
            }
            usort($listMaintainers, static fn (User $a, User $b) => [$b->getUsername() === $owner] <=> [$a->getUsername() === $owner]);
            $listOwner = $listMaintainers[0];

            if ($existing = $this->lists->findOneBy(['name' => $row['name'], 'owner' => $listOwner])) {
                $listMap[$row['id']] = $existing;
                $io->writeln(\sprintf(' Existing list <info>%s</info>', $row['name']));
                continue;
            }

            $list = new WatchList($listOwner)->setName($row['name'])->setType(ListType::Mixed);
            foreach (\array_slice($listMaintainers, 1) as $editor) {
                $list->addMember($editor, true);
            }
            foreach ($subscribers[$row['id']] ?? [] as $subscriberId) {
                if (isset($userMap[$subscriberId]) && $userMap[$subscriberId] !== $listOwner) {
                    $list->addMember($userMap[$subscriberId], false);
                }
            }
            $this->em->persist($list);
            $listMap[$row['id']] = $list;
            $io->writeln(\sprintf(' New list <info>%s</info> (owner %s, %d members)', $row['name'], $listOwner->getUsername(), $list->getMembers()->count()));
        }
        $this->em->flush();

        $movies = array_column($dump->rows('movie'), null, 'id');
        $byKey = [];
        foreach ($dump->rows('movie_list_entry') as $row) {
            if (!isset($listMap[$row['movie_list_id']], $movies[$row['movie_id']])) {
                continue;
            }
            $key = $row['movie_list_id'].'-'.$row['movie_id'];
            $current = $byKey[$key] ?? null;
            $byKey[$key] = [
                'list' => $listMap[$row['movie_list_id']],
                'movie' => $movies[$row['movie_id']],
                'addedBy' => $current['addedBy'] ?? $userMap[$row['added_by_id']] ?? null,
                'addedAt' => min($current['addedAt'] ?? $row['time_added'], $row['time_added']),
                'watchedAt' => max($current['watchedAt'] ?? null, $row['time_watched']),
            ];
        }
        $rows = \array_slice(array_values($byKey), 0, $limit);

        $io->section(\sprintf('%d entries, %d different titles', \count($rows), \count(array_unique(array_map(static fn (array $r) => $r['movie']['imdb_id'], $rows)))));
        $titleCache = [];
        $refresh = [];
        $io->progressStart(\count($rows));
        foreach ($rows as $row) {
            $imdbId = $row['movie']['imdb_id'];
            $title = $titleCache[$imdbId] ??= $this->titles->findOneByImdbId($imdbId) ?? $this->createTitle($row['movie'], $delay);
            $list = $row['list'];

            if (!$this->entries->findOneBy(['list' => $list, 'title' => $title])) {
                $entry = new ListEntry($list, $title, $row['addedBy'])
                    ->setAddedAt(new \DateTimeImmutable($row['addedAt']))
                    ->setWatchedAt($row['watchedAt'] ? new \DateTimeImmutable($row['watchedAt']) : null);
                $list->getEntries()->add($entry);
                $this->em->persist($entry);
            }
            $validId = (bool) preg_match('/^tt\d+$/', $imdbId);
            if ($validId && 'none' !== $details && ('all' === $details || !$row['watchedAt']) && !$title->getAvailability($list->getCountry())) {
                $refresh[$imdbId]['title'] = $title;
                $refresh[$imdbId]['countries'][$list->getCountry()] = true;
            }
            $this->em->flush();
            $io->progressAdvance();
        }
        $io->progressFinish();

        $i = 0;
        foreach ($refresh as ['title' => $title, 'countries' => $countries]) {
            $this->bus->dispatch(new RefreshTitle($title->getId(), array_keys($countries)), [new DelayStamp(++$i * 1000)]);
        }

        $io->success(\sprintf('Imported %d users, %d lists and %d entries. Queued %d title refreshes (%d streaming API requests) for the worker.', \count($userMap), \count($listMap), \count($rows), $i, $i));

        return Command::SUCCESS;
    }

    /** @param array<string, string|null> $row */
    private function createUser(array $row): User
    {
        $user = new User()
            ->setUsername($row['username'])
            ->setEmail($row['email'] ?: null)
            ->setAdmin(str_contains((string) $row['roles'], 'ROLE_ADMIN'))
            ->setPassword($row['password']);
        $this->em->persist($user);

        return $user;
    }

    /** @param array<string, string|null> $movie */
    private function createTitle(array $movie, float $delay): Title
    {
        $result = null;
        if (preg_match('/^tt\d+$/', $movie['imdb_id'])) {
            $result = array_find($this->imdb->search($movie['imdb_id']), static fn ($r) => $r->imdbId === $movie['imdb_id']);
            usleep((int) ($delay * 1_000_000));
        }

        $title = new Title($movie['imdb_id'], $result->type ?? TitleType::Movie, $result->name ?? $movie['name'])
            ->setYear($result->year ?? ($movie['year'] ? (int) $movie['year'] : null))
            ->setPosterUrl($result?->posterUrl)
            ->setCast($result?->cast)
            ->setGenres(json_decode($movie['genre'] ?? '[]', true) ?: []);
        $this->em->persist($title);

        return $title;
    }

    /**
     * @param list<array<string, string|null>> $rows
     *
     * @return array<string, list<string>> list id => user ids
     */
    private function groupBy(array $rows): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['movie_list_id']][] = $row['user_id'];
        }

        return $grouped;
    }
}
