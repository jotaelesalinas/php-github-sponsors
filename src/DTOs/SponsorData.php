<?php

namespace JLSalinas\GithubSponsors\DTOs;

use Spatie\DataTransferObject\DataTransferObject;

class SponsorData extends DataTransferObject
{
    public int $id;
    public string $name;
    public string $login;
    public string $email;
    public string $url;
    public string $avatarUrl;
}
