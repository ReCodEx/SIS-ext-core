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

    public function getAffiliation(SisScheduleEvent $event, User $user): ?SisAffiliation
    {
        return $this->findOneBy([
            'event' => $event,
            'user' => $user,
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
        $sub = $this->em->createQueryBuilder()->select("e")->from(SisScheduleEvent::class, "e");
        $sub->where("e.term = :term")->andWhere("e = a.event");

        $qb = $this->createQueryBuilder('a')->delete()
            ->where('a.user = :user')->setParameter('user', $user->getId());
        $qb->andWhere($qb->expr()->exists($sub->getDQL()))->setParameter('term', $term->getId());

        $qb->getQuery()->execute();
    }
}
