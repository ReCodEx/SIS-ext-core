<?php

namespace App\Model\Entity;

use App\Security\Roles;
use Doctrine\ORM\Mapping as ORM;
use DateTime;
use JsonSerializable;
use InvalidArgumentException;

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

    /**
     * @ORM\Column(type="string", nullable=true)
     * Prefix of the ReCodEx authentication token used to perform operations on ReCodEx API.
     * The suffix is stored in our token used to authenticate agains this API as a payload.
     * The divison of the token in two parts makes it more difficult to get the whole token and breach the security.
     * This column SHOULD NEVER be sent over to the client side (or anywhere else).
     */
    protected $recodexToken = null;

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

    public function getTitlesBeforeName(): string
    {
        return $this->titlesBeforeName;
    }

    public function setTitlesBeforeName(string $titlesBeforeName): void
    {
        $this->titlesBeforeName = $titlesBeforeName;
    }

    public function getTitlesAfterName(): string
    {
        return $this->titlesAfterName;
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

    public function getRecodexToken(): ?string
    {
        return $this->recodexToken;
    }

    /**
     * Set the recodex token field by chopping off the right prefix of the whole token and returning the suffix.
     * @param string|null $wholeToken a complete JWT in string form or null (if the token should be reset)
     * @return string|null the remaining suffix which was not stored to this entity (or null, if the token is reset)
     */
    public function setRecodexToken(?string $wholeToken): ?string
    {
        if (!$wholeToken) {
            $this->recodexToken = null;
            return null;
        }

        if (strlen($wholeToken < 4)) { // sanity check
            throw new InvalidArgumentException("ReCodEx security token is too short to be a valid JWT token.");
        }

        $len = strlen($wholeToken);
        if ($len < 16) {
            $len /= 2;  // token is quite short, let's split it in half
        } elseif ($len > 250) {
            $len = 250;  // token is too long, make sure it fits the DB field
        } else {
            $len -= 8; // in regular cases, the last 8 chars are chopped off
        }

        $this->recodexToken = substr($wholeToken, 0, $len); // prefix
        return substr($wholeToken, $len); // suffix
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
            'role' => $this->getRole(),
            'defaultLanguage' => $this->getDefaultLanguage(),
            'sisUserLoaded' => $this->getSisUserLoaded()?->getTimestamp(),
            'sisEventsLoaded' => $this->getSisEventsLoaded()?->getTimestamp(),
            'createdAt' => $this->getCreatedAt()->getTimestamp(),
            'updatedAt' => $this->getUpdatedAt()->getTimestamp(),
        ];
    }
}
