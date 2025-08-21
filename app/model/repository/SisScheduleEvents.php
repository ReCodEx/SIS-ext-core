<?php

namespace App\Model\Repository;

use App\Model\Entity\SisScheduleEvent;
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
}
