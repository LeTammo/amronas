<?php

namespace App\MessageHandler;

use App\Entity\Availability;
use App\Entity\Title;
use App\Enum\DetailsSource;
use App\Message\RefreshProviders;
use App\Repository\TitleRepository;
use App\Streaming\Omdb;
use App\Streaming\TitleFacts;
use App\Streaming\TmdbProviders;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class RefreshProvidersHandler
{
    public function __construct(
        private TitleRepository $titles,
        private TmdbProviders $tmdb,
        private Omdb $omdb,
        private EntityManagerInterface $em,
    ) {
    }

    public function __invoke(RefreshProviders $message): void
    {
        if (!$title = $this->titles->find($message->titleId)) {
            return;
        }
        $imdb = $this->omdb->fetch($title->getImdbId());
        $tmdb = $this->tmdb->fetch($title->getImdbId(), $title->getType(), $message->countries);

        // IMDb's runtime wins (for series the episode length it shows); TMDB's only where IMDb has none
        $runtime = $imdb?->runtime ?? ($title->isSeries() || !$title->getRuntime() ? $tmdb?->runtime : null);
        if ($runtime) {
            $title->setRuntime($runtime);
        }

        if (DetailsSource::Streaming !== $title->getDetailsSource()) {
            $this->fillIn($title, ['omdb' => $imdb, 'tmdb' => $tmdb?->facts]);
        }

        foreach ($tmdb?->offersByCountry ?? [] as $country => $offers) {
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

    /**
     * The Streaming Availability API doesn't know the title (yet): take IMDb's data, and TMDB's where IMDb has none.
     *
     * @param array<string, ?TitleFacts> $sources by DetailsSource value, best first
     */
    private function fillIn(Title $title, array $sources): void
    {
        $sources = array_filter($sources, static fn (?TitleFacts $facts) => $facts && !$facts->isEmpty());
        if (!$sources) {
            return;
        }
        $pick = static fn (string $field): mixed => array_find(
            array_map(static fn (TitleFacts $facts) => $facts->$field, $sources),
            static fn (mixed $value) => null !== $value && [] !== $value,
        );

        $title
            ->setGenres($pick('genres') ?? $title->getGenres())
            ->setRating($pick('rating') ?? $title->getRating())
            ->setYear($title->getYear() ?? $pick('year'))
            ->setEndYear($pick('endYear') ?? $title->getEndYear())
            ->setSeasons($pick('seasons') ?? $title->getSeasons())
            ->setPlot($pick('plot') ?? $title->getPlot())
            ->setDetailsSource(DetailsSource::from(array_key_first($sources)))
            ->setDetailsFetchedAt(new \DateTimeImmutable());
    }
}
