<?php

declare(strict_types=1);

namespace JLSalinas\GithubSponsors;

use GuzzleHttp\Psr7\Response;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Http\Client\PendingRequest;
use PHPUnit\Framework\TestCase;
use Mockery;

use Psr\SimpleCache\CacheInterface as Cache;
use Illuminate\Support\Collection;

class SponsorshipsTest extends TestCase
{
    protected Repository $cache;
    protected string $wrong_token;

    public function setUp(): void
    {
        $this->cache = new Repository(new ArrayStore);
        $this->wrong_token = 'super-secret-auth-token';
        Mockery::close();
    }

    public function tearDown(): void
    {
        $this->cache->clear();
        Mockery::close();
    }

    protected static function response(string $name)
    {
        $json = file_get_contents(__DIR__ . '/responses/dataset_' . $name . '.json');
        return json_decode($json, true);
    }

    public function testThrowsWithMissingToken()
    {
        $gh_sponsorships = new GithubSponsorships($this->cache, new GithubGraphApi);
        $this->expectException(MissingTokenException::class);
        $gh_sponsorships->all();
    }

    public function testThrowsWithWrongTokenSet()
    {
        $gh_sponsorships = new GithubSponsorships($this->cache, new GithubGraphApi);
        $this->expectException(UnauthorizedException::class);
        $gh_sponsorships->withToken($this->wrong_token)->all();
    }

    public function _testThrowsWithWrongTokenWith()
    {
        $gh_sponsorships = new GithubSponsorships($this->cache, new GithubGraphApi);
        $this->expectException(UnauthorizedException::class);
        $gh_sponsorships->setToken()->all();
    }

    public function testGetsEmptyNotCached()
    {
        $cache = Mockery::mock(Cache::class);
        $cache->shouldReceive('get')
            ->once()
            ->andReturn(null);
        $cache->shouldReceive('set')
            ->once()
            ->andReturn(null);

        $api = Mockery::mock(GithubGraphApi::class);
        $api->shouldReceive('sponsorships')
            ->once()
            ->andReturn([]);

        $GhSponsorships = new GithubSponsorships($cache, $api);

        $data = $GhSponsorships->withToken($this->wrong_token)->all();
        $this->assertEquals(0, count($data));
    }

    public function testGetsEmptyCached()
    {
        $cache = Mockery::mock(Cache::class);
        $cache->shouldReceive('get')
            ->once()
            ->andReturn(new Collection([]));
        $cache->shouldReceive('set')
            ->never();

        $api = Mockery::mock(GithubGraphApi::class);
        $api->shouldReceive('sponsorships')
            ->never();

        $GhSponsorships = new GithubSponsorships($cache, $api);

        $data = $GhSponsorships->withToken($this->wrong_token)->all();
        $this->assertEquals(0, count($data));
    }

    public function testGetsOnePageNotCached()
    {
        $cache = Mockery::mock(Cache::class);
        $cache->shouldReceive('get')
            ->once()
            ->andReturn(null);
        $cache->shouldReceive('set')
            ->once()
            ->andReturn(null);

        $api = Mockery::mock(GithubGraphApi::class);
        $api->shouldReceive('sponsorships')
            ->once()
            ->andReturn(self::response('ok_seven'));

        $GhSponsorships = new GithubSponsorships($cache, $api);

        $data = $GhSponsorships->withToken($this->wrong_token)->all();
        $this->assertEquals(7, count($data));
    }

    public function testGetsOnePageCached()
    {
        $cache = Mockery::mock(Cache::class);
        $cache->shouldReceive('get')
            ->once()
            ->andReturn(GithubSponsorships::apiToDtoCollection(self::response('ok_seven')));
        $cache->shouldReceive('set')
            ->never();

        $api = Mockery::mock(GithubGraphApi::class);
        $api->shouldReceive('sponsorships')
            ->never();

        $GhSponsorships = new GithubSponsorships($cache, $api);

        $data = $GhSponsorships->withToken($this->wrong_token)->all();
        $this->assertEquals(7, count($data));
    }

    public function testGetBySponsorId()
    {
        $cache = Mockery::mock(Cache::class);
        $cache->shouldReceive('get')
            ->times(5)
            ->andReturn(GithubSponsorships::apiToDtoCollection(self::response('ok_seven')));
        $cache->shouldReceive('set')
            ->never();

        $api = Mockery::mock(GithubGraphApi::class);
        $api->shouldReceive('sponsorships')
            ->never();

        $GhSponsorships = (new GithubSponsorships($cache, $api))
            ->setToken($this->wrong_token);

        $data = $GhSponsorships->getBySponsorId(0);
        $this->assertEquals(null, $data);
        $data = $GhSponsorships->getBySponsorId(1);
        $this->assertEquals(null, $data);

        $data = $GhSponsorships->getBySponsorId(1001);
        $this->assertEquals(true, is_a($data, SponsorshipData::class));
        $this->assertEquals(true, is_a($data->tier, TierData::class));
        $this->assertEquals(true, is_a($data->sponsor, SponsorData::class));
        $this->assertEquals(1, $data->id);
        $this->assertEquals('Supercool', $data->tier->name);
        $this->assertEquals('mike001', $data->sponsor->login);

        $data = $GhSponsorships->getBySponsorId(1007);
        $this->assertEquals(true, is_a($data, SponsorshipData::class));
        $this->assertEquals(true, is_a($data->tier, TierData::class));
        $this->assertEquals(true, is_a($data->sponsor, SponsorData::class));
        $this->assertEquals(7, $data->id);
        $this->assertEquals('VIP', $data->tier->name);
        $this->assertEquals('arnoldt800', $data->sponsor->login);

        $data = $GhSponsorships->getBySponsorId(1008);
        $this->assertEquals(null, $data);
    }

    public function testGetBySponsorLogin()
    {
        $cache = Mockery::mock(Cache::class);
        $cache->shouldReceive('get')
            ->times(2)
            ->andReturn(GithubSponsorships::apiToDtoCollection(self::response('ok_seven')));
        $cache->shouldReceive('set')
            ->never();

        $api = Mockery::mock(GithubGraphApi::class);
        $api->shouldReceive('sponsorships')
            ->never();

        $GhSponsorships = (new GithubSponsorships($cache, $api))
            ->setToken($this->wrong_token);

        $data = $GhSponsorships->getBySponsorLogin('asdf');
        $this->assertEquals(null, $data);

        $data = $GhSponsorships->getBySponsorLogin('james007');
        $this->assertEquals(true, is_a($data, SponsorshipData::class));
        $this->assertEquals(true, is_a($data->tier, TierData::class));
        $this->assertEquals(true, is_a($data->sponsor, SponsorData::class));
        $this->assertEquals(3, $data->id);
        $this->assertEquals('VIP', $data->tier->name);
        $this->assertEquals(1003, $data->sponsor->id);
        $this->assertEquals('james@example.com', $data->sponsor->email);
    }

    public function testGetBySponsorEmail()
    {
        $cache = Mockery::mock(Cache::class);
        $cache->shouldReceive('get')
            ->times(2)
            ->andReturn(GithubSponsorships::apiToDtoCollection(self::response('ok_seven')));
        $cache->shouldReceive('set')
            ->never();

        $api = Mockery::mock(GithubGraphApi::class);
        $api->shouldReceive('sponsorships')
            ->never();

        $GhSponsorships = (new GithubSponsorships($cache, $api))
            ->setToken($this->wrong_token);

        $data = $GhSponsorships->getBySponsorEmail('asdf@example.com');
        $this->assertEquals(null, $data);

        $data = $GhSponsorships->getBySponsorEmail('anna23@example.com');
        $this->assertEquals(true, is_a($data, SponsorshipData::class));
        $this->assertEquals(true, is_a($data->tier, TierData::class));
        $this->assertEquals(true, is_a($data->sponsor, SponsorData::class));
        $this->assertEquals(5, $data->id);
        $this->assertEquals('Supercool', $data->tier->name);
        $this->assertEquals(1005, $data->sponsor->id);
        $this->assertEquals('anna23', $data->sponsor->login);
    }
}
