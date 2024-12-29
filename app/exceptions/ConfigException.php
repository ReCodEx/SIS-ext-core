<?php

namespace App\Exceptions;

use Nette\Http\IResponse;
use Exception;

/**
 * Something is wrong in the configuration of the core module.
 */
class ConfigException extends ApiException
{
    /**
     * Create instance with further details.
     * @param string $details description
     * @param string $frontendErrorCode
     * @param null $frontendErrorParams
     * @param Exception $previous Previous exception
     */
    public function __construct(
        string $details = 'please contact the administrator of the service',
        string $frontendErrorCode = FrontendErrorMappings::E500_000__INTERNAL_SERVER_ERROR,
        $frontendErrorParams = null,
        $previous = null
    ) {
        parent::__construct(
            "Internal Server Error - $details",
            IResponse::S500_InternalServerError,
            $frontendErrorCode,
            $frontendErrorParams,
            $previous
        );
    }
}
