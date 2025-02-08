<?php

namespace App\Helpers;

use App\Model\Entity\User;
use App\Model\Entity\SisUser;

/**
 * Handles comparison and updates of user data from SIS user records.
 */
class UserUpdater
{
    /**
     * Maps property name to getter method name in User objects.
     */
    private array $oldGetters = ['login' => 'getSisLogin'];

    /**
     * Maps property name to setter method name in User objects.
     */
    private array $oldSetters = ['login' => 'setSisLogin'];

    /**
     * Maps property name to getter method name in SisUser objects.
     */
    private array $newGetters = ['login' => 'getLogin'];

    public function __construct()
    {
        // add regular stuff that is named the same in both entities
        foreach (['titlesBeforeName', 'firstName', 'lastName', 'titlesAfterName', 'email'] as $key) {
            $this->oldGetters[$key] = 'get' . ucfirst($key);
            $this->oldSetters[$key] = 'set' . ucfirst($key);
            $this->newGetters[$key] = 'get' . ucfirst($key);
        }
    }

    /**
     * Compare user and sis user record and return a list of fileds that differ
     * with old (user) and new (sis user) values.
     * @param User $user (from ReCodEx) - old
     * @param SisUser $sisUser - new
     * @return array keys are property names, values are arrays with 'old' and 'new' values
     */
    public function diff(User $user, SisUser $sisUser): array
    {
        $diff = [];
        foreach ($this->oldGetters as $key => $oldGetter) {
            $newGetter = $this->newGetters[$key];
            $old = $user->$oldGetter();
            $new = $sisUser->$newGetter();
            if ($old !== $new) {
                $diff[$key] = ['old' => $old, 'new' => $new];
            }
        }

        return $diff;
    }

    /**
     * Update the user entity with data from sis user record.
     * @param User $user (from ReCodEx) to be updated
     * @param SisUser $sisUser (new data)
     * @return bool true if any field was updated, false if they were already synced
     */
    public function update(User $user, SisUser $sisUser): bool
    {
        $updated = false;
        foreach ($this->oldGetters as $key => $oldGetter) {
            $newGetter = $this->newGetters[$key];
            $old = $user->$oldGetter();
            $new = $sisUser->$newGetter();
            if ($old !== $new) {
                $setter = $this->oldSetters[$key];
                $user->$setter($new);
                $updated = true;
            }
        }

        return $updated;
    }
}
