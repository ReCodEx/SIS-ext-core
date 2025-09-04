<?php

namespace App\Presenters;

use App\Exceptions\ForbiddenRequestException;
use App\Exceptions\NotImplementedException;
use App\Helpers\RecodexApiHelper;
use App\Helpers\RecodexGroup;
use App\Model\Repository\SisTerms;
use App\Security\ACL\IGroupPermissions;

/**
 * Group management (both for teachers and students).
 */
class GroupsPresenter extends BasePresenter
{
    /**
     * @var SisTerms
     * @inject
     */
    public $sisTerms;

    /**
     * @var RecodexApiHelper
     * @inject
     */
    public $recodexApi;

    /**
     * @var IGroupPermissions
     * @inject
     */
    public $groupAcl;

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
     * @Param(type="query", name="courseIds", validation="array",
     *        description="List of SIS courses the teacher is involved in.")
     */
    public function actionTeacher(array $courseIds)
    {
        $groups = $this->recodexApi->getGroups($this->getCurrentUser());
        $groups = RecodexGroup::pruneForTeacher($groups, $courseIds);
        $this->sendSuccessResponse($groups);
    }

    /**
     * Proxy to ReCodEx that creates a new group.
     * @POST
     */
    public function actionCreate()
    {
        throw new NotImplementedException();
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

    /**
     * Proxy to ReCodEx that unbinds a group with schedule event (student group) in SIS.
     * This basically removes the 'group' attribute from ReCodEx group entity.
     * @POST
     */
    public function actionUnbind(string $id, string $eventId)
    {
        throw new NotImplementedException();
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
