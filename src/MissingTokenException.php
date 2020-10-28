<?php

namespace JLSalinas\GithubSponsors;

use Exception;

class MissingTokenException extends Exception
{
    public function __construct()
    {
        parent::__construct('Missing token.');
    }
}
