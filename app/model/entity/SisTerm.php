<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use JsonSerializable;
use InvalidArgumentException;

/**
 * @ORM\Entity
 * A record represening one semester with all important dates, especially the ranges from-until
 * it is advertised to students/teachers. This needs to be set by admin to correctly handle SIS operations.
 */
class SisTerm implements JsonSerializable
{
    use CreateableEntity;
    use UpdateableEntity;

    /**
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=\Ramsey\Uuid\Doctrine\UuidGenerator::class)
     * @var \Ramsey\Uuid\UuidInterface
     */
    protected $id;

    /**
     * @ORM\Column(type="integer")
     * Calendar year in which the academic year begins.
     */
    protected $year;

    /**
     * @ORM\Column(type="integer")
     * 1 = winter term, 2 = summer term
     */
    protected $term;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $beginning = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $end = null;

    /**
     * @ORM\Column(type="datetime")
     * From when the term should be advertised to students (students can enroll groups).
     */
    protected $studentsFrom;

    /**
     * @ORM\Column(type="datetime")
     * Until when the term should be advertised to students (students can enroll groups).
     */
    protected $studentsUntil;

    /**
     * @ORM\Column(type="datetime")
     * From when the term should be advertised to teachers (teachers can create groups).
     */
    protected $teachersFrom;

    /**
     * @ORM\Column(type="datetime")
     * Until when the term should be advertised to teachers (teachers can create groups).
     */
    protected $teachersUntil;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * After this date, semi-automated group archiving will be suggested.
     */
    protected $archiveAfter = null;

    public function __construct(
        int $year,
        int $term,
        DateTime $studentsFrom,
        DateTime $studentsUntil,
        DateTime $teachersFrom,
        DateTime $teachersUntil
    ) {
        $this->year = $year;
        $this->term = $term;
        $this->setStudentsAdvertisement($studentsFrom, $studentsUntil);
        $this->setTeachersAdvertisement($teachersFrom, $teachersUntil);
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    /**
     * Should courses in the term be advertised to the students for group enrollment?
     * @param DateTime $now
     * @return bool
     */
    public function isAdvertisedForStudents(DateTime $now = new DateTime()): bool
    {
        return $now >= $this->studentsFrom && $now <= $this->studentsUntil;
    }

    /**
     * Should courses in the term be advertised to the students for group enrollment?
     * @param DateTime $now
     * @return bool
     */
    public function isAdvertisedForTeachers(DateTime $now = new DateTime()): bool
    {
        return $now >= $this->teachersFrom && $now <= $this->teachersUntil;
    }

    /*
     * Accessors
     */

    public function getId(): ?string
    {
        return $this->id === null ? null : (string)$this->id;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getTerm(): int
    {
        return $this->term;
    }

    public function getBeginning(): ?DateTime
    {
        return $this->beginning;
    }

    public function setBeginning(?DateTime $beginning): void
    {
        $this->beginning = $beginning;
    }

    public function getEnd(): ?DateTime
    {
        return $this->end;
    }

    public function setEnd(?DateTime $end): void
    {
        $this->end = $end;
    }

    public function getStudentsFrom(): DateTime
    {
        return $this->studentsFrom;
    }

    public function getStudentsUntil(): DateTime
    {
        return $this->studentsUntil;
    }

    public function setStudentsAdvertisement(DateTime $from, DateTime $until): void
    {
        if ($from > $until) {
            throw new InvalidArgumentException(
                "In the date range from-until, the `form` date must be before `until` date."
            );
        }
        $this->studentsFrom = $from;
        $this->studentsUntil = $until;
    }

    public function getTeachersFrom(): DateTime
    {
        return $this->teachersFrom;
    }

    public function getTeachersUntil(): DateTime
    {
        return $this->teachersUntil;
    }

    public function setTeachersAdvertisement(DateTime $from, DateTime $until): void
    {
        if ($from > $until) {
            throw new InvalidArgumentException(
                "In the date range from-until, the `form` date must be before `until` date."
            );
        }
        $this->teachersFrom = $from;
        $this->teachersUntil = $until;
    }

    public function getArchiveAfter(): ?DateTime
    {
        return $this->archiveAfter;
    }

    public function setArchiveAfter(?DateTime $archiveAfter): void
    {
        $this->archiveAfter = $archiveAfter;
    }

    // JSON interface

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->getId(),
            'year' => $this->year,
            'term' => $this->term,
            'beginning' => $this->getBeginning()?->getTimestamp(),
            'end' => $this->getEnd()?->getTimestamp(),
            'studentsFrom' => $this->getStudentsFrom()->getTimestamp(),
            'studentsUntil' => $this->getStudentsUntil()->getTimestamp(),
            'teachersFrom' => $this->getTeachersFrom()->getTimestamp(),
            'teachersUntil' => $this->getTeachersUntil()->getTimestamp(),
            'archiveAfter' => $this->getArchiveAfter()?->getTimestamp(),
            'createdAt' => $this->getCreatedAt()->getTimestamp(),
            'updatedAt' => $this->getUpdatedAt()->getTimestamp(),
        ];
    }
}
