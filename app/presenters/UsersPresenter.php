<?php

namespace App\Presenters;

use App\Exceptions\ForbiddenRequestException;
use App\Model\Entity\UserChangelog;
use App\Model\Repository\Users;
use App\Model\Repository\UserChangelogs;
use App\Model\Repository\SisUsers;
use App\Helpers\RecodexApiHelper;
use App\Helpers\SisHelper;
use App\Helpers\UserUpdater;
use App\Security\ACL\IUserPermissions;
use Tracy\ILogger;
use DateTime;
use Exception;

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
     * @var SisHelper
     * @inject
     */
    public $sisApi;

    /**
     * @var Users
     * @inject
     */
    public $users;

    /**
     * @var SisUsers
     * @inject
     */
    public $sisUsers;

    /**
     * @var UserChangelogs
     * @inject
     */
    public $userChangelogs;

    /**
     * @var IUserPermissions
     * @inject
     */
    public $userAcl;

    /**
     * @var UserUpdater
     * @inject
     */
    public $userUpdater;

    public function checkDefault(string $id)
    {
        $user = $this->users->findOrThrow($id);
        if (!$this->userAcl->canViewDetail($user)) {
            throw new ForbiddenRequestException("You do not have permissions to view selected user's details.");
        }
    }

    /**
     * Retrieve user data
     * @GET
     * @param string $id of the user
     */
    public function actionDefault(string $id)
    {
        $user = $this->users->findOrThrow($id);
        $this->sendSuccessResponse($user);
    }

    public function checkSisUser(string $id)
    {
        $user = $this->users->findOrThrow($id);
        if (!$this->userAcl->canFetchSis($user)) {
            throw new ForbiddenRequestException("You do not have permissions to fetch SIS user data.");
        }
    }

    /**
     * Fetch SIS user data from our cache of from SIS.
     * The update (fetching data from SIS API) is done if expiration conditions are met.
     * @POST
     * @param string $id of the (ReCodEx) user (associated with SIS user entity)
     * @Param(type="post", name="expiration", validation="numericint", required=false,
     *        description="Age of cached obj (in days) before they need re-fetching from SIS.
     *                     No expiration, no refetching is performed.")
     */
    public function actionSisUser(string $id)
    {
        $user = $this->users->findOrThrow($id);
        $sisUser = $this->sisUsers->get($user->getSisId());
        if ($sisUser && $sisUser->getId() !== $user->getSisId()) {
            // this is just an assert, it should never happen
            throw new Exception("SIS user ID and user's SIS ID does not match!");
        }
        $updated = false;
        $failed = false;

        // let's see if the sisUser needs updating
        $expiration = $this->getRequest()->getPost('expiration');
        if ($expiration !== null) {
            $expiration = (int)$expiration;
            $threshold = new DateTime();
            if ($expiration > 0) {
                $threshold->modify("-$expiration day");
            }

            try {
                if (!$sisUser || $sisUser->getUpdatedAt() < $threshold) {
                    $sisUserRecord = $this->sisApi->getUserRecord($user->getSisId());
                    if (!$sisUser) {
                        $sisUser = $sisUserRecord->createUser();
                    } else {
                        $sisUserRecord->updateUser($sisUser);
                    }
                    $sisUser->updatedNow();
                    $user->setSisUserLoaded();
                    $this->sisUsers->persist($sisUser, false);
                    $this->users->persist($user);
                    $updated = true;
                }
            } catch (Exception $e) {
                $this->logger->log($e, ILogger::EXCEPTION);
                $failed = true;
            }
        }

        $this->sendSuccessResponse(['sisUser' => $sisUser, 'updated' => $updated, 'failed' => $failed]);
    }

    public function checkSyncSis(string $id)
    {
        $user = $this->users->findOrThrow($id);
        if (!$this->userAcl->canSyncSis($user)) {
            throw new ForbiddenRequestException("You do not have permissions to push SIS user data to ReCodEx.");
        }
    }

    /**
     * Perform the synchronization between our SIS data and ReCodEx.
     * @POST
     * @param string $id of the user being synced with SIS
     */
    public function actionSyncSis(string $id)
    {
        $user = $this->users->findOrThrow($id);
        $sisUser = $this->sisUsers->get($user->getSisId());

        // refresh user data from ReCodEx and check we are up to date
        $recodexUser = $this->recodexApi->getUser($user->getId());
        if ($recodexUser->updateUser($user)) {
            $user->updatedNow();
            $this->users->persist($user);
            $this->sendSuccessResponse(['user' => $user, 'sisUser' => $sisUser, 'canceled' => true]);
            return;
        }

        // perform a diff and see whether any copying is needed
        $diff = $this->userUpdater->diff($user, $sisUser);
        if ($diff) {
            $updateLogin = array_key_exists('login', $diff);
            $updateProfile = !$updateLogin || count($diff) > 1;
            $this->userUpdater->update($user, $sisUser);

            if ($updateProfile) {
                $recodexUser = $this->recodexApi->updateUser($user);
            }
            if ($updateLogin) {
                $authService = $this->recodexApi->getSisLoginKey();
                $recodexUser = $user->getSisLogin()
                    ? $this->recodexApi->setExternalId($user->getId(), $authService, $user->getSisLogin())
                    : $this->recodexApi->removeExternalId($user->getId(), $authService);
            }

            // make sure returned recodex user stored the right data
            $recodexUser->updateUser($user);
            $this->users->persist($user, false);

            // save the updated user entity and the diff changelog to local DB
            $changelog = new UserChangelog($user, $diff);
            $this->userChangelogs->persist($changelog);
        }


        $this->sendSuccessResponse(['user' => $user, 'sisUser' => $sisUser, 'updated' => (bool)$diff]);
    }
}
