<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/** Someone the owner shared the list with: either view only or allowed to edit. */
#[ORM\Entity]
#[ORM\UniqueConstraint(columns: ['list_id', 'user_id'])]
class ListMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private \DateTimeImmutable $joinedAt;

    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'members')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private WatchList $list,
        #[ORM\ManyToOne]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private User $user,
        #[ORM\Column]
        private bool $canEdit,
    ) {
        $this->joinedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getList(): WatchList
    {
        return $this->list;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function canEdit(): bool
    {
        return $this->canEdit;
    }

    public function setCanEdit(bool $canEdit): static
    {
        $this->canEdit = $canEdit;

        return $this;
    }

    public function getJoinedAt(): \DateTimeImmutable
    {
        return $this->joinedAt;
    }
}
