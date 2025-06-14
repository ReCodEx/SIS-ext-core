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
     * Find a term by year and term number.
     * @param int $year
     * @param int $term 1 for winter term, 2 for summer term
     * @return SisTerm|null null if not found
     */
    public function findTerm($year, $term): ?SisTerm
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
