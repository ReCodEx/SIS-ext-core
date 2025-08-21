<?php

namespace App\Helpers;

use App\Model\Entity\SisAffiliation;
use App\Model\Entity\SisCourse;
use App\Model\Entity\SisScheduleEvent;
use App\Model\Entity\SisTerm;
use App\Model\Repository\SisAffiliations;
use App\Model\Repository\SisCourses;
use App\Model\Repository\SisScheduleEvents;
use App\Model\Repository\Users;
use DateTime;
use Exception;
use JsonSerializable;

/**
 * Warper that parses and provides access to individual properties of the course record.
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

    private $firstWeek;

    private static $typeMap = [
        "P" => SisScheduleEvent::TYPE_LECTURE,
        "X" => SisScheduleEvent::TYPE_LABS,
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
        $result->time = ($data["time"] !== null) ? intval($data["time"]) : null;
        $result->room = $data["room"];
        $result->fortnightly = (bool)$data["fortnight"];
        $result->firstWeek = intval($data["firstweek"]);
        $result->type = array_key_exists($data["type"], self::$typeMap) ? self::$typeMap[$data["type"]]
            : SisScheduleEvent::TYPE_UNKNOWN;

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
     * @return string SIS course identification
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
     * @return int the first logical week when the lecture starts (usually 1 = the first week of the semester)
     */
    public function getFirstWeek(): int
    {
        return $this->firstWeek;
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
            'firstWeek' => $this->firstWeek,
            'type' => $this->type
        ];
    }

    public function updateLocalCourseAndAffiliations(
        SisCourses $courses,
        SisScheduleEvents $events,
        SisAffiliations $affiliations,
        Users $users,
        SisTerm $term,
    ): void {
        // update/create the course itself
        $course = $courses->findByCode($this->courseId);
        if ($course) {
            $course->setCaptionCs($this->getCaption('cs'));
            $course->setCaptionEn($this->getCaption('en'));
            $course->updatedNow();
        } else {
            $course = new SisCourse($this->courseId, $this->getCaption('cs'), $this->getCaption('en'));
        }
        $courses->persist($course);

        // update/create the scheduling event
        $event = $events->findBySisId($this->getCode());
        if ($event) {
            // sanity check
            if ($event->getCourse()->getId() !== $course->getId()) {
                throw new Exception("Event course ID does not match the course ID in the record.");
            }
            if ($event->getTerm()->getId() !== $term->getId()) {
                throw new Exception("Event term does not match the term in the record.");
            }
            $event->setType($this->type);
            $event->updatedNow();
        } else {
            $event = new SisScheduleEvent(
                $this->getCode(),
                $term,
                $course,
                $this->type,
            );
        }
        $event->setSchedule($this->dayOfWeek, $this->firstWeek, $this->time, 90, $this->room, $this->fortnightly);
        $events->persist($event);

        // update event affiliations for the user (if exists)
        $user = $users->getBySisId($this->sisUserId);
        if ($user) {
            $affiliation = $affiliations->getAffiliation($event, $user, $term);
            if ($affiliation) {
                // update existing affiliation
                $affiliation->setType($this->affiliation);
            } else {
                // create new affiliation
                $affiliation = new SisAffiliation($user, $event, $term, $this->affiliation);
            }
            $affiliations->persist($affiliation, false);

            $user->setSisEventsLoaded();
            $users->persist($user);
        }
    }
}
