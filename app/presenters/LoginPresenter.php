<?php

namespace App\Presenters;

use App\Exceptions\ForbiddenRequestException;
use App\Exceptions\InvalidAccessTokenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\WrongCredentialsException;
use App\Model\Entity\User;
use App\Model\Repository\Users;
use App\Helpers\RecodexApiHelper;
use App\Helpers\RecodexUser;
use App\Security\AccessManager;
use App\Security\Roles;
use App\Security\TokenScope;
use Nette\Security\AuthenticationException;
use Exception;

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
     * Split the ReCodEx API token (save it to DB and suffix to the newly generated token),
     * generate a new token for our frontend and send the response.
     * @param User $user The user to log in
     * @param string $token The token from ReCodEx API to split and save
     */
    private function finalizeLogin(User $user, string $token): void
    {
        // part of the token is stored in the database, suffix goes into our token (payload)
        $tokenSuffix = $user->setRecodexToken($token);
        $user->updatedNow();
        $this->users->persist($user);

        // generate our token for our frontend
        $token = $this->accessManager->issueToken(
            $user,
            null, // no effective role override
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

        $this->finalizeLogin($user, $recodexResponse['accessToken']);
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
     * Refresh the access token of current user (as well as the ReCodEx API token).
     * @GET
     * @LoggedIn
     * @throws AuthenticationException
     * @throws ForbiddenRequestException
     * @throws NotFoundException
     * @throws InvalidAccessTokenException
     */
    public function actionRefresh()
    {
        $recodexResponse = $this->recodexApi->refreshToken();
        /** @var RecodexUser */
        $recodexUser = $recodexResponse['user'];

        // Update the user entity with new info from ReCodEx.
        $user = $this->users->findOrThrow($recodexUser->getId());
        $recodexUser->updateUser($user);

        $this->finalizeLogin($user, $recodexResponse['accessToken']);
    }
}
