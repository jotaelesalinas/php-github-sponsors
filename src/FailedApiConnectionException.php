<?php

namespace JLSalinas\GithubSponsors;

use Exception;

class FailedApiConnectionException extends Exception
{
    public function __construct($msg = null) {
        parent::__construct(
            'Connection to the API server failed' .
            ($msg !== null ? $msg : '.')
        );
    }
}
