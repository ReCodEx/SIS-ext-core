<?php

namespace App\Model\Repository;

use App\Model\Entity\SisAffiliation;
use App\Model\Entity\SisScheduleEvent;
use App\Model\Entity\SisTerm;
use App\Model\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends BaseRepository<SisAffiliation>
 */
class SisAffiliations extends BaseRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, SisAffiliation::class);
    }

    public function getAffiliation(SisScheduleEvent $event, User $user, SisTerm $term): ?SisAffiliation
    {
        return $this->findOneBy([
            'event' => $event,
            'user' => $user,
            'term' => $term,
        ]);
    }
}
