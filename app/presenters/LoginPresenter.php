<?php

namespace App\Presenters;

use App\Exceptions\ForbiddenRequestException;
use App\Exceptions\InvalidAccessTokenException;
use App\Exceptions\WrongCredentialsException;
use App\Model\Repository\Users;
use App\Helpers\RecodexApiHelper;
use App\Helpers\RecodexUser;
use App\Security\AccessManager;
use App\Security\Roles;
use App\Security\TokenScope;
use Nette\Security\AuthenticationException;

/**
 * Endpoints used to log a user in
 */
class LoginPresenter extends BasePresenter
{
    /**
     * @var AccessManager
     * @inject
     */
    public $accessManager;

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
     * @var Roles
     * @inject
     */
    public $roles;

    /**
     * Log in using temp token from ReCodEx.
     * @POST
     * @Param(type="post", name="token", validation="string:1..", description="Tmp. token from ReCodEx")
     * @throws AuthenticationException
     * @throws ForbiddenRequestException
     * @throws InvalidAccessTokenException
     * @throws WrongCredentialsException
     */
    public function actionDefault()
    {
        $req = $this->getRequest();
        $tempToken = $req->getPost("token");
        $instanceId = $this->recodexApi->getTempTokenInstance($tempToken);

        // Call ReCodEx API and get full token + user info using the temporary token
        $this->recodexApi->setAuthToken($tempToken);
        $recodexResponse = $this->recodexApi->getTokenAndUser();
        /** @var RecodexUser */
        $recodexUser = $recodexResponse['user'];

        // Make sure corresponding user exists and is up to date.
        $user = $this->users->get($recodexUser->getId());
        if (!$user) {
            $user = $recodexUser->createUser($instanceId);
        } else {
            $recodexUser->updateUser($user);
        }
        $user->updatedNow();

        // part of the token is stored in the database, suffix goes into our token (payload)
        $tokenSuffix = $user->setRecodexToken($recodexResponse['accessToken']);
        $this->users->persist($user);

        // generate our token for our frontend
        $token = $this->accessManager->issueToken(
            $user,
            null, // no effective role overried
            [TokenScope::MASTER, TokenScope::REFRESH],
            null, // default expiration
            ['suffix' => $tokenSuffix]
        );

        $this->sendSuccessResponse([
            "accessToken" => $token,
            "user" => $user,
        ]);
    }

    /**
     * @throws ForbiddenRequestException
     */
    public function checkRefresh()
    {
        if (!$this->isInScope(TokenScope::REFRESH)) {
            throw new ForbiddenRequestException(
                sprintf("Only tokens in the '%s' scope can be refreshed", TokenScope::REFRESH)
            );
        }
    }

    /**
     * Refresh the access token of current user
     * @GET
     * @LoggedIn
     * @throws ForbiddenRequestException
     */
    public function actionRefresh()
    {
        $token = $this->getAccessToken();

        $user = $this->getCurrentUser();
        $this->users->flush();

        $this->sendSuccessResponse(
            [
                "accessToken" => $this->accessManager->issueRefreshedToken($token),
                "user" => $user,
            ]
        );
    }
}
