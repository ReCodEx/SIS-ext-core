<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use DateTimeInterface;
use JsonSerializable;
use InvalidArgumentException;

#[ORM\Entity]
class SisTerm implements JsonSerializable
{
    use CreatableEntity;
    use UpdatableEntity;

    /**
     * @var \Ramsey\Uuid\UuidInterface
     */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: \Ramsey\Uuid\Doctrine\UuidGenerator::class)]
    protected $id;

    /**
     * Calendar year in which the academic year begins.
     */
    #[ORM\Column(type: 'integer')]
    protected $year;

    /**
     * 1 = winter term, 2 = summer term
     */
    #[ORM\Column(type: 'integer')]
    protected $term;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected $beginning = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected $end = null;

    /**
     * From when the term should be advertised to students (students can enroll groups).
     */
    #[ORM\Column(type: 'datetime')]
    protected $studentsFrom;

    /**
     * Until when the term should be advertised to students (students can enroll groups).
     */
    #[ORM\Column(type: 'datetime')]
    protected $studentsUntil;

    /**
     * From when the term should be advertised to teachers (teachers can create groups).
     */
    #[ORM\Column(type: 'datetime')]
    protected $teachersFrom;

    /**
     * Until when the term should be advertised to teachers (teachers can create groups).
     */
    #[ORM\Column(type: 'datetime')]
    protected $teachersUntil;

    /**
     * After this date, semi-automated group archiving will be suggested.
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    protected $archiveAfter = null;

    public function __construct(
        int $year,
        int $term,
        ?DateTimeInterface $beginning = null,
        ?DateTimeInterface $end = null
    ) {
        $this->year = $year;
        $this->term = $term;
        $this->beginning = $beginning;
        $this->end = $end;
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    /**
     * Should courses in the term be advertised to the students for group enrollment?
     * @param DateTime $now
     * @return bool
     */
    public function isAdvertisedForStudents(DateTimeInterface $now = new DateTime()): bool
    {
        return $now >= $this->studentsFrom && $now <= $this->studentsUntil;
    }

    /**
     * Should courses in the term be advertised to the students for group enrollment?
     * @param DateTime $now
     * @return bool
     */
    public function isAdvertisedForTeachers(DateTimeInterface $now = new DateTime()): bool
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

    public function getYearTermKey(): string
    {
        return sprintf("%d-%d", $this->year, $this->term);
    }

    public function getBeginning(): ?DateTime
    {
        return $this->beginning;
    }

    public function setBeginning(?DateTimeInterface $beginning): void
    {
        $this->beginning = $beginning;
    }

    public function getEnd(): ?DateTime
    {
        return $this->end;
    }

    public function setEnd(?DateTimeInterface $end): void
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

    public function setStudentsAdvertisement(DateTimeInterface $from, DateTimeInterface $until): void
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

    public function setTeachersAdvertisement(DateTimeInterface $from, DateTimeInterface $until): void
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

    public function setArchiveAfter(?DateTimeInterface $archiveAfter): void
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
