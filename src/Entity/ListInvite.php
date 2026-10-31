<?php

namespace App\Entity;

use App\Repository\ListInviteRepository;
use Doctrine\ORM\Mapping as ORM;

/** A one-time invite link. Only a hash of the token is stored. */
#[ORM\Entity(repositoryClass: ListInviteRepository::class)]
class ListInvite
{
    public const LIFETIME = '+7 days';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64, unique: true)]
    private string $tokenHash;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $usedAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $usedBy = null;

    public function __construct(
        #[ORM\ManyToOne]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private WatchList $list,
        #[ORM\ManyToOne]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private User $createdBy,
        string $token,
        #[ORM\Column]
        private bool $canEdit,
    ) {
        $this->tokenHash = self::hash($token);
        $this->createdAt = new \DateTimeImmutable();
        $this->expiresAt = $this->createdAt->modify(self::LIFETIME);
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getList(): WatchList
    {
        return $this->list;
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function canEdit(): bool
    {
        return $this->canEdit;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isUsable(): bool
    {
        return null === $this->usedAt && $this->expiresAt > new \DateTimeImmutable();
    }

    public function markUsed(User $user): void
    {
        $this->usedAt = new \DateTimeImmutable();
        $this->usedBy = $user;
    }

    public function getUsedAt(): ?\DateTimeImmutable
    {
        return $this->usedAt;
    }

    public function getUsedBy(): ?User
    {
        return $this->usedBy;
    }
}
