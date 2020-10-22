<?php

namespace JLSalinas\GithubSponsors;

use Spatie\DataTransferObject\DataTransferObject;

class SponsorshipData extends DataTransferObject
{
    public int $id;
    public string $createdAt;
    public TierData $tier;
    public SponsorData $sponsor;
}
