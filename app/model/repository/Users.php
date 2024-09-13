<?php

namespace App\Model\Repository;

use App\Model\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends BaseSoftDeleteRepository<User>
 */
class Users extends BaseSoftDeleteRepository
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

    /**
     * @param string ...$roles
     * @return User[]
     */
    public function findByRoles(string ...$roles): array
    {
        return $this->findBy(["role" => $roles]);
    }

    /**
     * Find all users who have not authenticated to the system for some time.
     * @param DateTime|null $before Only users with last activity before given date
     *                              (i.e., not active after given date) are returned.
     * @param bool|null $allowed if not null, only users with particular isAllowed state are returned
     * @param string[] $roles only users of these roles are listed
     * @return User[]
     */
    public function findByLastAuthentication(?DateTime $before, ?bool $allowed = null, array $roles = []): array
    {
        $qb = $this->createQueryBuilder('u'); // takes care of softdelete cases
        if ($before) {
            $qb->andWhere(
                'u.createdAt <= :before AND (u.lastAuthenticationAt <= :before OR u.lastAuthenticationAt IS NULL)'
            )->setParameter('before', $before);
        }
        if ($allowed !== null) {
            $qb->andWhere('u.isAllowed = :allowed')->setParameter('allowed', $allowed);
        }
        if ($roles) {
            $qb->andWhere('u.role IN (:roles)')->setParameter('roles', $roles);
        }
        return $qb->getQuery()->getResult();
    }
}
