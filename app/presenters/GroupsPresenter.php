<?php

namespace App\Presenters;

use App\Exceptions\ForbiddenRequestException;
use App\Exceptions\BadRequestException;
use App\Model\Entity\SisTerm;
use App\Model\Repository\SisTerms;
use App\Security\ACL\ITermPermissions;
use Nette\Application\Request;
use DateTime;

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


    public function checkDefault()
    {
        //    throw new ForbiddenRequestException("You do not have permissions to list terms.");
    }

    /**
     * @GET
     */
    public function actionDefault()
    {
        $terms = $this->sisTerms->findAll();
        $this->sendSuccessResponse($terms);
    }
}
