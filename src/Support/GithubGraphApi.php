<?php

declare(strict_types=1);

namespace JLSalinas\GithubSponsors\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use JLSalinas\GithubSponsors\Exceptions\WrongApiResponseException;

// https://github.com/illuminate/http/blob/f61ecfef0088df838bc9a84454b9ba3b9e7bc1d8/Client/PendingRequest.php#L317

class GithubGraphApi
{
    protected string $token;
    protected PendingRequest $http_client;

    /**
     * CheckGithubSponsor constructor.
     * @param string $token GitHub API auth token
     * @param PendingRequest|null $request The HTTP client to perform the API call.
     */
    public function __construct(string $token, ?PendingRequest $request = null)
    {
        $this->token = $token;
        $this->http_client = $request ?? new PendingRequest;
    }

    // code adapted from: https://github.com/spatie/spatie.be/blob/70614d5c8424f9178315f1d8e6690b0745993fad/app/Services/GitHub/GitHubGraphApi.php
    public function fetchAllSponsorships(array $currentSponsorships = [], $afterCursor = null): array
    {
        $afterCursor = json_encode($afterCursor);

        $response = $this->http_client
            ->withToken($this->token)
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
        return $this->fetchAllSponsorships($currentSponsorships, $endCursor);
    }
}
