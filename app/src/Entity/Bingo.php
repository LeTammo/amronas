<?php

namespace App\Entity;

use App\Repository\BingoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BingoRepository::class)]
class Bingo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::JSON)]
    private array $fields = [];

    #[ORM\Column(type: Types::JSON)]
    private array $crossed = [];

    #[ORM\Column(nullable: true)]
    private ?bool $isFinished = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isSolved = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'bingos')]
    #[ORM\JoinColumn(nullable: false)]
    private User $player;

    public function __construct(User $player, BingoTemplate $template)
    {
        $this->player = $player;
        $this->createdAt = new \DateTimeImmutable();
        $this->isFinished = false;
        $this->isSolved = false;

        $randomizedContent = $template->getContent();
        shuffle($randomizedContent);
        $this->fields = $randomizedContent;

        $this->crossed = array_fill(0, $template->getSize()*$template->getSize(), false);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function setFields(array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    public function isCrossed(int $index): bool
    {
        return $this->crossed[$index];
    }

    public function toggle(int $index): self
    {
        $this->crossed[$index] = !$this->crossed[$index];

        return $this;
    }

    public function isIsFinished(): ?bool
    {
        return $this->isFinished;
    }

    public function setIsFinished(?bool $isFinished): self
    {
        $this->isFinished = $isFinished;

        return $this;
    }

    public function isIsSolved(): ?bool
    {
        return $this->isSolved;
    }

    public function setIsSolved(?bool $isSolved): self
    {
        $this->isSolved = $isSolved;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

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
}
