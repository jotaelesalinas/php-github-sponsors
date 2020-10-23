<?php

declare(strict_types=1);

namespace JLSalinas\GithubSponsors;

use GuzzleHttp\Psr7\Response;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use PHPUnit\Framework\TestCase;
use Mockery;

use Illuminate\Http\Client\PendingRequest;

class GithubApiTest extends TestCase
{
    protected string $wrong_token;

    public function setUp(): void
    {
        $this->wrong_token = 'super-secret-auth-token';
        Mockery::close();
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

    public function testThrowsOnNullResponse()
    {
        $client = Mockery::mock(PendingRequest::class);
        $client->shouldReceive('withToken')
            ->once()
            ->andReturn(Mockery::self());
        $client->shouldReceive('post')
            ->once()
            ->andReturn(null);

        $api = new GithubGraphApi($client);

        $this->expectException(FailedApiConnectionException::class);
        $response = $api->sponsorships($this->wrong_token);
    }

    protected static function response(string $name)
    {
        if (preg_match('/^\d+$/', $name)) {
            return new Response($name / 1);
        }

        $body = file_get_contents(__DIR__ . '/responses/api_' . $name . '.json');
        return new Response(200, [], $body);
    }

    public function testThrowsOnUnauthorized()
    {
        $client = Mockery::mock(PendingRequest::class);
        $client->shouldReceive('withToken')
            ->once()
            ->andReturn(Mockery::self());
        $client->shouldReceive('post')
            ->once()
            ->andReturn(self::response('401'));

        $api = new GithubGraphApi($client);

        $this->expectException(UnauthorizedException::class);
        $response = $api->sponsorships($this->wrong_token);
    }

    public function testThrowsOnConnectionError()
    {
        $client = Mockery::mock(PendingRequest::class);
        $client->shouldReceive('withToken')
            ->once()
            ->andReturn(Mockery::self());
        $client->shouldReceive('post')
            ->once()
            ->andReturn(self::response('501'));

        $api = new GithubGraphApi($client);

        $this->expectException(FailedApiConnectionException::class);
        $response = $api->sponsorships($this->wrong_token);
    }

    public function testThrowsOnMissingData()
    {
        $client = Mockery::mock(PendingRequest::class);
        $client->shouldReceive('withToken')
            ->once()
            ->andReturn(Mockery::self());
        $client->shouldReceive('post')
            ->once()
            ->andReturn(self::response('bad_missing_data'));

        $api = new GithubGraphApi($client);

        $this->expectException(WrongApiResponseException::class);
        $response = $api->sponsorships($this->wrong_token);
    }

    public function testThrowsOnMissingViewer()
    {
        $client = Mockery::mock(PendingRequest::class);
        $client->shouldReceive('withToken')
            ->once()
            ->andReturn(Mockery::self());
        $client->shouldReceive('post')
            ->once()
            ->andReturn(self::response('bad_missing_viewer'));

        $api = new GithubGraphApi($client);

        $this->expectException(WrongApiResponseException::class);
        $response = $api->sponsorships($this->wrong_token);
    }

    public function testThrowsOnMissingSponsorships()
    {
        $client = Mockery::mock(PendingRequest::class);
        $client->shouldReceive('withToken')
            ->once()
            ->andReturn(Mockery::self());
        $client->shouldReceive('post')
            ->once()
            ->andReturn(self::response('bad_missing_sponsorships'));

        $api = new GithubGraphApi($client);

        $this->expectException(WrongApiResponseException::class);
        $response = $api->sponsorships($this->wrong_token);
    }

    public function testThrowsOnMissingNodes()
    {
        $client = Mockery::mock(PendingRequest::class);
        $client->shouldReceive('withToken')
            ->once()
            ->andReturn(Mockery::self());
        $client->shouldReceive('post')
            ->once()
            ->andReturn(self::response('bad_missing_nodes'));

        $api = new GithubGraphApi($client);

        $this->expectException(WrongApiResponseException::class);
        $response = $api->sponsorships($this->wrong_token);
    }

    public function testGetsOnePage()
    {
        $client = Mockery::mock(PendingRequest::class);
        $client->shouldReceive('withToken')
            ->once()
            ->andReturn(Mockery::self());
        $client->shouldReceive('post')
            ->once()
            ->andReturn(self::response('one_page'));

        $api = new GithubGraphApi($client);

        $response = $api->sponsorships($this->wrong_token);
        $this->assertEquals(4, count($response));
    }

    public function testGetsOnePageEmpty()
    {
        $client = Mockery::mock(PendingRequest::class);
        $client->shouldReceive('withToken')
            ->once()
            ->andReturn(Mockery::self());
        $client->shouldReceive('post')
            ->once()
            ->andReturn(self::response('one_page_empty'));

        $api = new GithubGraphApi($client);

        $response = $api->sponsorships($this->wrong_token);
        $this->assertEquals(0, count($response));
    }

    public function testGetsTwoPages()
    {
        $client = Mockery::mock(PendingRequest::class);
        $client->shouldReceive('withToken')
            ->once()
            ->andReturn(Mockery::self());
        $client->shouldReceive('post')
            ->once()
            ->andReturn(self::response('two_pages_first'));
        $client->shouldReceive('withToken')
            ->once()
            ->andReturn(Mockery::self());
        $client->shouldReceive('post')
            ->once()
            ->andReturn(self::response('two_pages_second'));

        $api = new GithubGraphApi($client);

        $response = $api->sponsorships($this->wrong_token);
        $this->assertEquals(7, count($response));
    }
}
