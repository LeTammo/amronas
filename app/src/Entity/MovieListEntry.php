<?php

namespace App\Entity;

use App\Repository\MovieListEntryRepository;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: MovieListEntryRepository::class)]
class MovieListEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Movie $movie = null;

    #[ORM\ManyToOne(inversedBy: 'movies')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MovieList $movieList = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $timeAdded = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $timeWatched = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $addedBy = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMovie(): ?Movie
    {
        return $this->movie;
    }

    public function setMovie(?Movie $movie): self
    {
        $this->movie = $movie;

        return $this;
    }

    public function getMovieList(): ?MovieList
    {
        return $this->movieList;
    }

    public function setMovieList(?MovieList $movieList): self
    {
        $this->movieList = $movieList;

        return $this;
    }

    public function getTimeAdded(): ?DateTimeInterface
    {
        return $this->timeAdded;
    }

    public function setTimeAdded(DateTimeInterface $timeAdded): self
    {
        $this->timeAdded = $timeAdded;

        return $this;
    }

    public function getTimeWatched(): ?DateTimeInterface
    {
        return $this->timeWatched;
    }

    public function setTimeWatched(?DateTimeInterface $timeWatched): self
    {
        $this->timeWatched = $timeWatched;

        return $this;
    }

    public function getAddedBy(): ?UserInterface
    {
        return $this->addedBy;
    }

    public function setAddedBy(UserInterface $addedBy): self
    {
        $this->addedBy = $addedBy;

        return $this;
    }

    public function __toString(): string
    {
        return $this->movie->getName();
    }
}
