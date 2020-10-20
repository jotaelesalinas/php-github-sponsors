<?php

namespace JLSalinas\GithubSponsors\DTOs;

use Spatie\DataTransferObject\DataTransferObject;

class TierData extends DataTransferObject
{
    public int $id;
    public string $name;
    public string $description;
    public int $monthlyPriceInDollars;
    public int $monthlyPriceInCents;
}
