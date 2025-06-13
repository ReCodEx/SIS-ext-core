<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use DateTime;
use JsonSerializable;

/**
 * @ORM\Entity
 * Record holding information about one course from SIS.
 * This is merely a cache for SIS data.
 */
class SisCourse implements JsonSerializable
{
    use CreatableEntity;
    use UpdatableEntity;

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
     * Identification (code) of the course in SIS.
     */
    protected $code;

    /**
     * @ORM\Column(type="string")
     * Name of the course in Czech.
     */
    protected $captionCs;

    /**
     * @ORM\Column(type="string")
     * Name of the course in English.
     */
    protected $captionEn;

    /**
     * @ORM\OneToMany(targetEntity="SisScheduleEvent", mappedBy="course")
     */
    protected $events;


    public function __construct(string $code, string $captionCs, string $captionEn)
    {
        $this->code = $code;
        $this->captionCs = $captionCs;
        $this->captionEn = $captionEn;
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
        $this->events = new ArrayCollection();
    }

    /*
     * Accessors
     */

    public function getId(): ?string
    {
        return $this->id === null ? null : (string)$this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getCaptionCs(): string
    {
        return $this->captionCs;
    }

    public function setCaptionCs(string $caption): void
    {
        $this->captionCs = $caption;
    }

    public function getCaptionEn(): string
    {
        return $this->captionEn;
    }

    public function setCaptionEn(string $caption): void
    {
        $this->captionEn = $caption;
    }

    public function getCaption(string $lang): string
    {
        return ($lang === 'cs') ? $this->captionCs : $this->captionEn;
    }

    // JSON interface

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->getId(),
            'code' => $this->getCode(),
            'caption_cs' => $this->getCaptionCs(),
            'caption_en' => $this->getCaptionEn(),
            'createdAt' => $this->getCreatedAt()->getTimestamp(),
            'updatedAt' => $this->getUpdatedAt()->getTimestamp(),
        ];
    }
}
