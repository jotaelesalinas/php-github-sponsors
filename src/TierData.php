<?php

namespace JLSalinas\GithubSponsors;

use Spatie\DataTransferObject\DataTransferObject;

class TierData extends DataTransferObject
{
    public int $id;
    public string $name;
    public string $description;
    public int $monthlyPriceInDollars;
    public int $monthlyPriceInCents;
}
