<?php

namespace App\Entity;

use App\Enum\Priority;
use App\Repository\ListEntryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ListEntryRepository::class)]
#[ORM\UniqueConstraint(columns: ['list_id', 'title_id'])]
class ListEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private \DateTimeImmutable $addedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $watchedAt = null;

    #[ORM\Column(enumType: Priority::class)]
    private Priority $priority = Priority::Normal;

    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'entries')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private WatchList $list,
        #[ORM\ManyToOne]
        #[ORM\JoinColumn(nullable: false)]
        private Title $title,
        #[ORM\ManyToOne]
        #[ORM\JoinColumn(onDelete: 'SET NULL')]
        private ?User $addedBy,
    ) {
        $this->addedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getList(): WatchList
    {
        return $this->list;
    }

    public function getTitle(): Title
    {
        return $this->title;
    }

    public function getAddedBy(): ?User
    {
        return $this->addedBy;
    }

    public function getAddedAt(): \DateTimeImmutable
    {
        return $this->addedAt;
    }

    public function setAddedAt(\DateTimeImmutable $addedAt): static
    {
        $this->addedAt = $addedAt;

        return $this;
    }

    public function getWatchedAt(): ?\DateTimeImmutable
    {
        return $this->watchedAt;
    }

    public function isWatched(): bool
    {
        return null !== $this->watchedAt;
    }

    public function setWatchedAt(?\DateTimeImmutable $watchedAt): static
    {
        $this->watchedAt = $watchedAt;

        return $this;
    }

    public function getPriority(): Priority
    {
        return $this->priority;
    }

    public function setPriority(Priority $priority): static
    {
        $this->priority = $priority;

        return $this;
    }
}
