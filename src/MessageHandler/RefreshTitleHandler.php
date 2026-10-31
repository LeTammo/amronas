<?php

namespace App\MessageHandler;

use App\Entity\Availability;
use App\Message\RefreshTitle;
use App\Repository\TitleRepository;
use App\Streaming\StreamingAvailability;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class RefreshTitleHandler
{
    public function __construct(
        private TitleRepository $titles,
        private StreamingAvailability $streaming,
        private EntityManagerInterface $em,
    ) {
    }

    public function __invoke(RefreshTitle $message): void
    {
        $title = $this->titles->find($message->titleId);
        $country = 1 === \count($message->countries) ? $message->countries[0] : null;
        if (!$title || !$details = $this->streaming->fetch($title->getImdbId(), $country)) {
            return;
        }

        $title
            ->setType($details->type)
            ->setOriginalName($details->originalTitle !== $title->getName() ? $details->originalTitle : null)
            ->setPlot($details->overview)
            ->setYear($details->year ?? $title->getYear())
            ->setEndYear($details->endYear)
            ->setRuntime($details->runtime ?? $title->getRuntime())
            ->setSeasons($details->seasons)
            ->setGenres($details->genres)
            ->setRating($details->rating)
            ->setPosterUrl($title->getPosterUrl() ?? $details->posterUrl)
            ->setDetailsFetchedAt(new \DateTimeImmutable());

        foreach ($message->countries as $country) {
            if (!$availability = $title->getAvailability($country)) {
                $availability = new Availability($title, $country);
                $title->addAvailability($availability);
                $this->em->persist($availability);
            }
            $availability->replaceOffers('motn', $details->offersByCountry[$country] ?? []);
        }

        $this->em->flush();
    }
}
