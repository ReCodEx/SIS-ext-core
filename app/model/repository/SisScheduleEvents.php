<?php

namespace App\Model\Repository;

use App\Model\Entity\SisScheduleEvent;
use App\Model\Entity\SisTerm;
use App\Model\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends BaseRepository<SisScheduleEvent>
 */
class SisScheduleEvents extends BaseRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, SisScheduleEvent::class);
    }

    public function findBySisId(string $sisId): ?SisScheduleEvent
    {
        return $this->findOneBy(['sisId' => $sisId]);
    }

    /**
     * Get all scheduling events for a specific user (optionally filter by term and affiliation).
     * @param User $user
     * @param SisTerm|null $term if given, only events of particular term are returned
     * @param string|string[]|null $affiliation if given, only events with particular affiliation(s) are returned
     * @return SisScheduleEvent[]
     */
    public function allEventsOfUser(User $user, ?SisTerm $term = null, mixed $affiliation = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->innerJoin('e.affiliations', 'a')
            ->where('a.user = :user')
            ->setParameter('user', $user->getId());

        if ($term) {
            $qb->andWhere('e.term = :term')
                ->setParameter('term', $term->getId());
        }

        if ($affiliation) {
            if (is_string($affiliation)) {
                $qb->andWhere('a.type = :affiliation')
                    ->setParameter('affiliation', $affiliation);
            } elseif (is_array($affiliation)) {
                $qb->andWhere('a.type IN (:affiliation)')
                    ->setParameter('affiliation', $affiliation);
            }
        }

        return $qb->getQuery()->getResult();
    }
}
