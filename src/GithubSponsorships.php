<?php

declare(strict_types=1);

namespace JLSalinas\GithubSponsors;

use Illuminate\Cache\NullStore;
use Illuminate\Cache\Repository;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;

use Psr\SimpleCache\CacheInterface as Cache;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class GithubSponsorships
 * @package JLSalinas\GithubSponsors
 */
class GithubSponsorships
{
    protected ?string $token = null;
    protected ?string $temp_token = null;

    protected GithubGraphApi $api;

    protected Cache $cache;
    protected int $cacheMinutes;
    protected bool $ignore_cache = false;

    protected Collection $sponsorships;

    /**
     * CheckGithubSponsor constructor.
     * @param Cache $cache
     * @param GithubGraphApi $api
     * @param int $cacheMinutes
     */
    public function __construct(Cache $cache = null, int $cacheMinutes = 60, GithubGraphApi $api = null)
    {
        $this->cache = $cache ?? new Repository(new NullStore);
        $this->api = $api ?? new GithubGraphApi;
        $this->cacheMinutes = $cacheMinutes;
    }

    /**
     * @return bool
     */
    public function clearCache(): bool
    {
        return $this->cache->clear();
    }

    /**
     * @param string $token
     * @return $this
     */
    public function setToken(string $token): self
    {
        $this->token = $token;
        return $this;
    }

    /**
     * @param string $token
     * @return $this
     */
    public function withToken(string $token): self
    {
        $this->temp_token = $token;
        return $this;
    }

    /**
     * @return $this
     */
    public function ignoringCache(): self
    {
        $this->ignore_cache = true;
        return $this;
    }

    /**
     * @return array
     * @throws FailedApiConnectionException
     * @throws MissingTokenException
     * @throws UnauthorizedException
     * @throws WrongApiResponseException
     */
    public function all(): array
    {
        $this->fetchSponsorships();
        return $this->sponsorships->all();
    }

    /**
     * @param int $id
     * @return SponsorshipData|null
     * @throws FailedApiConnectionException
     * @throws MissingTokenException
     * @throws UnauthorizedException
     * @throws WrongApiResponseException
     */
    public function getBySponsorId(int $id): ?SponsorshipData
    {
        $this->fetchSponsorships();
        return $this->sponsorships->where('sponsor.id', $id)->first();
    }

    /**
     * @param string $login
     * @return SponsorshipData|null
     * @throws FailedApiConnectionException
     * @throws MissingTokenException
     * @throws UnauthorizedException
     * @throws WrongApiResponseException
     */
    public function getBySponsorLogin(string $login): ?SponsorshipData
    {
        $this->fetchSponsorships();
        return $this->sponsorships->where('sponsor.login', $login)->first();
    }

    /**
     * @param string $email
     * @return SponsorshipData|null
     * @throws FailedApiConnectionException
     * @throws MissingTokenException
     * @throws UnauthorizedException
     * @throws WrongApiResponseException
     */
    public function getBySponsorEmail(string $email): ?SponsorshipData
    {
        $this->fetchSponsorships();
        return $this->sponsorships->where('sponsor.email', $email)->first();
    }

    /**
     * @throws FailedApiConnectionException
     * @throws UnauthorizedException
     * @throws WrongApiResponseException
     * @throws MissingTokenException
     */
    protected function fetchSponsorships(): void
    {
        $token = $this->temp_token ?? $this->token ?? null;
        $this->temp_token = null;

        if (!$token) {
            throw new MissingTokenException;
        }

        try {
            $cache_key = md5('github-sponsorships-' . $token);

            if ($this->ignore_cache) {
                    $this->cache->delete($cache_key);
            }

            $data = $this->cache->get($cache_key, null);

            if (!$data) {
                $data = $this->api->sponsorships($token);
                $data = static::apiToDtoCollection($data);

                $this->cache->set(
                    $cache_key,
                    $data,
                    Carbon::now()->addMinutes($this->cacheMinutes)
                );
            }

            $this->sponsorships = $data;
        } catch (InvalidArgumentException $e) {
            // do nothing
        }
    }

    /**
     * @param array $rawData
     * @return Collection
     */
    public static function apiToDtoCollection(array $rawData): Collection
    {
        return new Collection(array_map('self::sponsorshipFromApiData', $rawData));
    }

    /**
     * @param $data
     * @return SponsorshipData
     */
    protected static function sponsorshipFromApiData($data): SponsorshipData
    {
        $data['sponsor'] = $data['sponsorEntity'];
        unset($data['sponsorEntity']);
        return new SponsorshipData($data);
    }
}
