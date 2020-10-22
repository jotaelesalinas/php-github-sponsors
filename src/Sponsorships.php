<?php

declare(strict_types=1);

namespace JLSalinas\GithubSponsors;

use JLSalinas\GithubSponsors\Support\GithubGraphApi;
use JLSalinas\GithubSponsors\DTOs\SponsorshipData;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Psr\SimpleCache\CacheInterface as Cache;

class Sponsorships
{
    protected GithubGraphApi $api;
    protected int $cacheMinutes;
    protected Cache $cache;

    protected Collection $sponsorships;

    /**
     * CheckGithubSponsor constructor.
     * @param Cache $cache
     * @param GithubGraphApi $api
     * @param int $cacheMinutes
     */
    public function __construct(Cache $cache, GithubGraphApi $api, int $cacheMinutes = 60)
    {
        $this->api = $api;
        $this->cacheMinutes = $cacheMinutes;
        $this->cache = $cache;
    }

    protected function fetchSponsorships(bool $refreshCache = false): void
    {
        if ($refreshCache) {
            $this->sponsorships = static::apiToDtoCollection($this->api->fetchAllSponsorships());
            $this->cache->set(
                'github-sponsorships',
                $this->sponsorships,
                Carbon::now()->addMinutes($this->cacheMinutes)
            );
        } else {
            $data = $this->cache->get('github-sponsorships', null);
            if (!$data) {
                $data = static::apiToDtoCollection($this->api->fetchAllSponsorships());
                $this->cache->set(
                    'github-sponsorships',
                    $data,
                    Carbon::now()->addMinutes($this->cacheMinutes)
                );
            }
            $this->sponsorships = $data;
        }
    }

    public static function apiToDtoCollection(array $rawData): Collection
    {
        return new Collection(array_map('self::sponsorshipFromApiData', $rawData));
    }

    public function all(bool $refreshCache = false): array
    {
        $this->fetchSponsorships($refreshCache);
        return $this->sponsorships->all();
    }

    public function getBySponsorId(int $id, bool $refreshCache = false): ?SponsorshipData
    {
        $this->fetchSponsorships($refreshCache);
        return $this->sponsorships->where('sponsor.id', $id)->first();
    }

    public function getBySponsorLogin(string $login, bool $refreshCache = false): ?SponsorshipData
    {
        $this->fetchSponsorships($refreshCache);
        return $this->sponsorships->where('sponsor.login', $login)->first();
    }

    public function getBySponsorEmail(string $email, bool $refreshCache = false): ?SponsorshipData
    {
        $this->fetchSponsorships($refreshCache);
        return $this->sponsorships->where('sponsor.email', $email)->first();
    }

    protected static function sponsorshipFromApiData($data): SponsorshipData
    {
        $data['sponsor'] = $data['sponsorEntity'];
        unset($data['sponsorEntity']);
        return new SponsorshipData($data);
    }
}
