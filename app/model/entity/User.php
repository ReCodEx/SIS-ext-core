<?php

namespace App\Model\Entity;

use App\Security\Roles;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Gravatar\Gravatar;
use App\Exceptions\ApiException;
use InvalidArgumentException;
use DateTime;
use DateTimeInterface;
use DateTimeImmutable;

/**
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 */
class User
{
    use CreateableEntity;
    use DeleteableEntity;

    public function __construct(
        string $email,
        string $firstName,
        string $lastName,
        string $titlesBeforeName,
        string $titlesAfterName,
        ?string $role,
    ) {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->titlesBeforeName = $titlesBeforeName;
        $this->titlesAfterName = $titlesAfterName;
        $this->email = $email;
        $this->isVerified = false;
        $this->isAllowed = true;
        $this->createdAt = new DateTime();
        $this->login = null;
        $this->avatarUrl = null;

        if (empty($role)) {
            $this->role = Roles::STUDENT_ROLE;
        } else {
            $this->role = $role;
        }
    }

    /**
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=\Ramsey\Uuid\Doctrine\UuidGenerator::class)
     * @var \Ramsey\Uuid\UuidInterface
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     */
    protected $titlesBeforeName;

    /**
     * @ORM\Column(type="string")
     */
    protected $firstName;

    /**
     * @ORM\Column(type="string")
     */
    protected $lastName;

    public function getName()
    {
        return trim("{$this->titlesBeforeName} {$this->firstName} {$this->lastName} {$this->titlesAfterName}");
    }

    /**
     * @ORM\Column(type="string")
     */
    protected $titlesAfterName;

    /**
     * @ORM\Column(type="string")
     */
    protected $email;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $avatarUrl;

    /**
     * If true, then set gravatar image based on user email.
     * @param bool $useGravatar
     */
    public function setGravatar(bool $useGravatar = true)
    {
        $this->avatarUrl = !$useGravatar ? null :
            Gravatar::image($this->email, 200, "retro", "g", "png", false)->url();
    }

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isVerified;

    public function isVerified()
    {
        return $this->isVerified;
    }

    public function setVerified($verified = true)
    {
        $this->isVerified = $verified;
    }

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isAllowed;

    public function isAllowed()
    {
        return $this->isAllowed;
    }

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $tokenValidityThreshold;

    /**
     * @ORM\Column(type="string")
     */
    protected $role;

    /**
     * @ORM\OneToOne(targetEntity="Login", mappedBy="user", cascade={"all"})
     */
    protected $login;


    /**
     * @return array
     */
    public function getNameParts(): array
    {
        return [
            "titlesBeforeName" => $this->titlesBeforeName,
            "firstName" => $this->firstName,
            "lastName" => $this->lastName,
            "titlesAfterName" => $this->titlesAfterName,
        ];
    }

    /**
     * Returns true if the user entity is associated with a local login entity.
     * @return bool
     */
    public function hasLocalAccount(): bool
    {
        return $this->login !== null;
    }

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var DateTime
     * When the last authentication or token renewal occurred.
     */
    protected $lastAuthenticationAt = null;

    /**
     * Update the last authentication time to present.
     * @param DateTime|null $time the authentication time (if null, current time is set)
     */
    public function updateLastAuthenticationAt(DateTime $time = null)
    {
        $this->lastAuthenticationAt = $time ?? new DateTime();
    }


    /*
     * Accessors
     */

    public function getId(): ?string
    {
        return $this->id === null ? null : (string)$this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function setTitlesBeforeName(string $titlesBeforeName): void
    {
        $this->titlesBeforeName = $titlesBeforeName;
    }

    public function setTitlesAfterName(string $titlesAfterName): void
    {
        $this->titlesAfterName = $titlesAfterName;
    }

    public function setRole(string $role): void
    {
        $this->role = $role;
    }

    public function setIsAllowed(bool $isAllowed): void
    {
        $this->isAllowed = $isAllowed;
    }

    public function getLogin(): ?Login
    {
        return $this->login;
    }

    public function setLogin(?Login $login): void
    {
        $this->login = $login;
    }

    public function getTokenValidityThreshold(): ?DateTime
    {
        return $this->tokenValidityThreshold;
    }

    public function setTokenValidityThreshold(DateTime $tokenValidityThreshold): void
    {
        $this->tokenValidityThreshold = $tokenValidityThreshold;
    }

    public function getLastAuthenticationAt(): ?DateTime
    {
        return $this->lastAuthenticationAt;
    }
}
