<?php

declare(strict_types=1);

namespace JLSalinas\GithubSponsors;

use PHPUnit\Framework\TestCase;
use Mockery;

use Illuminate\Http\Client\PendingRequest;

class GithubApiTest extends TestCase
{
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

        $api = new GithubGraphApi('asdf', $client);

        $this->expectException(WrongApiResponseException::class);
        $response = $api->sponsorships();
    }

    protected static function response(string $name)
    {
        return json_decode(file_get_contents(__DIR__ . '/responses/api_' . $name . '.json'), true);
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

        $api = new GithubGraphApi('asdf', $client);

        $this->expectException(WrongApiResponseException::class);
        $response = $api->sponsorships();
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

        $api = new GithubGraphApi('asdf', $client);

        $this->expectException(WrongApiResponseException::class);
        $response = $api->sponsorships();
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

        $api = new GithubGraphApi('asdf', $client);

        $this->expectException(WrongApiResponseException::class);
        $response = $api->sponsorships();
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

        $api = new GithubGraphApi('asdf', $client);

        $this->expectException(WrongApiResponseException::class);
        $response = $api->sponsorships();
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

        $api = new GithubGraphApi('asdf', $client);

        $response = $api->sponsorships();
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

        $api = new GithubGraphApi('asdf', $client);

        $response = $api->sponsorships();
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

        $api = new GithubGraphApi('asdf', $client);

        $response = $api->sponsorships();
        $this->assertEquals(7, count($response));
    }
}
