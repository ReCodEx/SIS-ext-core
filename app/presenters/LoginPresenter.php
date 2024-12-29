<?php

namespace App\Presenters;

use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenRequestException;
use App\Exceptions\FrontendErrorMappings;
use App\Exceptions\InvalidAccessTokenException;
use App\Exceptions\InvalidArgumentException;
use App\Exceptions\WrongCredentialsException;
use App\Model\Repository\Users;
use App\Security\AccessManager;
use App\Security\Identity;
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
     * Log in using user credentials
     * @POST
     * @Param(type="post", name="username", validation="email:1..", description="User's E-mail")
     * @Param(type="post", name="password", validation="string:1..", description="Password")
     * @throws AuthenticationException
     * @throws ForbiddenRequestException
     * @throws InvalidAccessTokenException
     * @throws WrongCredentialsException
     */
    public function actionDefault()
    {
        $req = $this->getRequest();
        $username = $req->getPost("username");

        //$user = $this->credentialsAuthenticator->authenticate($username, $password);
        //$this->verifyUserIpLock($user);
        //$user->updateLastAuthenticationAt();
        $this->users->flush();

        //$token = $this->accessManager->issueToken($user, null, [TokenScope::MASTER, TokenScope::REFRESH]);
        //$this->getUser()->login(new Identity($user, $this->accessManager->decodeToken($token)));

        $this->sendSuccessResponse([]
        /*            [
                "accessToken" => $token,
                "user" => $user,
            ]
        */);
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
