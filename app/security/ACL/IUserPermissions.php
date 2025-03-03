<?php

namespace App\Security\ACL;

use App\Model\Entity\User;

interface IUserPermissions
{
    public function canViewDetail(User $user): bool;

    public function canFetchSis(User $user): bool;

    public function canSyncSis(User $user): bool;
}
