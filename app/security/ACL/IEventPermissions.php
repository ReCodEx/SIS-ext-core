<?php

namespace App\Security\ACL;

use App\Model\Entity\SisScheduleEvent;

interface IEventPermissions
{
    public function canJoinGroup(SisScheduleEvent $event): bool;

    public function canBindGroup(SisScheduleEvent $event): bool;

    public function canCreateGroup(SisScheduleEvent $event): bool;
}
