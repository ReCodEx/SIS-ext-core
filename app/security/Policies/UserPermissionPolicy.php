<?php

namespace App\Security\Policies;

use App\Model\Entity\User;
use App\Security\Identity;
use App\Security\Roles;

class UserPermissionPolicy implements IPermissionPolicy
{
    public function getAssociatedClass()
    {
        return User::class;
    }

    public function isSameUser(Identity $identity, User $user): bool
    {
        $currentUser = $identity->getUserData();
        return $currentUser !== null && $currentUser->getId() === $user->getId();
    }

    public function isSupervisor(Identity $identity, User $user)
    {
        $currentUser = $identity->getUserData();
        if (!$currentUser) {
            return false;
        }

        return $user->getRole() === Roles::SUPERVISOR_ROLE;
    }
}
