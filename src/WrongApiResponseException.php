<?php

namespace JLSalinas\GithubSponsors;

use Exception;

class WrongApiResponseException extends Exception
{
    public static function isNull(): self
    {
        return new static("The request failed.");
    }

    public static function noData(): self
    {
        return new static("There is no data in the response.");
    }

    public static function missingField(string $field): self
    {
        return new static("There is no '{$field}' in the response.");
    }
}
