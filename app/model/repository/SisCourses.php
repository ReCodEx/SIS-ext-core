<?php

namespace App\Model\Repository;

use App\Model\Entity\SisCourse;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends BaseRepository<SisCourse>
 */
class SisCourses extends BaseRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, SisCourse::class);
    }

    public function findByCode(string $code): ?SisCourse
    {
        return $this->findOneBy(['code' => $code]);
    }
}
