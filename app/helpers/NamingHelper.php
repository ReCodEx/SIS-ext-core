<?php

namespace App\Helpers;

use App\Model\Entity\SisScheduleEvent;

/**
 * Helper class for naming conventions.
 * This class is currently implemented in a crude way, but it can be reimplemented in the future
 * (e.g., to use configurable naming conventions).
 */
class NamingHelper
{
    /**
     * Get the name of the group for a given SIS schedule event.
     * This name is constructed from the course name and the event's details.
     * @param SisScheduleEvent $event The SIS schedule event.
     * @param string $locale The locale to use for translation ('cs' or 'en').
     * @return string|null The group name or null if it cannot be determined.
     */
    public function getGroupName(SisScheduleEvent $event, string $locale): ?string
    {
        $courseName = $event->getCourse()->getCaption($locale);
        $dayNames = [
            'en' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            'cs' => ['Ne', 'Po', 'Út', 'St', 'Čt', 'Pá', 'So'],
        ];
        $fortnight = [
            'en' => [0 => 'Even weeks', 1 => 'Odd weeks'],
            'cs' => [0 => 'Sudé týdny', 1 => 'Liché týdny'],
        ];
        $unscheduled = [
            'en' => 'unscheduled',
            'cs' => 'nerozvrženo',
        ];

        if (!$courseName || empty($dayNames[$locale])) {
            return null;
        }

        $info = [];
        if ($event->getDayOfWeek() !== null && array_key_exists($event->getDayOfWeek(), $dayNames[$locale])) {
            $info[] = $dayNames[$locale][$event->getDayOfWeek()];
        }
        if ($event->getTime() !== null) {
            $info[] = (int)($event->getTime() / 60) . ':'
                . str_pad((string)($event->getTime() % 60), 2, '0', STR_PAD_LEFT);
        }
        if ($event->getFortnight() && $event->getFirstWeek() !== null) {
            $info[] = $fortnight[$locale][$event->getFirstWeek() % 2];
        }
        if ($event->getRoom() !== null) {
            $info[] = $event->getRoom();
        }

        $info = $info ? implode(', ', $info) : $unscheduled[$locale];
        return "$courseName ($info)";
    }

    /**
     * Get the description of the group for a given SIS schedule event.
     * @param SisScheduleEvent $event The SIS schedule event.
     * @param string $locale The locale to use for translation ('cs' or 'en').
     * @return string|null The group description or null if it cannot be determined.s
     */
    public function getGroupDescription(SisScheduleEvent $event, string $locale): ?string
    {
        $templates = [
            'en' => 'A group create from SIS scheduling event `%s` for course `%s`.',
            'cs' => 'Skupina vytvořená z rozvrhového lístku SISu `%s` pro předmět `%s`.',
        ];
        if (empty($templates[$locale])) {
            return null;
        }
        return sprintf($templates[$locale], $event->getSisId(), $event->getCourse()->getCode());
    }
}
