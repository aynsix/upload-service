<?php

declare(strict_types=1);

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass="UserRepository")
 * @ORM\Table(name="`user`")
 */
class User implements UserInterface
{
    /**
     * @var Uuid
     *
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, unique=true)
     */
    protected $email;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $emailVerified = false;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $enabled;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $securityToken;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    protected $salt;

    /**
     * @var array
     * @ORM\Column(type="json_array")
     */
    protected $roles = [];

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $password;

    /**
     * @var string
     */
    protected $plainPassword;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastInviteAt;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * Not mapped.
     *
     * @var bool
     */
    protected $inviteByEmail = false;

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    public function getId(): string
    {
        if (null === $this->id) {
            return '';
        }

        return $this->id->__toString();
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function hasPassword(): bool
    {
        return null !== $this->password;
    }

    public function getSalt()
    {
        return $this->salt;
    }

    public function getUsername()
    {
        return $this->email;
    }

    public function eraseCredentials()
    {
        $this->plainPassword = null;
    }

    public function setSalt(string $salt): void
    {
        $this->salt = $salt;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): void
    {
        $this->plainPassword = $plainPassword;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getSecurityToken(): ?string
    {
        return $this->securityToken;
    }

    public function setSecurityToken(?string $securityToken): void
    {
        $this->securityToken = $securityToken;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }

    public function setEmailVerified(bool $emailVerified): void
    {
        $this->emailVerified = $emailVerified;
    }

    public function isInviteByEmail(): bool
    {
        return $this->inviteByEmail;
    }

    public function setInviteByEmail(bool $inviteByEmail): void
    {
        $this->inviteByEmail = $inviteByEmail;
    }

    public function getLastInviteAt(): DateTime
    {
        return $this->lastInviteAt;
    }

    public function setLastInviteAt(DateTime $lastInviteAt): void
    {
        $this->lastInviteAt = $lastInviteAt;
    }

    public function canBeInvited(int $allowedDelay): bool
    {
        return null === $this->lastInviteAt
            || $this->lastInviteAt->getTimestamp() < time() - $allowedDelay;
    }
}
