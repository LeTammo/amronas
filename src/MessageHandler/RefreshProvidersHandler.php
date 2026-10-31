<?php

namespace App\MessageHandler;

use App\Entity\Availability;
use App\Message\RefreshProviders;
use App\Repository\TitleRepository;
use App\Streaming\OmdbRuntime;
use App\Streaming\TmdbProviders;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class RefreshProvidersHandler
{
    public function __construct(
        private TitleRepository $titles,
        private TmdbProviders $tmdb,
        private OmdbRuntime $omdb,
        private EntityManagerInterface $em,
    ) {
    }

    public function __invoke(RefreshProviders $message): void
    {
        $title = $this->titles->find($message->titleId);
        if (!$title || !$details = $this->tmdb->fetch($title->getImdbId(), $title->getType(), $message->countries)) {
            return;
        }

        $runtime = ($title->isSeries() ? $this->omdb->fetch($title->getImdbId()) : null) ?? $details->runtime;
        if ($runtime && ($title->isSeries() || !$title->getRuntime())) {
            $title->setRuntime($runtime);
        }

        foreach ($details->offersByCountry as $country => $offers) {
            if (!$availability = $title->getAvailability($country)) {
                if (!$offers) {
                    continue;
                }
                $availability = new Availability($title, $country);
                $title->addAvailability($availability);
                $this->em->persist($availability);
            }
            $availability->replaceOffers('tmdb', $offers);
        }

        $this->em->flush();
    }
}
