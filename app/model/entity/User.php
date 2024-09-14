<?php

namespace App\Model\Entity;

use App\Security\Roles;
use Doctrine\ORM\Mapping as ORM;
use DateTime;
use JsonSerializable;

/**
 * @ORM\Entity
 * This holds a copy of user-related data from ReCodEx.
 */
class User implements JsonSerializable
{
    use CreateableEntity;
    use UpdateableEntity;

    /**
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * A copy of ID from ReCodEx
     */
    protected $id;

    /**
     * @ORM\Column(type="uuid")
     * ID of ReCodEx instance where the user belongs to.
     */
    protected $instanceId;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     * Also known as UKCO.
     */
    protected $sisId = null;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     * Alhpanumerical login generated from name (which is used as alternative login to SIS).
     */
    protected $sisLogin = null;

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
     * @ORM\Column(type="string")
     */
    protected $role;

    /**
     * @ORM\Column(type="string", length=32)
     * Copied from UserSettings
     */
    protected $defaultLanguage;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * This is not a copy of ReCodEx field, it is used to manage validity of SIS-ext tokens.
     */
    protected $tokenValidityThreshold;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * When the user data (corresponding SisUser entity) were last loaded from SIS.
     * This needs to be kept here as well since no SisUser entity may have been loaded (yet).
     */
    protected $sisUserLoaded = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * When the SIS events were loaded for the last time (events, affiliations...).
     * This needs to be kept here since the events and courses may be shared and (also)
     * no affiliations may have beeen loaded the last time.
     */
    protected $sisEventsLoaded = null;

    public function __construct(
        string $id,
        string $instanceId,
        string $email,
        string $firstName,
        string $lastName,
        string $titlesBeforeName,
        string $titlesAfterName,
        ?string $role,
    ) {
        $this->id = $id;
        $this->instanceId = $instanceId;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->titlesBeforeName = $titlesBeforeName;
        $this->titlesAfterName = $titlesAfterName;
        $this->email = $email;
        $this->avatarUrl = null;

        if (empty($role)) {
            $this->role = Roles::STUDENT_ROLE;
        } else {
            $this->role = $role;
        }

        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    /*
     * Accessors
     */

    public function getId(): string
    {
        return $this->id;
    }

    public function getInstanceId(): string
    {
        return $this->instanceId;
    }

    public function getSisId(): ?string
    {
        return $this->sisId;
    }

    public function setSisId(?string $sisId): void
    {
        $this->sisId = $sisId;
    }

    public function getSisLogin(): ?string
    {
        return $this->sisLogin;
    }

    public function setSisLogin(?string $sisLogin): void
    {
        $this->sisLogin = $sisLogin;
    }

    // name

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


    public function getNameParts(): array
    {
        return [
            "titlesBeforeName" => $this->titlesBeforeName,
            "firstName" => $this->firstName,
            "lastName" => $this->lastName,
            "titlesAfterName" => $this->titlesAfterName,
        ];
    }

    public function getName()
    {
        return trim("{$this->titlesBeforeName} {$this->firstName} {$this->lastName} {$this->titlesAfterName}");
    }

    // other ReCodEx data

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): void
    {
        $this->avatarUrl = $avatarUrl;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): void
    {
        $this->role = $role;
    }

    public function getDefaultLanguage(): string
    {
        return $this->defaultLanguage;
    }

    public function setDefaultLanguage(string $lang): void
    {
        $this->defaultLanguage = $lang;
    }

    // security (access control)

    public function getTokenValidityThreshold(): ?DateTime
    {
        return $this->tokenValidityThreshold;
    }

    public function setTokenValidityThreshold(DateTime $tokenValidityThreshold): void
    {
        $this->tokenValidityThreshold = $tokenValidityThreshold;
    }

    public function getSisUserLoaded(): ?DateTime
    {
        return $this->sisUserLoaded;
    }

    public function setSisUserLoaded(?DateTime $when = new DateTime()): void
    {
        $this->sisUserLoaded = $when;
    }

    public function getSisEventsLoaded(): ?DateTime
    {
        return $this->sisEventsLoaded;
    }

    public function setSisEventsLoaded(?DateTime $when = new DateTime()): void
    {
        $this->sisEventsLoaded = $when;
    }

    // JSON interface

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->getId(),
            'sisId' => $this->getSisId(),
            'sisLogin' => $this->getSisLogin(),
            'name' => $this->getNameParts(),
            'email' => $this->getEmail(),
            'avatarUrl' => $this->getAvatarUrl(),
            'role' => $this->getRole(),
            'defaultLanguage' => $this->getDefaultLanguage(),
            'sisUserLoaded' => $this->getSisUserLoaded()?->getTimestamp(),
            'sisEventsLoaded' => $this->getSisEventsLoaded()?->getTimestamp(),
            'createdAt' => $this->getCreatedAt()->getTimestamp(),
            'updatedAt' => $this->getUpdatedAt()->getTimestamp(),
        ];
    }
}
