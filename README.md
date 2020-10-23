# php-github-sponsors

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]

PSR-4 PHP package to retrieve Github sponsorships and
check whether a given user is a sponsor or not.

## Install

Via Composer

``` bash
$ composer require jotaelesalinas/php-github-sponsors
```

## Usage

Basic usage (no caching):

``` php
use JLSalinas\GithubSponsors\GithubSponsorships;

$token = 'super-secret-auth-token';

$gh_sponsorships = new GithubSponsorships()->setToken($token);

$sponsorships = $gh_sponsorships->all();
$num_sponsorships = count($sponsorships);

if ($num_sponsorships > 0) {
    echo sprintf("You have %d sponsor%s:\n", $num_sponsorships, $num_sponsorships > 1 ? 's' : '');
    var_dump($sponsorships);
} else {
    echo "You don't have any sponsor... yet!\n";
}

if (!$sponsorship = $gh_sponsorships->getBySponsorEmail('email@example.com')) {
    echo "User with email email@example.com is not a sponsor.\n";
} else {
    echo "User with email email@example.com is a sponsor!\n";
    var_dump($sponsorship);
    // $sponsorship includes information about the tier, the sponsor and creation date
}

// Also available:
// - getBySponsorLogin()
// - getBySponsorId() -- as returned by Oauth and API
```

Caching options and per-request token:

``` php
use JLSalinas\GithubSponsors\GithubSponsorships;
use JLSalinas\GithubSponsors\MissingTokenException;

use Illuminate\Cache\ArrayStore as CacheStore;
use Illuminate\Cache\Repository;

$token = 'super-secret-auth-token';

$cache = new Repository(new CacheStore);
// or, in Laravel: $cache = cache()->store();

$gh_sponsorships = new GithubSponsorships($cache, 60); // cache for 60 minutes

$sponsorships = $gh_sponsorships->withToken($token)->all();

try {
    $sponsorship = $gh_sponsorships->getBySponsorEmail('email@example.com');
} catch (MissingTokenException $e) {
    echo "The previous token was used only for the very next request.\n";
}

$sponsorship = $gh_sponsorships->withToken($token)
                               ->getBySponsorEmail('email@example.com');

if (!$sponsorship) {
    echo "User with email email@example.com is not a sponsor.";
} else {
    echo "User with email email@example.com is a sponsor!";
    var_dump($sponsorship);
    // $sponsorship includes information about the tier, the sponsor and creation date
}

// if we want to ignore the cache and force fetching data from GitHub:
$sponsorship = $gh_sponsorships->withToken($token)
                               ->ignoringCache()
                               ->getBySponsorEmail('email@example.com');
```

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## To do

- [ ] Change GithubGraphApi to use Psr\Http\Client\ClientInterface and
      Psr\Http\Message\RequestInterface instead of Illuminate\Http\Client\PendingRequest.
      Use guzzle as implementation.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email jlsalinas@example.com instead of using the issue tracker.

## Credits

- [Jose Luis Salinas][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/jotaelesalinas/php-github-sponsors.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/jotaelesalinas/php-github-sponsors/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/jotaelesalinas/php-github-sponsors.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/jotaelesalinas/php-github-sponsors.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/jotaelesalinas/php-github-sponsors.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/jotaelesalinas/php-github-sponsors
[link-travis]: https://travis-ci.org/jotaelesalinas/php-github-sponsors
[link-scrutinizer]: https://scrutinizer-ci.com/g/jotaelesalinas/php-github-sponsors/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/jotaelesalinas/php-github-sponsors
[link-downloads]: https://packagist.org/packages/jotaelesalinas/php-github-sponsors
[link-author]: https://github.com/jotaelesalinas
[link-contributors]: ../../contributors
