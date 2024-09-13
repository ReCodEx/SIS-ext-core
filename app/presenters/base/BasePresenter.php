<?php

namespace App\Presenters;

use App\Security\AccessToken;
use App\Security\Identity;
use App\Security\AccessManager;
use App\Security\Authorizator;
use Nette\Utils\Strings;
use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenRequestException;
use App\Exceptions\WrongHttpMethodException;
use App\Exceptions\NotImplementedException;
use App\Exceptions\InvalidArgumentException;
use App\Exceptions\InternalServerException;
use App\Exceptions\FrontendErrorMappings;
use App\Model\Entity\User;
use App\Model\Repository\Users;
use App\Helpers\UserActions;
use App\Helpers\Validators;
use App\Helpers\AnnotationsParser;
use Nette\Application\Application;
use Nette\Http\IResponse;
use Nette\Utils\Arrays;
use Tracy\ILogger;
use ReflectionClass;
use ReflectionMethod;
use LogicException;
use ReflectionException;
use Nette;

class BasePresenter extends Nette\Application\UI\Presenter
{
    /**
     * @var Users
     * @inject
     */
    public $users;

    /**
     * @var UserActions
     * @inject
     */
    public $userActions;

    /**
     * @var AccessManager
     * @inject
     */
    public $accessManager;

    /**
     * @var Application
     * @inject
     */
    public $application;

    /**
     * @var Authorizator
     * @inject
     */
    public $authorizator;

    /**
     * @var ILogger
     * @inject
     */
    public $logger;

    /** @var object Processed parameters from annotations */
    protected $parameters;

    protected function formatPermissionCheckMethod($action)
    {
        return "check" . $action;
    }

    public function startup()
    {
        parent::startup();
        $this->application->errorPresenter = "ApiError";
        $this->parameters = new \stdClass();

        try {
            $presenterReflection = new ReflectionClass($this);
            $actionMethodName = $this->formatActionMethod($this->getAction());
            $actionReflection = $presenterReflection->getMethod($actionMethodName);
        } catch (ReflectionException $e) {
            throw new NotImplementedException();
        }

        // ACL-checking method
        $this->tryCall($this->formatPermissionCheckMethod($this->getAction()), $this->params);

        Validators::init();
        $this->processParams($actionReflection);
    }

    protected function isRequestJson(): bool
    {
        return $this->getHttpRequest()->getHeader("content-type") === "application/json";
    }

    /**
     * @return User
     * @throws ForbiddenRequestException
     */
    protected function getCurrentUser(): User
    {
        /** @var ?Identity $identity */
        $identity = $this->getUser()->getIdentity();

        if ($identity === null || $identity->getUserData() === null) {
            throw new ForbiddenRequestException();
        }

        return $identity->getUserData();
    }

    /**
     * @throws ForbiddenRequestException
     */
    protected function getAccessToken(): AccessToken
    {
        /** @var ?Identity $identity */
        $identity = $this->getUser()->getIdentity();

        if ($identity === null || $identity->getToken() === null) {
            throw new ForbiddenRequestException();
        }

        return $identity->getToken();
    }

    /**
     * Is current user in the given scope?
     * @param string $scope Scope ID
     * @return bool
     */
    protected function isInScope(string $scope): bool
    {
        /** @var ?Identity $identity */
        $identity = $this->getUser()->getIdentity();

        if (!$identity) {
            return false;
        }

        return $identity->isInScope($scope);
    }

    private function processParams(ReflectionMethod $reflection)
    {
        $annotations = AnnotationsParser::getAll($reflection);
        $requiredFields = Arrays::get($annotations, "Param", []);

        foreach ($requiredFields as $field) {
            $type = strtolower($field->type);
            $name = $field->name;
            $validationRule = isset($field->validation) ? $field->validation : null;
            $msg = isset($field->msg) ? $field->msg : null;
            $required = isset($field->required) ? $field->required : true;

            $value = null;
            switch ($type) {
                case "post":
                    $value = $this->getPostField($name, $required);
                    break;
                case "query":
                    $value = $this->getQueryField($name, $required);
                    break;
                default:
                    throw new InternalServerException("Unknown parameter type '$type'");
            }

            if ($validationRule !== null && $value !== null) {
                $value = $this->validateValue($name, $value, $validationRule, $msg);
            }

            $this->parameters->$name = $value;
        }
    }

    private function getPostField($param, $required = true)
    {
        $req = $this->getRequest();
        $post = $req->getPost();

        if ($req->isMethod("POST")) {
            // nothing to see here...
        } else {
            if ($req->isMethod("PUT") || $req->isMethod("DELETE")) {
                parse_str(file_get_contents('php://input'), $post);
            } else {
                throw new WrongHttpMethodException(
                    "Cannot get the post parameters in method '" . $req->getMethod() . "'."
                );
            }
        }

        if (array_key_exists($param, $post)) {
            return $post[$param];
        } else {
            if ($required) {
                throw new BadRequestException("Missing required POST field $param");
            } else {
                return null;
            }
        }
    }

    private function getQueryField($param, $required = true)
    {
        $value = $this->getRequest()->getParameter($param);
        if ($value === null && $required) {
            throw new BadRequestException("Missing required query field $param");
        }
        return $value;
    }

    private function validateValue($param, $value, $validationRule, $msg = null)
    {
        foreach (["int", "integer"] as $rule) {
            if ($validationRule === $rule || Strings::startsWith($validationRule, $rule . ":")) {
                throw new LogicException("Validation rule '$validationRule' will not work for request parameters");
            }
        }

        $value = Validators::preprocessValue($value, $validationRule);
        if (Validators::is($value, $validationRule) === false) {
            throw new InvalidArgumentException(
                $param,
                $msg ?? "The value '$value' does not match validation rule '$validationRule'"
                    . " - for more information check the documentation of Nette\\Utils\\Validators"
            );
        }

        return $value;
    }

    protected function logUserAction($code = IResponse::S200_OK)
    {
        if ($this->getUser()->isLoggedIn()) {
            $remoteAddr = $this->getHttpRequest()->getRemoteAddress();
            $params = $this->getRequest()->getParameters();
            unset($params[self::ACTION_KEY]);
            $this->userActions->log($this->getAction(true), $remoteAddr, $params, $code);
        }
    }

    protected function sendSuccessResponse($payload, $code = IResponse::S200_OK)
    {
        $this->logUserAction($code);

        $resp = $this->getHttpResponse();
        $resp->setCode($code);
        $this->sendJson(
            [
                "success" => true,
                "code" => $code,
                "payload" => $payload
            ]
        );
    }
}
