<?php

namespace App\Model\Repository;

use App\Model\Entity\SisUser;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends BaseRepository<SisUser>
 */
class SisUsers extends BaseRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, SisUser::class);
    }
}
