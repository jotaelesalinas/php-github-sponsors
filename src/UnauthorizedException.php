<?php

namespace JLSalinas\GithubSponsors;

use Exception;

class UnauthorizedException extends Exception
{
    public function __construct() {
        parent::__construct('Access to the API was denied.');
    }
}
