<?php

namespace App\Security\ACL;

use App\Model\Entity\SisTerm;

interface ITermPermissions
{
    public function canList(): bool;

    public function canCreate(): bool;

    public function canViewDetail(SisTerm $term): bool;

    public function canUpdate(SisTerm $term): bool;

    public function canRemove(SisTerm $term): bool;
}
