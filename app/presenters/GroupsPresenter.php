<?php

namespace App\Presenters;

use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenRequestException;
use App\Exceptions\NotFoundException;
use App\Exceptions\NotImplementedException;
use App\Helpers\RecodexApiHelper;
use App\Helpers\RecodexGroup;
use App\Model\Entity\SisScheduleEvent;
use App\Model\Repository\SisScheduleEvents;
use App\Security\ACL\IEventPermissions;
use App\Security\ACL\IGroupPermissions;

/**
 * Group management (both for teachers and students).
 */
class GroupsPresenter extends BasePresenter
{
    /**
     * @var SisScheduleEvents
     * @inject
     */
    public $sisEvents;

    /**
     * @var RecodexApiHelper
     * @inject
     */
    public $recodexApi;

    /**
     * @var IEventPermissions
     * @inject
     */
    public $eventAcl;

    /**
     * @var IGroupPermissions
     * @inject
     */
    public $groupAcl;

    private function isGroupSuitableForEvent(array $groups, string $groupId, SisScheduleEvent $event): void
    {
        if (empty($groups[$groupId])) {
            throw new NotFoundException("Group $groupId does not exist or is not accessible by the user.");
        }

        $courseId = $event->getCourse()->getCode();
        $term = $event->getTerm()->getYearTermKey();
        $courseCheck = $termCheck = false;
        $group = $groups[$groupId];
        while ($group !== null && !$courseCheck && !$termCheck) {
            $courseCheck |= $group->hasCourseAttribute($courseId);
            $termCheck |= $group->hasTermAttribute($term);
            $group = $group->parentGroupId ? ($groups[$group->parentGroupId] ?? null) : null;
        }

        if (!$courseCheck || !$termCheck) {
            throw new ForbiddenRequestException("Group $groupId is not located under the required course or term.");
        }
    }

    private function canUserAdministrateGroup(array $groups, string $groupId): void
    {
        if (empty($groups[$groupId])) {
            throw new NotFoundException("Group $groupId does not exist or is not accessible by the user.");
        }

        $group = $groups[$groupId];
        if ($group->membership === RecodexGroup::MEMBERSHIP_SUPERVISOR) {
            return; // direct supervisor has sufficient rights
        }

        // admin of selected group or any ancestor group also has sufficient rights
        while ($group !== null) {
            if ($group->membership === RecodexGroup::MEMBERSHIP_ADMIN) {
                return;
            }
            $group = $group->parentGroupId ? ($groups[$group->parentGroupId] ?? null) : null;
        }

        throw new ForbiddenRequestException("You do not have permissions to administrate group $groupId.");
    }

    public function checkStudent()
    {
        if (!$this->groupAcl->canViewStudent()) {
            throw new ForbiddenRequestException("You do not have permissions to list student groups.");
        }
    }

    /**
     * Proxy to ReCodEx that retrieves all groups relevant for student (joining groups).
     * @GET
     * @Param(type="query", name="eventIds", validation="array",
     *        description="List of SIS group IDs that we search for.")
     */
    public function actionStudent(array $eventIds)
    {
        $groups = $this->recodexApi->getGroups($this->getCurrentUser());
        $groups = RecodexGroup::pruneForStudent($groups, $eventIds);
        $this->sendSuccessResponse($groups);
    }

    public function checkTeacher()
    {
        if (!$this->groupAcl->canViewTeacher()) {
            throw new ForbiddenRequestException("You do not have permissions to list teacher groups.");
        }
    }

    /**
     * Proxy to ReCodEx that retrieves all groups relevant for teacher creating groups.
     * @GET
     * @Param(type="query", name="eventIds", validation="array",
     *        description="List of SIS group IDs the teacher teaches.")
     * @Param(type="query", name="courseIds", validation="array",
     *        description="List of SIS courses the teacher is involved in.")
     */
    public function actionTeacher(array $eventIds, array $courseIds)
    {
        $groups = $this->recodexApi->getGroups($this->getCurrentUser());
        $groups = RecodexGroup::pruneForTeacher($groups, $courseIds, $eventIds);
        $this->sendSuccessResponse($groups);
    }

    public function checkCreate(string $parentId, string $eventId)
    {
        $event = $this->sisEvents->findOrThrow($eventId);
        if (!$this->eventAcl->canCreateGroup($event)) {
            throw new ForbiddenRequestException("You do not have permissions to create groups for selected SIS event.");
        }

        $groups = $this->recodexApi->getGroups($this->getCurrentUser());
        $this->isGroupSuitableForEvent($groups, $parentId, $event); // throws exception if not suitable
    }

    /**
     * Proxy to ReCodEx that creates a new group.
     * @POST
     * @param string $parentId ID of the parent group under which the new group is created
     * @param string $eventId ID of the SIS event the new group is created for
     */
    public function actionCreate(string $parentId, string $eventId)
    {
        throw new NotImplementedException();
    }

    public function checkBind(string $id, string $eventId)
    {
        $event = $this->sisEvents->findOrThrow($eventId);
        if (!$this->eventAcl->canBindGroup($event)) {
            throw new ForbiddenRequestException("You do not have permissions to bind groups for selected SIS event.");
        }

        $groups = $this->recodexApi->getGroups($this->getCurrentUser());
        $this->isGroupSuitableForEvent($groups, $id, $event); // throws exception if not suitable

        if ($groups[$id]->hasGroupAttribute($event->getSisId())) {
            throw new BadRequestException("Group $id is already bound to the selected SIS event.");
        }
    }

    /**
     * Proxy to ReCodEx that binds a group with schedule event (student group) in SIS.
     * This basically sets the 'group' attribute to ReCodEx group entity.
     * @POST
     */
    public function actionBind(string $id, string $eventId)
    {
        throw new NotImplementedException();
    }

    public function checkUnbind(string $id, string $eventId)
    {
        $event = $this->sisEvents->findOrThrow($eventId);
        if (!$this->eventAcl->canBindGroup($event)) {
            throw new ForbiddenRequestException("You do not have permissions to unbind groups for selected SIS event.");
        }

        $groups = $this->recodexApi->getGroups($this->getCurrentUser());
        $this->canUserAdministrateGroup($groups, $id); // throws exception if not

        if (!$groups[$id]->hasGroupAttribute($event->getSisId())) {
            throw new BadRequestException("Group $id is not bound to the selected SIS event.");
        }
    }

    /**
     * Proxy to ReCodEx that unbinds a group with schedule event (student group) in SIS.
     * This basically removes the 'group' attribute from ReCodEx group entity.
     * @POST
     */
    public function actionUnbind(string $id, string $eventId)
    {
        throw new NotImplementedException();
    }

    public function checkJoin(string $id)
    {
        $groups = $this->recodexApi->getGroups($this->getCurrentUser());
        if (empty($groups[$id])) {
            throw new NotFoundException("Group $id does not exist or is not accessible by the user.");
        }

        $group = $groups[$id];
        if ($group->membership !== null) {
            throw new BadRequestException("User is already a member ($group->membership) of group $id.");
        }

        foreach ($group->attributes[RecodexGroup::ATTR_GROUP_KEY] ?? [] as $eventId) {
            if (!$this->eventAcl->canJoinGroup($this->sisEvents->findOrThrow($eventId))) {
                return; // suitable event was found
            }
        }

        // no corresponding event found -> deny access
        throw new ForbiddenRequestException(
            "Group $id does not correspond to any of SIS events you are enrolled for."
        );
    }

    /**
     * Proxy to ReCodEx that adds student to a group.
     * @POST
     */
    public function actionJoin(string $id)
    {
        throw new NotImplementedException();
    }
}
