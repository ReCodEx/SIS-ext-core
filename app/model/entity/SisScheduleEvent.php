<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use DateTime;
use JsonSerializable;

#[ORM\Entity]
class SisScheduleEvent implements JsonSerializable
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
     * Code of the scheduling event (ticket) denoted in SIS as 'GL'.
     */
    #[ORM\Column(type: 'string', unique: true)]
    protected $sisId;

    #[ORM\ManyToOne(targetEntity: SisTerm::class)]
    protected $term;

    #[ORM\ManyToOne(targetEntity: SisCourse::class, inversedBy: 'events')]
    protected $course;

    public const TYPE_LECTURE = 'lecture';
    public const TYPE_LABS = 'labs';
    public const TYPE_UNKNOWN = '?';

    /**
     * One of TYPE_* values (lecture, labs, ...)
     */
    #[ORM\Column(type: 'string')]
    protected $type;

    /**
     * Day of the week (0=Sunday, 1=Monday...6=Saturday)
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    protected $dayOfWeek;

    /**
     * When the lecture starts (logical weeks of the semester).
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    protected $firstWeek;

    /**
     * Time of the day when the event starts as minutes from midnight.
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    protected $time;

    /**
     * Length of the event in minutes.
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    protected $length;

    /**
     * Where the event is located.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected $room;

    /**
     * If true, the event takes place once every two weeks (false = regular weekly scheduling).
     */
    #[ORM\Column(type: 'boolean')]
    protected $fortnight = false;

    #[ORM\OneToMany(targetEntity: SisAffiliation::class, mappedBy: 'event')]
    protected $affiliations;

    public function __construct(
        string $sisId,
        SisTerm $term,
        SisCourse $course,
        string $type,
    ) {
        $this->sisId = $sisId;
        $this->term = $term;
        $this->course = $course;
        $this->type = $type;
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
        $this->affiliations = new ArrayCollection();
    }

    /*
     * Accessors
     */

    public function getId(): ?string
    {
        return $this->id === null ? null : (string)$this->id;
    }

    public function getSisId(): string
    {
        return $this->sisId;
    }

    public function getTerm(): SisTerm
    {
        return $this->term;
    }

    public function getCourse(): SisCourse
    {
        return $this->course;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getDayOfWeek(): ?int
    {
        return $this->dayOfWeek;
    }

    public function getFirstWeek(): ?int
    {
        return $this->firstWeek;
    }

    public function getTime(): ?int
    {
        return $this->time;
    }

    public function getLength(): ?int
    {
        return $this->length;
    }

    public function setLength(?int $length): void
    {
        $this->length = $length;
    }

    public function getRoom(): ?string
    {
        return $this->room;
    }

    public function getFortnight(): bool
    {
        return $this->fortnight;
    }

    public function setSchedule(
        ?int $dayOfWeek,
        ?int $firstWeek,
        ?int $time,
        ?int $length,
        ?string $room,
        bool $fortnight = false
    ): void {
        $this->dayOfWeek = $dayOfWeek;
        $this->firstWeek = $firstWeek;
        $this->time = $time;
        $this->length = $length;
        $this->room = $room;
        $this->fortnight = $fortnight;
    }

    // JSON interface

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->getId(),
            'year' => $this->getTerm()->getYear(),
            'term' => $this->getTerm()->getTerm(),
            'course' => $this->getCourse()->jsonSerialize(),
            'sisId' => $this->getSisId(),
            'type' => $this->getType(),
            'dayOfWeek' => $this->getDayOfWeek(),
            'firstWeek' => $this->getFirstWeek(),
            'time' => $this->getTime(),
            'length' => $this->getLength(),
            'room' => $this->getRoom(),
            'fortnight' => $this->getFortnight(),
            'createdAt' => $this->getCreatedAt()->getTimestamp(),
            'updatedAt' => $this->getUpdatedAt()->getTimestamp(),
        ];
    }
}
