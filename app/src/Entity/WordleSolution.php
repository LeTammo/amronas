<?php

namespace App\Entity;

use App\Repository\WordleSolutionRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WordleSolutionRepository::class)]
class WordleSolution
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $correctWord;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $createdAt;

    #[ORM\OneToMany(mappedBy: 'solution', targetEntity: WordleGame::class)]
    private Collection $games;

    public function __construct(string $correctWord, DateTime $date) {
        $this->correctWord = $correctWord;
        $this->games = new ArrayCollection();
        $this->createdAt = $date;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCorrectWord(): ?string
    {
        return $this->correctWord;
    }

    public function setCorrectWord(string $correctWord): self
    {
        $this->correctWord = $correctWord;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, WordleGame>
     */
    public function getGames(): Collection
    {
        return $this->games;
    }

    public function addGame(WordleGame $game): self
    {
        if (!$this->games->contains($game)) {
            $this->games->add($game);
            $game->setSolution($this);
        }

        return $this;
    }

    public function removeGame(WordleGame $game): self
    {
        if ($this->games->removeElement($game)) {
            // set the owning side to null (unless already changed)
            if ($game->getSolution() === $this) {
                $game->setSolution(null);
            }
        }

        return $this;
    }
}
