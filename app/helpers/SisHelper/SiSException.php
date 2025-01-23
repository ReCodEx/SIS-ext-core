<?php

namespace App\Exceptions;

use Exception;

/**
 * Call to SIS API failed.
 */
class SisException extends InternalServerException
{
    /**
     * Create instance with further details.
     * @param string $details description
     * @param Exception $previous Previous exception
     */
    public function __construct(string $details, $previous = null)
    {
        parent::__construct(
            "SIS API Error - $details",
            FrontendErrorMappings::E500_000__INTERNAL_SERVER_ERROR,
            null,
            $previous
        );
    }
}
