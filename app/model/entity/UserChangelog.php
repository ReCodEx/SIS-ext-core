<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use DateTime;

/**
 * @ORM\Entity
 * Records about changes made when synchronizing user data.
 */
class UserChangelog implements JsonSerializable
{
    use CreateableEntity;

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
     * @ORM\Column(type="text", length=65535)
     * JSON encoded diff (log of changes).
     */
    protected $diff = null;


    public function __construct(
        User $user,
        array $diff,
    ) {
        $this->user = $user;
        $this->diff = json_encode($diff);
        $this->createdAt = new DateTime();
    }

    /*
     * Accessors
     */

    public function getId(): string
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getDiff(): array
    {
        return json_decode($this->diff, true);
    }


    // JSON interface

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->getId(),
            'user' => $this->getUser()->getId(),
            'diff' => $this->getDiff(),
        ];
    }
}
