<?php

declare(strict_types=1);

namespace JLSalinas\GithubSponsors;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;

// to do: change implementation to use interfaces Psr\Http\Client\ClientInterface
// and Psr\Http\Message\RequestInterface
// https://www.php-fig.org/psr/psr-18/ -- composer require psr/http-client
// https://www.php-fig.org/psr/psr-7/ -- composer require psr/http-message
// implementation: https://github.com/ricardofiorani/guzzle-psr18-adapter
// how laravel sets the token:
//   https://github.com/illuminate/http/blob/f61ecfef0088df838bc9a84454b9ba3b9e7bc1d8/Client/PendingRequest.php#L317

/**
 * Class GithubGraphApi
 * @package JLSalinas\GithubSponsors
 */
class GithubGraphApi
{
    protected string $token;
    protected PendingRequest $http_client;

    /**
     * GithubGraphApi constructor.
     * @param string $token GitHub API auth token
     * @param PendingRequest|null $request The HTTP client to perform the API call.
     */
    public function __construct(string $token, ?PendingRequest $request = null)
    {
        $this->token = $token;
        $this->http_client = $request ?? new PendingRequest;
    }

    /**
     * @return array
     * @throws WrongApiResponseException
     */
    public function sponsorships(): array
    {
        return static::fetchAllSponsorships($this->http_client, $this->token);
    }

    /**
     * Fetch all sponsorships of the account linked to a given auth token.
     * Code adapted from: https://github.com/spatie/spatie.be/blob/70614d5c8424f9178315f1d8e6690b0745993fad/app/Services/GitHub/GitHubGraphApi.php
     * @param PendingRequest $http_client
     * @param string $token
     * @param array $currentSponsorships Used internally for pagination.
     * @param null $afterCursor Used internally for pagination.
     * @return array Array of SponsorshipData.
     * @throws WrongApiResponseException
     */
    protected static function fetchAllSponsorships(
        PendingRequest $http_client,
        string $token,
        array $currentSponsorships = [],
        $afterCursor = null
    ): array {
        $afterCursor = json_encode($afterCursor);

        $response = $http_client
            ->withToken($token)
            ->post('https://api.github.com/graphql', [
                'query' => <<<EOT
                {
                    viewer {
                        name
                        login
                        sponsorshipsAsMaintainer(after: {$afterCursor}, first: 100, includePrivate: true) {
                            nodes {
                                id
                                tier {
                                    id
                                    name
                                    description
                                    monthlyPriceInDollars
                                    monthlyPriceInCents
                                }
                                sponsorEntity {
                                    ...on User {
                                        id
                                        name
                                        login
                                        email
                                        url
                                        avatarUrl
                                    }
                                    ...on Organization {
                                        id
                                        name
                                        login
                                        email
                                        url
                                        avatarUrl
                                    }
                                }
                                createdAt
                            }
                            pageInfo {
                                hasNextPage
                                endCursor
                            }
                        }
                    }
                }
EOT,
            ]);

        // some validation
        if (!$response) {
            throw WrongApiResponseException::isNull();
        } elseif (!Arr::get($response, 'data')) {
            throw WrongApiResponseException::noData();
        } elseif (!Arr::get($response, 'data.viewer')) {
            throw WrongApiResponseException::missingField('viewer');
        } elseif (!Arr::get($response, 'data.viewer.sponsorshipsAsMaintainer')) {
            throw WrongApiResponseException::missingField('viewer.sponsorshipsAsMaintainer');
        } elseif (Arr::get($response, 'data.viewer.sponsorshipsAsMaintainer.nodes') === null) {
            throw WrongApiResponseException::missingField('viewer.sponsorshipsAsMaintainer.nodes');
        }

        $currentSponsorships = array_merge(
            $currentSponsorships,
            Arr::get($response, 'data.viewer.sponsorshipsAsMaintainer.nodes')
        );

        // pagination
        $hasNextPage = Arr::get($response, 'data.viewer.sponsorshipsAsMaintainer.pageInfo.hasNextPage');
        $endCursor = Arr::get($response, 'data.viewer.sponsorshipsAsMaintainer.pageInfo.endCursor');

        if (! $hasNextPage) {
            return $currentSponsorships;
        }

        // go for next page of results
        return static::fetchAllSponsorships($http_client, $token, $currentSponsorships, $endCursor);
    }
}
