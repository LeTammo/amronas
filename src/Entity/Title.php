<?php

namespace App\Entity;

use App\Enum\DetailsSource;
use App\Enum\TitleType;
use App\Repository\TitleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/** A movie or series, shared by all lists. Basic data comes from the search, details are fetched asynchronously. */
#[ORM\Entity(repositoryClass: TitleRepository::class)]
class Title
{
    private const GENRE_ALIASES = [
        'science_fiction' => 'sci_fi',
        'reality_tv' => 'reality',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $originalName = null;

    #[ORM\Column(nullable: true)]
    private ?int $year = null;

    #[ORM\Column(nullable: true)]
    private ?int $endYear = null;

    /** Runtime in minutes; for series the average episode (from TMDB). */
    #[ORM\Column(nullable: true)]
    private ?int $runtime = null;

    #[ORM\Column(nullable: true)]
    private ?int $seasons = null;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $genres = [];

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $plot = null;

    /** Rating from 0 to 10. */
    #[ORM\Column(nullable: true)]
    private ?float $rating = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $posterUrl = null;

    /** Main cast as shown in the search results. */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cast = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $detailsFetchedAt = null;

    #[ORM\Column(length: 10, nullable: true, enumType: DetailsSource::class)]
    private ?DetailsSource $detailsSource = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<string, Availability> */
    #[ORM\OneToMany(targetEntity: Availability::class, mappedBy: 'title', orphanRemoval: true, indexBy: 'country')]
    private Collection $availabilities;

    public function __construct(
        #[ORM\Column(length: 12, unique: true)]
        private string $imdbId,
        #[ORM\Column(enumType: TitleType::class)]
        private TitleType $type,
        #[ORM\Column(length: 255)]
        private string $name,
    ) {
        $this->createdAt = new \DateTimeImmutable();
        $this->availabilities = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImdbId(): string
    {
        return $this->imdbId;
    }

    public function getType(): TitleType
    {
        return $this->type;
    }

    public function setType(TitleType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function isSeries(): bool
    {
        return TitleType::Series === $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(?string $originalName): static
    {
        $this->originalName = $originalName;

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(?int $year): static
    {
        $this->year = $year;

        return $this;
    }

    public function getEndYear(): ?int
    {
        return $this->endYear;
    }

    public function setEndYear(?int $endYear): static
    {
        $this->endYear = $endYear;

        return $this;
    }

    public function getRuntime(): ?int
    {
        return $this->runtime;
    }

    public function setRuntime(?int $runtime): static
    {
        $this->runtime = $runtime;

        return $this;
    }

    public function getSeasons(): ?int
    {
        return $this->seasons;
    }

    public function setSeasons(?int $seasons): static
    {
        $this->seasons = $seasons;

        return $this;
    }

    /** @return list<string> */
    public function getGenres(): array
    {
        return $this->genres;
    }

    /**
     * The genres as keys for filtering and translation; IMDb and the streaming API name some differently ("Sci-Fi", "Science Fiction" → sci_fi).
     *
     * @return list<string>
     */
    public function getGenreKeys(): array
    {
        $keys = array_map(static function (string $genre): string {
            $key = trim(preg_replace('/[^a-z]+/', '_', strtolower($genre)), '_');

            return self::GENRE_ALIASES[$key] ?? $key;
        }, $this->genres);

        return array_values(array_unique(array_filter($keys)));
    }

    /** @param list<string> $genres */
    public function setGenres(array $genres): static
    {
        $this->genres = array_values($genres);

        return $this;
    }

    public function getPlot(): ?string
    {
        return $this->plot;
    }

    public function setPlot(?string $plot): static
    {
        $this->plot = $plot;

        return $this;
    }

    public function getRating(): ?float
    {
        return $this->rating;
    }

    public function setRating(?float $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getPosterUrl(): ?string
    {
        return $this->posterUrl;
    }

    public function setPosterUrl(?string $posterUrl): static
    {
        $this->posterUrl = $posterUrl;

        return $this;
    }

    public function getCast(): ?string
    {
        return $this->cast;
    }

    public function setCast(?string $cast): static
    {
        $this->cast = $cast;

        return $this;
    }

    public function getDetailsFetchedAt(): ?\DateTimeImmutable
    {
        return $this->detailsFetchedAt;
    }

    public function getDetailsSource(): ?DetailsSource
    {
        return $this->detailsSource;
    }

    public function setDetailsSource(?DetailsSource $detailsSource): static
    {
        $this->detailsSource = $detailsSource;

        return $this;
    }

    /** True if genres, rating and seasons only come from a fallback (IMDb via OMDb, or TMDB). */
    public function hasFallbackDetails(): bool
    {
        return null !== $this->detailsSource && DetailsSource::Streaming !== $this->detailsSource;
    }

    public function setDetailsFetchedAt(?\DateTimeImmutable $detailsFetchedAt): static
    {
        $this->detailsFetchedAt = $detailsFetchedAt;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getAvailability(string $country): ?Availability
    {
        return $this->availabilities->get($country);
    }

    public function addAvailability(Availability $availability): void
    {
        $this->availabilities->set($availability->getCountry(), $availability);
    }

    public function getTrailerSearchUrl(): string
    {
        return 'https://www.youtube.com/results?search_query='.rawurlencode(trim(\sprintf('%s %s trailer', $this->name, $this->year ?? '')));
    }

    public function getImdbUrl(): string
    {
        return 'https://www.imdb.com/title/'.$this->imdbId.'/';
    }
}
