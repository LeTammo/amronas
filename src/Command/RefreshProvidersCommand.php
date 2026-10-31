<?php

namespace App\Command;

use App\Message\RefreshProviders;
use App\Repository\WatchListRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

/** Fetches the TMDB offers (ARD, ZDF, Joyn, …) for titles already on lists. Doesn't touch the quota of the streaming API. */
#[AsCommand('app:refresh-providers', 'Queues TMDB streaming offers for all titles on lists')]
final readonly class RefreshProvidersCommand
{
    public function __construct(
        private WatchListRepository $lists,
        private MessageBusInterface $bus,
    ) {
    }

    public function __invoke(SymfonyStyle $io, #[Option('Also refresh watched titles')] bool $all = false): int
    {
        $countries = [];
        foreach ($this->lists->findAll() as $list) {
            foreach ($list->getEntries() as $entry) {
                if ($all || !$entry->isWatched()) {
                    $countries[$entry->getTitle()->getId()][$list->getCountry()] = true;
                }
            }
        }

        $i = 0;
        foreach ($countries as $titleId => $titleCountries) {
            $this->bus->dispatch(new RefreshProviders($titleId, array_keys($titleCountries)), [new DelayStamp(++$i * 200)]);
        }

        $io->success(\sprintf('Queued %d titles for the worker.', $i));

        return Command::SUCCESS;
    }
}
