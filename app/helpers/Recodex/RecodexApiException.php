<?php

namespace App\Exceptions;

use Exception;

/**
 * Call to ReCodEx API failed.
 */
class RecodexApiException extends InternalServerException
{
    private $response = null;
    private $body = null;

    /**
     * Create instance with further details.
     * @param string $details description
     * @param Exception $previous Previous exception
     */
    public function __construct(string $details, $previous = null, $response = null, $body = null)
    {
        parent::__construct(
            "ReCodEx API Error - $details",
            FrontendErrorMappings::E500_000__INTERNAL_SERVER_ERROR,
            null,
            $previous
        );
        $this->response = $response;
        $this->body = $body;
    }

    /**
     * Get the response object of the failed API call, if available.
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Get the body of the response of the failed API call, if available.
     */
    public function getBody()
    {
        return $this->body;
    }
}
