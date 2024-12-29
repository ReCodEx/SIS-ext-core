<?php

namespace App\Helpers;

use App\Exceptions\RecodexApiException;
use App\Model\Entity\User;
use Nette;

/**
 * Wraper for User data sent from ReCodEx API.
 */
class RecodexUser
{
    use Nette\SmartObject;

    /** @var array parsed JSON data from user view */
    private array $data;

    /** @var RecodexApiHelper that created/loaded this user structure */
    private RecodexApiHelper $recodexApi;

    /**
     * @param array $data parsed JSON user view
     */
    public function __construct(array $data, RecodexApiHelper $recodexApi)
    {
        $this->data = $data;
        $this->recodexApi = $recodexApi;
    }

    /**
     * @return string ReCodEx user ID
     * @throws RecodexApiException if the ID is missing
     */
    public function getId(): string
    {
        if (empty($this->data['id'])) {
            throw new RecodexApiException("User ID missing in the ReCodEx user view response.");
        }
        return $this->data['id'];
    }

    public function getSisId(): ?string
    {
        return $this->data['privateData']['externalIds'][$this->recodexApi->getSisIdKey()] ?? null;
    }

    public function getSisLogin(): ?string
    {
        return $this->data['privateData']['externalIds'][$this->recodexApi->getSisLoginKey()] ?? null;
    }

    public function getEmail(): string
    {
        if (empty($this->data['privateData']['email'])) {
            throw new RecodexApiException("User email is missing.");
        }
        return $this->data['privateData']['email'];
    }

    public function getFirstName(): string
    {
        $name = $this->data['name']['firstName'] ?? null;
        if ($name === null) {
            throw new RecodexApiException("First name is missing.");
        }
        return $name;
    }

    public function getLastName(): string
    {
        $name = $this->data['name']['lastName'] ?? null;
        if ($name === null) {
            throw new RecodexApiException("Last name is missing.");
        }
        return $name;
    }

    public function getRole(): string
    {
        $role = $this->data['privateData']['role'] ?? null;
        if ($role === null) {
            throw new RecodexApiException("User role is missing.");
        }
        return $role;
    }

    public function getDefaultLanguage(): string
    {
        return $this->data['privateData']['settings']['defaultLanguage'] ?? 'en';
    }

    /**
     * Creates a new user entity from ReCodEx user data.
     * @param string $instanceId to which the user belongs to
     * @return User (not persisted)
     * @throws RecodexApiException
     */
    public function createUser(string $instanceId): User
    {
        if (!in_array($instanceId, $this->data['privateData']['instanceIds'] ?? [])) {
            throw new RecodexApiException("The user does not belong into the given ReCodEx instance.");
        }

        $user = new User(
            $this->getId(),
            $instanceId,
            $this->getEmail(),
            $this->getFirstName(),
            $this->getLastName(),
            $this->data['name']['titlesBeforeName'] ?? '',
            $this->data['name']['titlesAfterName'] ?? '',
            $this->getRole()
        );

        $user->setSisId($this->getSisId());
        $user->setSisLogin($this->getSisLogin());
        $user->setDefaultLanguage($this->getDefaultLanguage());
        return $user;
    }

    /**
     * Perform an update of an existing User entity overriding any fields that differ.
     * @param User $user entity to be updated with data from ReCodEx.
     * @return bool true if anything was changed
     * @throws RecodexApiException
     */
    public function updateUser(User $user): bool
    {
        if ($user->getId() !== $this->getId()) {
            throw new RecodexApiException("User ID mismatch.");
        }

        if (!in_array($user->getInstanceId(), $this->data['privateData']['instanceIds'] ?? [])) {
            throw new RecodexApiException("User instance ID mismatch.");
        }

        $properties = [ // keys are suffixes for getter/setter methods
            'SisId' => $this->getSisId(),
            'SisLogin' => $this->getSisLogin(),
            'Email' => $this->getEmail(),
            'FirstName' => $this->getFirstName(),
            'LastName' => $this->getLastName(),
            'TitlesBeforeName' => $this->data['name']['titlesBeforeName'] ?? '',
            'TitlesAfterName' => $this->data['name']['titlesAfterName'] ?? '',
            'Role' => $this->getRole(),
            'DefaultLanguage' => $this->getDefaultLanguage(),
        ];

        $changed = false;
        foreach ($properties as $key => $value) {
            $getter = "get$key";
            if ($user->$getter() !== $value) {
                $setter = "set$key";
                $user->$setter($value);
                $changed = true;
            }
        }

        return $changed;
    }
}
