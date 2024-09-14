<?php

namespace App\Model\Repository;

use App\Model\Entity\SisTerm;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends BaseRepository<SisTerm>
 */
class SisTerms extends BaseRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, SisTerm::class);
    }

    /**
     * @param int $year
     * @param int $term
     * @return SisTerm|null
     */
    public function isValid($year, $term): ?SisTerm
    {
        return $this->findOneBy(
            [
                "year" => $year,
                "term" => $term
            ]
        );
    }

    /**
     * @return SisTerm[]
     */
    public function findAll(): array
    {
        return $this->repository->findBy(
            [],
            [
                "year" => "DESC",
                "term" => "DESC",
            ]
        );
    }
}
