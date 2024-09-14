<?php

namespace App\Model\Repository;

use App\Model\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends BaseRepository<User>
 */
class Users extends BaseRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, User::class);
    }

    public function getByEmail(string $email): ?User
    {
        return $this->findOneBy(["email" => $email]);
    }

    /**
     * Search users firstnames and surnames based on given string.
     * @param string|null $search
     * @return User[]
     */
    public function searchByNames(?string $search): array
    {
        return $this->searchBy(["firstName", "lastName"], $search);
    }
}
