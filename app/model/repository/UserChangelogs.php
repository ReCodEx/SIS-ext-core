<?php

namespace App\Model\Repository;

use App\Model\Entity\UserChangelog;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends BaseRepository<UserChangelog>
 */
class UserChangelogs extends BaseRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, UserChangelog::class);
    }
}
