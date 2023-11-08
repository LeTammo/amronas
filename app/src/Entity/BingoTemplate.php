<?php

namespace App\Entity;

use App\Repository\BingoTemplateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BingoTemplateRepository::class)]
class BingoTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $size = 5;

    #[ORM\Column(type: Types::JSON)]
    private array $content = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getContent(): array
    {
        return $this->content;
    }

    public function setContent(array $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function isFilled(): bool
    {
        return count($this->content) === $this->size * $this->size;
    }
}
