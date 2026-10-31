<?php

namespace App\Entity;

use App\Enum\ListType;
use App\Repository\WatchListRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: WatchListRepository::class)]
class WatchList
{
    /** Icons (Tabler) a list can have; shown in the navigation. */
    public const ICONS = [
        'movie', 'video', 'heart', 'circle-heart', 'badge-3d', 'device-projector', 'ticket', 'stereo-glasses', 'chair-director', 'device-tv',
        'clipboard-heart', 'brand-bilibili', 'brand-funimation', 'bat', 'cat', 'dog', 'alien', 'antenna', 'hourglass-empty', 'universe',
        'music', 'users', 'user-screen', 'mickey', 'flame', 'butterfly', 'snail', 'canary', 'flood', 'mountain',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 80)]
    private string $name = '';

    /** Null for lists from before icons existed: then whoever may edit is asked to pick one. */
    #[ORM\Column(length: 40, nullable: true)]
    #[Assert\NotBlank(message: 'list.icon_required')]
    #[Assert\Choice(choices: self::ICONS)]
    private ?string $icon = null;

    #[ORM\Column(enumType: ListType::class)]
    private ListType $type = ListType::Mixed;

    /** Country for streaming offers (ISO 3166-1 alpha-2, lowercase). Defaults to the owner's country. */
    #[ORM\Column(length: 2)]
    private string $country;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $owner;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, ListMember> */
    #[ORM\OneToMany(targetEntity: ListMember::class, mappedBy: 'list', cascade: ['persist'], orphanRemoval: true)]
    private Collection $members;

    /** @var Collection<int, ListEntry> */
    #[ORM\OneToMany(targetEntity: ListEntry::class, mappedBy: 'list', orphanRemoval: true)]
    private Collection $entries;

    public function __construct(User $owner)
    {
        $this->owner = $owner;
        $this->country = $owner->getCountry();
        $this->createdAt = new \DateTimeImmutable();
        $this->members = new ArrayCollection();
        $this->entries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = trim($name);

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getType(): ListType
    {
        return $this->type;
    }

    public function setType(ListType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = strtolower($country);

        return $this;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function isOwner(User $user): bool
    {
        return $this->owner === $user;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return Collection<int, ListMember> */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function getMember(User $user): ?ListMember
    {
        return $this->members->findFirst(static fn (int $key, ListMember $member) => $member->getUser() === $user);
    }

    public function addMember(User $user, bool $canEdit): ListMember
    {
        if ($member = $this->getMember($user)) {
            return $member;
        }

        $member = new ListMember($this, $user, $canEdit);
        $this->members->add($member);

        return $member;
    }

    public function removeMember(ListMember $member): void
    {
        $this->members->removeElement($member);
    }

    /** @return list<User> owner first, then everyone who may edit */
    public function getEditors(): array
    {
        return [$this->owner, ...$this->members->filter(static fn (ListMember $m) => $m->canEdit())->map(static fn (ListMember $m) => $m->getUser())->getValues()];
    }

    public function canView(User $user): bool
    {
        return $this->isOwner($user) || null !== $this->getMember($user);
    }

    public function canEdit(User $user): bool
    {
        return $this->isOwner($user) || ($this->getMember($user)?->canEdit() ?? false);
    }

    /** @return Collection<int, ListEntry> */
    public function getEntries(): Collection
    {
        return $this->entries;
    }

    public function countUnwatched(): int
    {
        return $this->entries->filter(static fn (ListEntry $e) => !$e->isWatched())->count();
    }

    public function countWatched(): int
    {
        return $this->entries->count() - $this->countUnwatched();
    }
}
