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
use Exception;
use JsonSerializable;

/**
 * Warper that parses and provides access to individual properties of the course record.
 */
class SisCourseRecord implements JsonSerializable
{
    private static $languages = ['cs', 'en'];

    /**
     * @var string
     * Identifier of the scheduling event (known in SIS as `GL`)
     */
    private $code;

    /**
     * @var string
     * Identifier of the course (known in SIS as `povinn`)
     */
    private $courseId;

    /**
     * @var string
     * Type of the event (lecture, labs, ...) as SisScheduleEvent::TYPE_* constant.
     */
    private $type;

    /**
     * @var string|null
     * Affiliation of the selected user to the listed event (as SisAffiliation::TYPE_* constant).
     */
    private $affiliation;

    /**
     * @var string[]
     * Captions of the course in different languages, indexed by language code (cs, en).
     */
    private $captions;

    /**
     * @var string[]
     * Annotations of the course in different languages, indexed by language code (cs, en).
     */
    private $annotations;

    /**
     * @var int
     * Calendar year where the academic year (where the event is scheduled) begins.
     */
    private $year;

    /**
     * @var int
     * Semester of the course (1 for winter, 2 for summer).
     */
    private $term;

    /**
     * @var string
     * SIS user ID of the user associated with the course record.
     */
    private $sisUserId;

    /**
     * @var int|null
     * Day of the week when the event is scheduled (0=Sunday, 1=Monday...).
     * Null if the event is not scheduled on a specific day.
     */
    private $dayOfWeek;

    /**
     * @var int|null
     * Time of the event in minutes since midnight (0-1439).
     * Null if the event is not scheduled at a specific time.
     */
    private $time;

    /**
     * @var string|null
     * Room where the event is scheduled. Null if the event is not scheduled in a specific room.
     */
    private $room;

    /**
     * @var bool
     * Whether the event is scheduled bi-weekly (every two weeks).
     */
    private $fortnightly;

    /**
     * @var int
     * The first logical week of the semester when the event starts (usually 1).
     */
    private $firstWeek;

    /**
     * @var string[]
     * Mapping of SIS event types to internal constants.
     */
    private static $typeMap = [
        "P" => SisScheduleEvent::TYPE_LECTURE,
        "X" => SisScheduleEvent::TYPE_LABS,
    ];

    /**
     * @var string[]
     * Mapping of SIS affiliation types to internal constants.
     */
    private static $affiliationMap = [
        "student" => SisAffiliation::TYPE_STUDENT,
        "teacher" => SisAffiliation::TYPE_TEACHER,
        "guarantor" => SisAffiliation::TYPE_GUARANTOR,
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
        $result->affiliation = array_key_exists($data["affiliation"], self::$affiliationMap)
            ? self::$affiliationMap[$data["affiliation"]] : null;
        $result->year = intval($data["year"]);
        $result->term = intval($data["semester"]);
        $result->dayOfWeek = $data["day_of_week"] !== null ? intval($data["day_of_week"]) : null;
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
        if (!$this->affiliation) {
            return;
        }

        $user = $users->getBySisId($this->sisUserId);
        if (!$user) {
            return;
        }

        $affiliation = $affiliations->getAffiliation($event, $user, $term);
        if ($affiliation) {
            // update existing affiliation
            $affiliation->setType($this->affiliation);
        } else {
            // create new affiliation
            $affiliation = new SisAffiliation($user, $event, $this->affiliation);
        }
        $affiliations->persist($affiliation, false);

        $user->setSisEventsLoaded();
        $users->persist($user);
    }
}
