<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use JsonSerializable;

/**
 * @ORM\Entity
 * This is a cache for user-related data from SIS.
 */
class SisUser implements JsonSerializable
{
    use CreateableEntity;
    use UpdateableEntity;

    /**
     * @ORM\Id
     * @ORM\Column(type="string", unique=true)
     * Also known as UKCO.
     */
    protected $id;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     * Alhpanumerical login generated from name (which is used as alternative login to SIS).
     */
    protected $login = null;

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

    public function __construct(
        string $id,
        string $login,
        string $email,
        string $firstName,
        string $lastName,
        string $titlesBeforeName,
        string $titlesAfterName,
    ) {
        $this->id = $id;
        $this->login = $login;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->titlesBeforeName = $titlesBeforeName;
        $this->titlesAfterName = $titlesAfterName;
        $this->email = $email;
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

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setLogin(?string $login): void
    {
        $this->login = $login;
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

    public function getNameParts(): array
    {
        return [
            "titlesBeforeName" => $this->titlesBeforeName,
            "firstName" => $this->firstName,
            "lastName" => $this->lastName,
            "titlesAfterName" => $this->titlesAfterName,
        ];
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    // JSON interface

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->getId(),
            'login' => $this->getLogin(),
            'name' => $this->getNameParts(),
            'email' => $this->getEmail(),
            'createdAt' => $this->getCreatedAt()->getTimestamp(),
            'updatedAt' => $this->getUpdatedAt()->getTimestamp(),
        ];
    }
}
