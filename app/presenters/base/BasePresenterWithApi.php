<?php

namespace App\Presenters;

use App\Helpers\RecodexApiHelper;
use App\Helpers\SisHelper;

/**
 * Base presenter for presenters that need to access ReCodEx API.
 * Automatically initializes injected ReCodEx API helper with the auth token.
 */
class BasePresenterWithApi extends BasePresenter
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

    public function startup()
    {
        parent::startup();

        // Initialize ReCodEx auth token (main part is in User entity, suffix is in our auth token's payload).
        $user = $this->getCurrentUser();
        $token = $this->getAccessToken();
        $suffix = $token->getPayload('suffix');

        if ($user->getRecodexToken() && $suffix) {
            $this->recodexApi->setAuthToken($user->getRecodexToken() . $suffix);
        }
    }
}
