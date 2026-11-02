<?php

namespace App\Service;

use App\Entity\ListEntry;
use App\Entity\Title;
use App\Entity\User;
use App\Entity\WatchList;
use App\Imdb\SearchResult;
use App\Message\RefreshProviders;
use App\Message\RefreshTitle;
use App\Repository\ListEntryRepository;
use App\Repository\TitleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class WatchListManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private TitleRepository $titles,
        private ListEntryRepository $entries,
        private MessageBusInterface $bus,
    ) {
    }

    /** Adds a search result to the list. Returns null if the title is already on it. */
    public function add(WatchList $list, SearchResult $result, User $user): ?ListEntry
    {
        $title = $this->titles->findOneByImdbId($result->imdbId);
        if ($title && $this->entries->findOneBy(['list' => $list, 'title' => $title])) {
            return null;
        }

        if (!$title) {
            $title = new Title($result->imdbId, $result->type, $result->name)
                ->setYear($result->year)
                ->setPosterUrl($result->posterUrl)
                ->setCast($result->cast);
            $this->em->persist($title);
        }

        $entry = new ListEntry($list, $title, $user);
        $list->getEntries()->add($entry);
        $this->em->persist($entry);
        $this->em->flush();

        if (!$title->getDetailsFetchedAt()) {
            $this->refresh($title, $list);
        }

        return $entry;
    }

    /** Fetches details and streaming offers for the list's country in the background. */
    public function refresh(Title $title, WatchList $list): void
    {
        // The streaming API first: TMDB and OMDb only fill in genres and rating if it doesn't know the title
        $this->bus->dispatch(new RefreshTitle($title->getId(), [$list->getCountry()]));
        $this->bus->dispatch(new RefreshProviders($title->getId(), [$list->getCountry()]));
    }

    /** After the list's country changed: fetch offers for titles that don't have any for it yet. Returns how many were queued. */
    public function refreshMissingOffers(WatchList $list): int
    {
        $count = 0;
        foreach ($list->getEntries() as $entry) {
            if (!$entry->isWatched() && !$entry->getTitle()->getAvailability($list->getCountry())) {
                $this->refresh($entry->getTitle(), $list);
                ++$count;
            }
        }

        return $count;
    }
}
