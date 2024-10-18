[![Latest Version on Packagist](https://img.shields.io/packagist/v/cleaniquecoders/shrinkr.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/shrinkr) [![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/cleaniquecoders/shrinkr/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/cleaniquecoders/shrinkr/actions?query=workflow%3Arun-tests+branch%3Amain) [![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/cleaniquecoders/shrinkr/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/cleaniquecoders/shrinkr/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain) [![Total Downloads](https://img.shields.io/packagist/dt/cleaniquecoders/shrinkr.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/shrinkr)

---

# Shrinkr

Shrinkr is a Laravel package for shortening URLs, offering custom slugs, analytics, branded domains, and API integration.

Shrinkr makes it easy to convert long URLs into short, shareable links. With features like **custom slugs**, **click analytics**, **branded domains**, and seamless **API support**, Shrinkr empowers users to manage URLs effectively.

---

## Installation

Install the package via Composer:

```bash
composer require cleaniquecoders/shrinkr
```

Publish the migration files and migrate:

```bash
php artisan vendor:publish --tag="shrinkr-migrations"
php artisan migrate
```

Publish the config file:

```bash
php artisan vendor:publish --tag="shrinkr-config"
```

The published configuration file (`config/shrinkr.php`) will allow you to customize settings.

Optionally, publish the views:

```bash
php artisan vendor:publish --tag="shrinkr-views"
```

---

## Usage

Here’s a basic usage example:

```php
use CleaniqueCoders\Shrinkr\Facades\Shrinkr;

// Shorten a URL
$shortUrl = Shrinkr::shorten('https://example.com/long-url');
echo $shortUrl; // Outputs: https://yourdomain.com/abc123

// Retrieve the original URL
$originalUrl = Shrinkr::resolve('abc123');
echo $originalUrl; // Outputs: https://example.com/long-url
```

---

## Testing

Run the tests using:

```bash
composer test
```

---

## Changelog

Refer to the [CHANGELOG](CHANGELOG.md) for the latest updates and changes.

---

## Contributing

We welcome contributions! Please see [CONTRIBUTING](CONTRIBUTING.md) for guidelines.

---

## Security Vulnerabilities

Report security vulnerabilities by reviewing [our security policy](../../security/policy).

---

## Credits

- [Nasrul Hazim Bin Mohamad](https://github.com/nasrulhazim)
- [All Contributors](../../contributors)

---

## License

Shrinkr is open-sourced software licensed under the [MIT license](LICENSE.md).
