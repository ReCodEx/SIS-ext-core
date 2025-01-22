<?php

namespace App\Helpers;

use DateTime;
use Exception;
use JsonSerializable;

/**
 * Wraper that parses and provides access to individual properties of the course record.
 */
class SisCourseRecord implements JsonSerializable
{
    private static $languages = ['cs', 'en'];

    private $code;

    private $courseId;

    private $type;

    private $affiliation;

    private $captions;

    private $annotations;

    private $year;

    private $term;

    private $sisUserId;

    private $dayOfWeek;

    private $time;

    private $room;

    private $fortnightly;

    private $oddWeeks;

    private static $typeMap = [
        "P" => "lecture",
        "X" => "lab"
    ];

    /**
     * @param string $sisUserId
     * @param array $data
     * @return SisCourseRecord
     */
    public static function fromArray($sisUserId, $data)
    {
        $result = new SisCourseRecord();
        $result->sisUserId = $sisUserId;

        $result->code = $data["id"];
        $result->courseId = $data["course"];
        $result->affiliation = $data["affiliation"];
        $result->year = $data["year"];
        $result->term = $data["semester"];
        $result->dayOfWeek = $data["day_of_week"] !== null ? intval($data["day_of_week"]) - 1 : null;
        if ($data["time"] !== null) {
            $minutes = intval($data["time"]);
            $result->time = (new DateTime())
                ->setTime(floor($minutes / 60), $minutes % 60)
                ->format("H:i");
        } else {
            $result->time = null;
        }
        $result->room = $data["room"];
        $result->fortnightly = (bool)$data["fortnight"];
        $result->oddWeeks = $data["firstweek"] == 1;
        $result->type = array_key_exists($data["type"], self::$typeMap) ? self::$typeMap[$data["type"]] : "unknown";

        foreach (self::$languages as $language) {
            $result->captions[$language] = $data["caption_" . $language];
            $result->annotations[$language] = !empty($data["annotation_" . $language])
                ? $data["annotation_" . $language] : '';
        }

        return $result;
    }

    /**
     * @return string UKCO
     */
    public function getSisUserId(): string
    {
        return $this->sisUserId;
    }

    /**
     * @param string $lang -uage identifier (cs, en)
     * @return string caption of the course in selected language
     */
    public function getCaption(string $lang): string
    {
        if (!array_key_exists($lang, $this->captions)) {
            throw new Exception("Caption for language '$lang' does not exist");
        }

        return $this->captions[$lang];
    }

    /**
     * @param string $lang -uage identifier (cs, en)
     * @return string annotation in selected language
     */
    public function getAnnotation(string $lang): string
    {
        if (!array_key_exists($lang, $this->annotations)) {
            throw new Exception("Annotation for language '$lang' does not exist'");
        }

        return $this->annotations[$lang];
    }

    /**
     * @return string term identification as <year>-<semester>
     */
    public function getTermIdentifier(): string
    {
        return sprintf("%s-%s", $this->year, $this->term);
    }

    /**
     * @return bool true if the user is student of the course
     */
    public function isOwnerStudent(): bool
    {
        return $this->affiliation === "student";
    }

    /**
     * @return bool true if the user is either a teacher of guarantor of the course
     */
    public function isOwnerSupervisor(): bool
    {
        return $this->affiliation === "teacher" || $this->affiliation === "guarantor";
    }

    /**
     * @return string code (ID) of the scheduling event
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return string SIS course identificator
     */
    public function getCourseId(): string
    {
        return $this->courseId;
    }

    /**
     * @return int|null day of the week; 0 = Sunday, 1 = Monday, ...
     */
    public function getDayOfWeek(): ?int
    {
        return $this->dayOfWeek;
    }

    /**
     * @return int|null number of minutes from midnight
     */
    public function getTime(): ?int
    {
        return $this->time;
    }

    /**
     * @return string room name
     */
    public function getRoom(): string
    {
        return $this->room;
    }

    /**
     * @return bool true if the course is held once every 2 weeks
     */
    public function isFortnightly(): bool
    {
        return $this->fortnightly;
    }

    /**
     * Only valid if the courses are held once every two weeks.
     * @return bool true if the course run in the odd weeks (starting in week 1)
     */
    public function getOddWeeks(): bool
    {
        return $this->oddWeeks;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'code' => $this->code,
            'courseId' => $this->courseId,
            'captions' => $this->captions,
            'annotations' => $this->annotations,
            'dayOfWeek' => $this->dayOfWeek,
            'time' => $this->time,
            'room' => $this->room,
            'fortnightly' => $this->fortnightly,
            'oddWeeks' => $this->oddWeeks,
            'type' => $this->type
        ];
    }
}
