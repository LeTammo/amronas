<?php

namespace App\Entity;

use App\Enum\ListView;
use App\Enum\SortField;
use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['username'], message: 'user.username.taken')]
#[UniqueEntity(fields: ['email'], message: 'user.email.taken')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 30)]
    #[Assert\Regex('/^[\w.-]+$/u', message: 'user.username.chars')]
    private string $username = '';

    #[ORM\Column(length: 180, unique: true, nullable: true)]
    #[Assert\Email]
    private ?string $email = null;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    #[ORM\Column]
    private string $password = '';

    #[ORM\Column(length: 2)]
    private string $locale = 'de';

    /** Country used for streaming offers (ISO 3166-1 alpha-2, lowercase). */
    #[ORM\Column(length: 2)]
    private string $country = 'de';

    /** @var list<string> ids of the streaming services the user subscribed to */
    #[ORM\Column(type: Types::JSON)]
    private array $services = [];

    #[ORM\Column(enumType: SortField::class)]
    private SortField $sortField = SortField::Rating;

    #[ORM\Column]
    private bool $sortDescending = true;

    #[ORM\Column(enumType: ListView::class)]
    private ListView $listView = ListView::List;

    /** @var list<int> ids of the lists opened last, newest first */
    #[ORM\Column(type: Types::JSON)]
    private array $recentLists = [];

    /** False until the user went through the first-login setup. */
    #[ORM\Column]
    private bool $setupCompleted = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = trim($username);

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email ? mb_strtolower(trim($email)) : null;

        return $this;
    }

    public function getRoles(): array
    {
        return array_values(array_unique([...$this->roles, 'ROLE_USER']));
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): static
    {
        $this->roles = array_values(array_diff($roles, ['ROLE_USER']));

        return $this;
    }

    public function isAdmin(): bool
    {
        return \in_array('ROLE_ADMIN', $this->roles, true);
    }

    public function setAdmin(bool $admin): static
    {
        return $this->setRoles($admin ? [...$this->roles, 'ROLE_ADMIN'] : array_diff($this->roles, ['ROLE_ADMIN']));
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

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

    /** @return list<string> */
    public function getServices(): array
    {
        return $this->services;
    }

    /** @param list<string> $services */
    public function setServices(array $services): static
    {
        $this->services = array_values(array_unique($services));

        return $this;
    }

    public function getSortField(): SortField
    {
        return $this->sortField;
    }

    public function setSortField(SortField $sortField): static
    {
        $this->sortField = $sortField;

        return $this;
    }

    public function isSortDescending(): bool
    {
        return $this->sortDescending;
    }

    public function setSortDescending(bool $sortDescending): static
    {
        $this->sortDescending = $sortDescending;

        return $this;
    }

    public function getListView(): ListView
    {
        return $this->listView;
    }

    public function setListView(ListView $listView): static
    {
        $this->listView = $listView;

        return $this;
    }

    /** @return list<int> */
    public function getRecentLists(): array
    {
        return $this->recentLists;
    }

    /** Remembers a list as opened; returns false if nothing changed. */
    public function addRecentList(int $listId, int $keep = 3): bool
    {
        $recent = \array_slice(array_values(array_unique([$listId, ...$this->recentLists])), 0, $keep);
        if ($recent === $this->recentLists) {
            return false;
        }
        $this->recentLists = $recent;

        return true;
    }

    public function isSetupCompleted(): bool
    {
        return $this->setupCompleted;
    }

    public function setSetupCompleted(bool $setupCompleted): static
    {
        $this->setupCompleted = $setupCompleted;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** Ensure the session doesn't contain the password hash. */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }
}
