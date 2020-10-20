<?php

namespace JLSalinas\GithubSponsors\DTOs;

use DateTime;
use Spatie\DataTransferObject\DataTransferObject;
use Carbon\Carbon;

class SponsorshipData extends DataTransferObject
{
    public int $id;
    public string $createdAt;
    public TierData $tier;
    public SponsorData $sponsor;
}
