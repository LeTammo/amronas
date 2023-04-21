<?php

namespace App\Entity;

use App\Repository\WordleGameRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WordleGameRepository::class)]
class WordleGame
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'games')]
    #[ORM\JoinColumn(nullable: false)]
    private ?WordleSolution $solution;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $player = null;

    #[ORM\OneToMany(mappedBy: 'game', targetEntity: WordleGuess::class)]
    private Collection $guesses;

    #[ORM\Column(nullable: true)]
    private ?bool $isFinished;

    #[ORM\Column(nullable: true)]
    private ?bool $isSolved;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $createdAt;

    public function __construct(WordleSolution $solution)
    {
        $this->solution = $solution;
        $this->guesses = new ArrayCollection();
        $this->createdAt = new DateTime();
        $this->isFinished = false;
        $this->isSolved = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSolution(): ?WordleSolution
    {
        return $this->solution;
    }

    public function setSolution(?WordleSolution $solution): self
    {
        $this->solution = $solution;

        return $this;
    }

    public function getPlayer(): ?User
    {
        return $this->player;
    }

    public function setPlayer(?User $player): self
    {
        $this->player = $player;

        return $this;
    }

    /**
     * @return Collection<int, WordleGuess>
     */
    public function getGuesses(): Collection
    {
        return $this->guesses;
    }

    public function addGuess(WordleGuess $guess): self
    {
        if (!$this->guesses->contains($guess)) {
            $this->guesses->add($guess);
        }

        return $this;
    }

    public function removeGuess(WordleGuess $guess): self
    {
        $this->guesses->removeElement($guess);

        return $this;
    }

    public function isFinished(): ?bool
    {
        return $this->isFinished;
    }

    public function setIsFinished(?bool $isFinished): self
    {
        $this->isFinished = $isFinished;

        return $this;
    }

    public function isSolved(): ?bool
    {
        return $this->isSolved;
    }

    public function setIsSolved(?bool $isSolved): self
    {
        $this->isSolved = $isSolved;

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

    public function getLetterStatus(string $testLetter): string
    {
        $isCorrect = false;
        $isPresent = false;
        $isAbsent = false;

        foreach ($this->guesses as $guess) {
            $info = $guess->getInfo();
            $word = $guess->getGuessedWord();
            for ($i = 0; $i < strlen($word); $i++) {
                if ($word[$i] !== $testLetter)
                    continue;
                if ($info[$i] === 'correct')
                    $isCorrect = true;
                if ($info[$i] === 'present')
                    $isPresent = true;
                $isAbsent = true;
            }
        }

        if ($isCorrect)
            return 'correct';
        if ($isPresent)
            return 'present';
        if ($isAbsent)
            return 'absent';
        return '';
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
    #[ORM\Column]
    private int $oldId;
    public function getOldId(): ?int{return $this->oldId;}
    public function setOldId(int $oldId): self{$this->oldId = $oldId;return $this;}
}
