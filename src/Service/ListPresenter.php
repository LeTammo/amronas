<?php

namespace App\Service;

use App\Dto\ListFilter;
use App\Entity\ListEntry;
use App\Entity\User;
use App\Enum\SortField;
use App\Enum\TitleType;

/** Filters, sorts and groups the entries of a list for one viewer. */
final class ListPresenter
{
    /** Movies up to this runtime count as "short" (minutes). */
    public const SHORT_RUNTIME = 120;

    /** Details that are still missing after this long won't come anymore (e.g. no API key). */
    private const PENDING_FOR = '-10 minutes';

    /** New titles stay on top in their own group for this long, whatever the sorting. */
    private const RECENT_FOR = '-2 minutes';

    /**
     * @param list<ListEntry> $entries
     * @param string          $country streaming offers are shown for this country (the list's)
     *
     * @return array{groups: array<string, list<array<string, mixed>>>, totals: array<string, int>, genres: array<string, int>, total: int, shown: int, pending: bool}
     *                totals: entries per group without filters; genres: all genres on the list, most common first, with how many entries the other filters leave of each
     */
    public function present(array $entries, ListFilter $filter, User $viewer, string $country): array
    {
        $services = $viewer->getServices();
        $pendingSince = new \DateTimeImmutable(self::PENDING_FOR);
        $recentSince = new \DateTimeImmutable(self::RECENT_FOR);
        $sort = $filter->sort ?? $viewer->getSortField();
        $descending = null !== $filter->dir ? 'desc' === $filter->dir : $viewer->isSortDescending();

        $rows = [];
        $pending = false;
        $genres = [];
        $genreTotals = [];
        $totals = ['recent' => 0, 'important' => 0, 'waiting' => 0, 'normal' => 0, 'maybe' => 0, 'watched' => 0];
        foreach ($entries as $entry) {
            $title = $entry->getTitle();
            $group = match (true) {
                $entry->isWatched() => 'watched',
                $entry->getAddedAt() > $recentSince => 'recent',
                default => $entry->getPriority()->value,
            };
            ++$totals[$group];

            $genreKeys = $title->getGenreKeys();
            foreach ($genreKeys as $genre) {
                $genreTotals[$genre] = ($genreTotals[$genre] ?? 0) + 1;
                $genres[$genre] ??= 0;
            }

            if ($filter->type && $title->getType() !== $filter->type) {
                continue;
            }
            if ($filter->short && (TitleType::Series === $title->getType() || null === $title->getRuntime() || $title->getRuntime() > self::SHORT_RUNTIME)) {
                continue;
            }
            foreach ($genreKeys as $genre) {
                ++$genres[$genre];
            }
            if ($filter->genre && !array_intersect($filter->genre, $genreKeys)) {
                continue;
            }
            $availability = $title->getAvailability($country);
            $isPending = !$title->getDetailsFetchedAt() && $entry->getAddedAt() > $pendingSince;
            $pending = $pending || $isPending;
            $rows[] = [
                'entry' => $entry,
                'title' => $title,
                'group' => $group,
                'offers' => $availability?->overview($services),
                'checked' => null !== $availability,
                'pending' => $isPending,
            ];
        }

        usort($rows, fn (array $a, array $b) => $this->compare($a['entry'], $b['entry'], $sort, $descending));

        $groups = array_fill_keys(array_keys($totals), []);
        foreach ($rows as $row) {
            $groups[$row['group']][] = $row;
        }
        usort($groups['recent'], static fn (array $a, array $b) => $b['entry']->getAddedAt() <=> $a['entry']->getAddedAt());
        usort($groups['watched'], static fn (array $a, array $b) => $b['entry']->getWatchedAt() <=> $a['entry']->getWatchedAt());

        return [
            'groups' => array_filter($groups),
            'totals' => $totals,
            'genres' => $this->sortGenres($genres, $genreTotals),
            'total' => \count($entries),
            'shown' => \count($rows),
            'pending' => $pending,
        ];
    }

    /**
     * Most common genres first (on the whole list, so the order stays put while filtering).
     *
     * @param array<string, int> $genres
     * @param array<string, int> $totals
     *
     * @return array<string, int>
     */
    private function sortGenres(array $genres, array $totals): array
    {
        uksort($genres, static fn (string $a, string $b) => [$totals[$b], $a] <=> [$totals[$a], $b]);

        return $genres;
    }

    private function compare(ListEntry $a, ListEntry $b, SortField $sort, bool $descending): int
    {
        $value = static fn (ListEntry $e): mixed => match ($sort) {
            SortField::Added => $e->getAddedAt(),
            SortField::Name => mb_strtolower($e->getTitle()->getName()),
            SortField::Year => $e->getTitle()->getYear(),
            SortField::Rating => $e->getTitle()->getRating(),
            SortField::Length => $e->getTitle()->getRuntime() ?? ($e->getTitle()->getSeasons() ? $e->getTitle()->getSeasons() * 1000 : null),
        };
        [$x, $y] = [$value($a), $value($b)];

        if (null === $x || null === $y) {
            return (null === $x) <=> (null === $y);
        }

        return $descending ? $y <=> $x : $x <=> $y;
    }
}
