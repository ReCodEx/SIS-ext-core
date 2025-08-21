<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(columns={"user_id", "event_id"})})
 * Holding affiliations between users and scheduling events (student/teacher/guarantor).
 * This is merely a cache for SIS data.
 */
class SisAffiliation
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="SisScheduleEvent")
     */
    protected $event;

    /**
     * @ORM\ManyToOne(targetEntity="SisTerm")
     */
    protected $term;

    public const TYPE_STUDENT = 'student';
    public const TYPE_TEACHER = 'teacher';
    public const TYPE_GUARANTOR = 'guarantor';

    /**
     * @ORM\Column(type="string")
     * One of TYPE_* values (student, teacher...)
     */
    protected $type;

    public function __construct(
        User $user,
        SisScheduleEvent $event,
        SisTerm $term,
        string $type,
    ) {
        $this->user = $user;
        $this->event = $event;
        $this->term = $term;
        $this->type = $type;
    }

    /*
     * Accessors
     */

    public function getUser(): User
    {
        return $this->user;
    }

    public function getEvent(): SisScheduleEvent
    {
        return $this->event;
    }

    public function getTerm(): SisTerm
    {
        return $this->term;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }
}
