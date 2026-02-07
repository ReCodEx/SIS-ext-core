<?php

namespace App\Security\ACL;

interface IGroupPermissions
{
    public function canViewAll(): bool;

    public function canViewStudent(): bool;

    public function canViewTeacher(): bool;

    public function canEditRawAttributes(): bool;

    public function canCreateTermGroup(): bool;

    public function canSetArchived(): bool;
}
