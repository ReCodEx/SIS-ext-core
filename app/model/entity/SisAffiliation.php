<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table]
#[ORM\UniqueConstraint(columns: ['user_id', 'event_id'])]
#[ORM\Entity]
class SisAffiliation
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    protected $user;

    #[ORM\ManyToOne(targetEntity: SisScheduleEvent::class, inversedBy: 'affiliations')]
    protected $event;

    public const TYPE_STUDENT = 'student';
    public const TYPE_TEACHER = 'teacher';
    public const TYPE_GUARANTOR = 'guarantor';

    /**
     * One of TYPE_* values (student, teacher...)
     */
    #[ORM\Column(type: 'string')]
    protected $type;

    public function __construct(
        User $user,
        SisScheduleEvent $event,
        string $type,
    ) {
        $this->user = $user;
        $this->event = $event;
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }
}
