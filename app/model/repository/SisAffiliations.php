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

    /**
     * Clear all affiliations for a user in a specific term.
     * This is necessary before an update, to avoid lingering affiliations when user changes
     * course/event enrollment in SIS.
     * @param User $user whose affiliations are removed
     * @param SisTerm $term only affiliations to sis events in this term are cleared
     */
    public function clearAffiliations(User $user, SisTerm $term): void
    {
        $this->createQueryBuilder('a')
            ->delete()
            ->join('a.event', 'e')
            ->where('a.user = :user')
            ->andWhere('e.term = :term')
            ->setParameter('user', $user->getId())
            ->setParameter('term', $term->getId())
            ->getQuery()
            ->execute();
    }
}
