<?php

namespace App\Entity;

use App\Repository\WordleGuessRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WordleGuessRepository::class)]
class WordleGuess
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $guessedWord;

    #[ORM\ManyToOne(inversedBy: 'guesses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?WordleGame $game;

    #[ORM\Column(type: Types::JSON)]
    private array $info = [];

    #[ORM\Column]
    private ?bool $isCorrect;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $createdAt;

    public function __construct(WordleGame $game, string $guessedWord, bool $isCorrect)
    {
        $this->createdAt = new DateTime();
        $this->game = $game;
        $this->guessedWord = $guessedWord;
        $this->isCorrect = $isCorrect;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGuessedWord(): ?string
    {
        return $this->guessedWord;
    }

    public function setGuessedWord(string $guessedWord): self
    {
        $this->guessedWord = $guessedWord;

        return $this;
    }

    public function getGame(): ?WordleGame
    {
        return $this->game;
    }

    public function setGame(?WordleGame $game): void
    {
        $this->game = $game;
    }

    public function getInfo(): array
    {
        return $this->info;
    }

    public function setInfo(array $info): self
    {
        $this->info = $info;

        return $this;
    }

    public function isCorrect(): ?bool
    {
        return $this->isCorrect;
    }

    public function setIsCorrect(bool $isCorrect): self
    {
        $this->isCorrect = $isCorrect;

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
}
