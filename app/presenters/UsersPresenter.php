<?php

namespace App\Presenters;

use App\Exceptions\ForbiddenRequestException;
use App\Model\Repository\Users;
use App\Helpers\RecodexApiHelper;
use App\Security\Roles;
use App\Security\ACL\IUserPermissions;

/**
 * User-related operations.
 */
class UsersPresenter extends BasePresenter
{
    /**
     * @var RecodexApiHelper
     * @inject
     */
    public $recodexApi;

    /**
     * @var Users
     * @inject
     */
    public $users;

    /**
     * @var IUserPermissions
     * @inject
     */
    public $userAcl;

    /**
     * @var Roles
     * @inject
     */
    public $roles;

    public function checkDefault(string $id)
    {
        $user = $this->users->findOrThrow($id);
        if (!$this->userAcl->canViewDetail($user)) {
            throw new ForbiddenRequestException("You do not have permissions to view selected user's details.");
        }
    }

    /**
     * @GET
     */
    public function actionDefault(string $id)
    {
        $user = $this->users->findOrThrow($id);
        $this->sendSuccessResponse($user);
    }
}
