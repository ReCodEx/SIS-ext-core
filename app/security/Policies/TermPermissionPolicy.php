<?php

namespace App\Security\Policies;

use App\Model\Entity\SisTerm;
use App\Security\Identity;
use DateTime;

class TermPermissionPolicy implements IPermissionPolicy
{
    public function getAssociatedClass()
    {
        return SisTerm::class;
    }

    public function isVisibleToStudents(Identity $identity, SisTerm $term): bool
    {
        $now = new DateTime();
        return $term->getStudentsFrom() <= $now && $now <= $term->getStudentsUntil();
    }

    public function isVisibleToTeachers(Identity $identity, SisTerm $term): bool
    {
        $now = new DateTime();
        return $term->getTeachersFrom() <= $now && $now <= $term->getTeachersUntil();
    }
}
