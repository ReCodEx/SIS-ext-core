<?php

namespace App\Model\Repository;

use App\Model\Entity\SisTerm;
use Doctrine\ORM\EntityManagerInterface;
use DateTime;

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

    public function findAllActive(DateTime $now = new DateTime()): array
    {
        return array_filter($this->findAll(), function (SisTerm $term) use ($now) {
            return ($term->getStudentsFrom() <= $now && $now <= $term->getStudentsUntil())
                || ($term->getTeachersFrom() <= $now && $now <= $term->getTeachersUntil());
        });
    }
}
