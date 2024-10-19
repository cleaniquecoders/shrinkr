Here’s the **updated README** to reflect the new logging feature and improvements.

---

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cleaniquecoders/shrinkr.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/shrinkr)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/cleaniquecoders/shrinkr/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/cleaniquecoders/shrinkr/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/cleaniquecoders/shrinkr/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/cleaniquecoders/shrinkr/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/cleaniquecoders/shrinkr.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/shrinkr)

---

# Shrinkr

Shrinkr is a Laravel package for shortening URLs, offering custom slugs, analytics, branded domains, and API integration.

Shrinkr makes it easy to convert long URLs into short, shareable links. With features like **custom slugs**, **click analytics**, **branded domains**, and seamless **API support**, Shrinkr empowers users to manage URLs effectively.

---

## Features

- **Shorten URLs** with or without custom slugs.
- **Analytics tracking**: Track clicks, referrers, IP addresses, and devices.
- **Branded domains**: Use custom domains for shortened URLs.
- **Configurable logging**: Store logs in files or databases.
- **API support**: Programmatically shorten and resolve URLs.

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

---

## Configuration

You can configure **logging options** by modifying the `config/shrinkr.php` file.

```php
return [
    'logger' => \CleaniqueCoders\Shrinkr\Actions\Logger\LogToFile::class, // Default logger
];
```

To log to a **database**, change the logger to:

```php
'logger' => \CleaniqueCoders\Shrinkr\Actions\Logger\LogToDatabase::class,
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

## Redirect Tracking

The redirect feature tracks detailed information such as:

- **IP address** of the visitor
- **Browser and OS** (via User-Agent parsing)
- **Referrer** (where the link was clicked)
- **Headers and query parameters**
- Optionally **store logs in a database** or log file

Example database log entry:

| url_id | ip         | browser | platform | referrer     | created_at          |
|----------|------------|---------|----------|--------------|---------------------|
| 1   | 192.168.1.1 | Chrome  | Windows  | google.com   | 2024-10-18 12:34:56 |

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

