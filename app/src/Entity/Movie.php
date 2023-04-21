<?php

namespace App\Entity;

use App\Repository\MovieRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MovieRepository::class)]
class Movie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $imdbId = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $genre = [];

    #[ORM\Column(nullable: true)]
    private ?int $year = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $trailerYoutubeId = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $streamProvider = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $posterUrl = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getImdbId(): ?string
    {
        return $this->imdbId;
    }

    public function setImdbId(string $imdbId): self
    {
        $this->imdbId = $imdbId;

        return $this;
    }

    public function getGenre(): array
    {
        return $this->genre;
    }

    public function setGenre(?array $genre): self
    {
        $this->genre = $genre;

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(?int $year): self
    {
        $this->year = $year;

        return $this;
    }

    public function getTrailerYoutubeId(): ?string
    {
        return $this->trailerYoutubeId;
    }

    public function setTrailerYoutubeId(?string $trailerYoutubeId): self
    {
        $this->trailerYoutubeId = $trailerYoutubeId;

        return $this;
    }

    public function getStreamProvider(): array
    {
        return $this->streamProvider;
    }

    public function setStreamProvider(?array $streamProvider): self
    {
        $this->streamProvider = $streamProvider;

        return $this;
    }

    public function toggleProvider(string $provider): bool
    {
        $providerIsInList = in_array($provider, $this->streamProvider);

        if ($providerIsInList) {
            $this->streamProvider = array_diff($this->streamProvider, [$provider]);
        } else {
            $this->streamProvider[] = $provider;
        }
        return $providerIsInList;
    }

    public function getPosterUrl(): ?string
    {
        return $this->posterUrl;
    }

    public function setPosterUrl(?string $posterUrl): self
    {
        $this->posterUrl = $posterUrl;

        return $this;
    }
}
