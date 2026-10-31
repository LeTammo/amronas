<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Where a title can be streamed in one country. Offers come from two sources: "motn" (Streaming Availability API) and "tmdb".
 *
 * @phpstan-type SummaryOffer array{service: string, name: string, link: ?string, type: string, preferred: bool, price: ?string, amount: float}
 * @phpstan-type Offer array{service: string, name: string, type: string, link: ?string, price: ?string, amount?: ?float, color: ?string, source?: string}
 */
#[ORM\Entity]
#[ORM\UniqueConstraint(columns: ['title_id', 'country'])]
class Availability
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** @var list<Offer> */
    #[ORM\Column(type: Types::JSON)]
    private array $offers = [];

    #[ORM\Column]
    private \DateTimeImmutable $fetchedAt;

    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'availabilities')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private Title $title,
        #[ORM\Column(length: 2)]
        private string $country,
    ) {
        $this->fetchedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): Title
    {
        return $this->title;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    /** @return list<Offer> */
    public function getOffers(): array
    {
        return $this->offers;
    }

    /** @param list<Offer> $offers */
    public function setOffers(array $offers): static
    {
        $this->offers = $offers;
        $this->fetchedAt = new \DateTimeImmutable();

        return $this;
    }

    /**
     * Replaces the offers of one source and keeps the others.
     *
     * @param list<Offer> $offers
     */
    public function replaceOffers(string $source, array $offers): static
    {
        $others = array_filter($this->offers, static fn (array $o) => ($o['source'] ?? 'motn') !== $source);

        return $this->setOffers([...array_values($others), ...array_map(static fn (array $o) => ['source' => $source] + $o, $offers)]);
    }

    public function getFetchedAt(): \DateTimeImmutable
    {
        return $this->fetchedAt;
    }

    /**
     * How a title is shown in the list: green pills for everything you can watch with a subscription or for free,
     * rent and buy offers grouped for the dropdown. One offer per service and section, preferred services first.
     * Services with a subscription aren't listed for rent or buy.
     *
     * @param list<string> $preferredServices
     *
     * @return array{included: list<SummaryOffer>, rent: list<SummaryOffer>, buy: list<SummaryOffer>, more: int} more: number of services in the dropdown
     */
    public function overview(array $preferredServices): array
    {
        // TMDB offers last: for the same service the streaming API's offer wins, it has a price and a direct link
        $offers = $this->offers;
        usort($offers, static fn (array $a, array $b) => ('tmdb' === ($a['source'] ?? null)) <=> ('tmdb' === ($b['source'] ?? null)));

        $byService = [];
        foreach ($offers as $offer) {
            // Add-on channels (e.g. "HBO Max via Prime Video") need an extra subscription and would show up under the wrong name
            if ('addon' === $offer['type']) {
                continue;
            }
            $section = \in_array($offer['type'], ['subscription', 'free'], true) ? 'included' : $offer['type'];
            $current = $byService[$offer['service']][$section] ?? null;
            // Keep the first offer, or the cheapest (e.g. SD and HD); a subscription wins over free with ads
            if ($current && !('included' === $section && 'subscription' === $offer['type'] && 'free' === $current['type'])
                && ('included' === $section || ($offer['amount'] ?? \INF) >= $current['amount'])) {
                continue;
            }
            $byService[$offer['service']][$section] = [
                'service' => $offer['service'],
                'name' => $offer['name'],
                'link' => $offer['link'] ?? null,
                'type' => $offer['type'],
                'preferred' => \in_array($offer['service'], $preferredServices, true),
                'price' => $offer['price'] ?? null,
                'amount' => $offer['amount'] ?? \INF,
            ];
        }

        $overview = ['included' => [], 'rent' => [], 'buy' => []];
        $more = [];
        foreach ($byService as $service => $sections) {
            foreach ($sections as $section => $offer) {
                if (!isset($overview[$section]) || ('included' !== $section && isset($sections['included']))) {
                    continue;
                }
                $overview[$section][] = $offer;
                if ('included' !== $section) {
                    $more[$service] = true;
                }
            }
        }
        foreach ($overview as &$sectionOffers) {
            usort($sectionOffers, static fn (array $a, array $b) => [$b['preferred'], $a['amount'], mb_strtolower($a['name'])] <=> [$a['preferred'], $b['amount'], mb_strtolower($b['name'])]);
        }

        return $overview + ['more' => \count($more)];
    }
}
