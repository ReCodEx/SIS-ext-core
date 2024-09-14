<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use JsonSerializable;

/**
 * @ORM\Entity
 * A record represening one
 */
class SisScheduleEvent implements JsonSerializable
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
     * @ORM\Column(type="string", unique=true)
     * Code of the scheduling event (ticket) denoted in SIS as 'GL'.
     */
    protected $sisId;

    /**
     * @ORM\ManyToOne(targetEntity="SisTerm")
     */
    protected $term;

    /**
     * @ORM\ManyToOne(targetEntity="SisCourse", inversedBy="events")
     */
    protected $course;

    public const TYPE_LECTURE = 'lecture';
    public const TYPE_LABS = 'labs';

    /**
     * @ORM\Column(type="string")
     * One of TYPE_* values (lecture, labs, ...)
     */
    protected $type;

    /**
     * @ORM\Column(type="int")
     * Day of the week (0=Sunday, 1=Monday...6=Saturday)
     */
    protected $dayOfWeek;

    /**
     * @ORM\Column(type="int")
     * When the lecture starts (logical weeks of the semester).
     */
    protected $firstWeek;

    /**
     * @ORM\Column(type="int")
     * Time of the day when the event starts as minutes from midnight.
     */
    protected $time;

    /**
     * @ORM\Column(type="int")
     * Length of the event in minutes.
     */
    protected $length;

    /**
     * @ORM\Column(type="string")
     * Where the event is located.
     */
    protected $room;

    /**
     * @ORM\Column(type="boolean")
     * If true, the event takes place once every two weeks (false = regular weekly scheduling).
     */
    protected $fortnight = false;

    public function __construct(
        string $sisId,
        SisTerm $term,
        SisCourse $course,
        string $type,
        int $dayOfWeek,
        int $firstWeek,
        int $time,
        int $length,
        string $room,
        bool $fortnight = false
    ) {
        $this->sisId = $sisId;
        $this->term = $term;
        $this->course = $course;
        $this->type = $type;
        $this->dayOfWeek = $dayOfWeek;
        $this->firstWeek = $firstWeek;
        $this->time = $time;
        $this->length = $length;
        $this->room = $room;
        $this->fortnight = $fortnight;
        $this->createdAt = new DateTime();
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

    public function getDayOfWeek(): int
    {
        return $this->dayOfWeek;
    }

    public function getFirstWeek(): int
    {
        return $this->firstWeek;
    }

    public function getTime(): int
    {
        return $this->time;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function setLength(int $length): void
    {
        $this->length = $length;
    }

    public function getRoom(): string
    {
        return $this->room;
    }

    public function getFortnight(): bool
    {
        return $this->fortnight;
    }

    public function setSchedule(
        int $dayOfWeek,
        int $firstWeek,
        int $time,
        int $length,
        string $room,
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
            'term' => $this->getTerm()->jsonSerialize(),
            'course' => $this->getCourse()->jsonSerialize(),
            'sisId' => $this->getSisId(),
            'type' => $this->getType(),
            'dayOfWeek' => $this->getDayOfWeek(),
            'firstWeek' => $this->getFirstWeek(),
            'time' => $this->getTime(),
            'length' => $this->getLength(),
            'room' => $this->getRoom(),
            'fortnight' => $this->getFortnight(),
        ];
    }
}
