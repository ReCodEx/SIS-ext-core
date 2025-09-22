<?php

namespace App\Security\ACL;

interface IGroupPermissions
{
    public function canViewStudent(): bool;

    public function canViewTeacher(): bool;

    public function canEditRawAttributes(): bool;
}
