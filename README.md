# Bullhorn API

[![Latest Version on Packagist](https://img.shields.io/packagist/v/justijndepover/bullhorn-api.svg?style=flat-square)](https://packagist.org/packages/justijndepover/bullhorn-api)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/justijndepover/bullhorn-api.svg?style=flat-square)](https://packagist.org/packages/justijndepover/bullhorn-api)

PHP Client for the Bullhorn API

## Caution

This application is still in development and could implement breaking changes. Please use at your own risk.

## Installation

You can install the package with composer

```sh
composer require justijndepover/bullhorn-api
```

## Usage

Connecting to Bullhorn:
```php
// note the state param: this can be a random string. It's used as an extra layer of protection. Bullhorn will return this value when connecting.
$bullhorn = new Bullhorn(CLIENT_ID, CLIENT_SECRET, REDIRECT_URI, STATE);

// if you already possess authentication credentials, provide them:
$bullhorn->setAccessToken($accessToken);
$bullhorn->setRefreshToken($refreshToken);
$bullhorn->setTokenExpiresAt($expiresAt);
$bullhorn->setRestUrl($restUrl);
$bullhorn->setBHRestToken($BHRestToken);

// when one of the tokens (accesstoken, refreshtoken, BHRestToken) changes, a callback method is called. Giving you the opportunity to store them.
$bullhorn->setTokenUpdateCallback(function ($bullhorn) {
    // you should store away these tokens
    $bullhorn->getAccessToken();
    $bullhorn->getRefreshToken();
    $bullhorn->getTokenExpiresAt();
    $bullhorn->getRestUrl();
    $bullhorn->getBHRestToken();
});

// open the connection
$bullhorn->connect();
```

Your application is now connected. To start fetching data:
```php
$bullhorn->get('entity/Candidate/5059165');
```

## Security

If you find any security related issues, please open an issue or contact me directly at [justijndepover@gmail.com](justijndepover@gmail.com).

## Contribution

If you wish to make any changes or improvements to the package, feel free to make a pull request.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
