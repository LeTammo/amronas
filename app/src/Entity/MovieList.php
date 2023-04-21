<?php

namespace App\Entity;

use App\Repository\MovieListRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: MovieListRepository::class)]
class MovieList
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'movie_list_maintainer')]
    private Collection $maintainer;

    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'movie_list_subscriber')]
    private Collection $subscriber;

    #[ORM\OneToMany(mappedBy: 'movieList', targetEntity: MovieListEntry::class, orphanRemoval: true)]
    private Collection $movies;


    public function __construct()
    {
        $this->maintainer = new ArrayCollection();
        $this->subscriber = new ArrayCollection();
        $this->movies = new ArrayCollection();
    }

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

    /**
     * @return Collection<int, UserInterface>
     */
    public function getMaintainer(): Collection
    {
        return $this->maintainer;
    }

    public function getMaintainerToString(User $currentUser): array
    {
        return array_map(fn (User $user) => $user === $currentUser ? 'Du' : ucfirst($user->getUsername()), $this->maintainer->toArray());
    }

    public function addMaintainer(UserInterface $maintainer): self
    {
        if (!$this->maintainer->contains($maintainer)) {
            $this->maintainer->add($maintainer);
        }

        return $this;
    }

    public function removeMaintainer(UserInterface $maintainer): self
    {
        $this->maintainer->removeElement($maintainer);

        return $this;
    }

    /**
     * @return Collection<int, UserInterface>
     */
    public function getSubscriber(): Collection
    {
        return $this->subscriber;
    }

    public function addSubscriber(UserInterface $subscriber): self
    {
        if (!$this->subscriber->contains($subscriber)) {
            $this->subscriber->add($subscriber);
        }

        return $this;
    }

    public function removeSubscriber(UserInterface $subscriber): self
    {
        $this->subscriber->removeElement($subscriber);

        return $this;
    }

    /**
     * @return Collection<int, MovieListEntry>
     */
    public function getMovies(): Collection
    {
        return $this->movies;
    }

    public function addMovie(MovieListEntry $movie): self
    {
        if (!$this->movies->contains($movie)) {
            $this->movies->add($movie);
            $movie->setMovieList($this);
        }

        return $this;
    }

    public function removeMovie(MovieListEntry $movie): self
    {
        if ($this->movies->removeElement($movie)) {
            // set the owning side to null (unless already changed)
            if ($movie->getMovieList() === $this) {
                $movie->setMovieList(null);
            }
        }

        return $this;
    }

    public function getUnwatchedMovies(): Collection
    {
        return $this->movies->filter(fn (MovieListEntry $movie) => !$movie->getTimeWatched());
    }

    public function getWatchedMovies(): Collection
    {
        return $this->movies->filter(fn (MovieListEntry $movie) => $movie->getTimeWatched());
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
