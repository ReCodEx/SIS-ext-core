<?php

namespace App\Security\Policies;

use App\Model\Entity\SisScheduleEvent;
use App\Model\Entity\SisAffiliation;
use App\Model\Repository\SisAffiliations;
use App\Security\Identity;

class EventPermissionPolicy implements IPermissionPolicy
{
    /**
     * @var SisAffiliations
     */
    private $affiliations;

    public function __construct(SisAffiliations $affiliations)
    {
        $this->affiliations = $affiliations;
    }

    public function getAssociatedClass()
    {
        return SisScheduleEvent::class;
    }

    public function isUserEnrolledFor(Identity $identity, SisScheduleEvent $event): bool
    {
        $currentUser = $identity->getUserData();
        if (!$currentUser) {
            return false;
        }

        $affiliation = $this->affiliations->getAffiliation($event, $currentUser);
        return $affiliation !== null && $affiliation->getType() === SisAffiliation::TYPE_STUDENT;
    }

    public function isUserTeacherOf(Identity $identity, SisScheduleEvent $event): bool
    {
        $currentUser = $identity->getUserData();
        if (!$currentUser) {
            return false;
        }

        $affiliation = $this->affiliations->getAffiliation($event, $currentUser);
        return $affiliation !== null && $affiliation->getType() !== SisAffiliation::TYPE_STUDENT;
        // (affiliation exists and it's anything but student)
    }
}
