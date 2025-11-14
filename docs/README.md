# Shrinkr Documentation

Complete documentation for the Shrinkr URL shortener package for Laravel.

## Quick Links

- [Installation](01-getting-started/01-installation.md) - Get started quickly
- [Quick Start Guide](01-getting-started/03-quick-start.md) - Your first shortened URL
- [Configuration Reference](02-configuration/01-complete-reference.md) - All config options
- [API Documentation](07-api/01-getting-started.md) - RESTful API and integrations

## Documentation Structure

### 01. Getting Started

Learn how to install and configure Shrinkr.

- [Installation](01-getting-started/01-installation.md) - Install and set up Shrinkr
- [Configuration](01-getting-started/02-configuration.md) - Basic configuration
- [Quick Start](01-getting-started/03-quick-start.md) - Create your first URL

### 02. Configuration

Comprehensive configuration guides for customizing Shrinkr.

- [Complete Reference](02-configuration/01-complete-reference.md) - All configuration options
- [Logging](02-configuration/02-logging.md) - Configure analytics logging
- [Routing](02-configuration/03-routing.md) - URL routing and middleware
- [Models](02-configuration/04-models.md) - Extend and customize models

### 03. Usage

Practical guides for using Shrinkr in your application.

- [Shortening URLs](03-usage/01-shortening-urls.md) - Create shortened URLs
- [Resolving URLs](03-usage/02-resolving-urls.md) - Access shortened URLs
- [Updating URLs](03-usage/03-updating-urls.md) - Modify existing URLs
- [Deleting URLs](03-usage/04-deleting-urls.md) - Remove URLs
- [Working with Users](03-usage/05-working-with-users.md) - User-specific features

### 04. Features

Deep dives into Shrinkr's powerful features.

- [URL Expiry](04-features/01-url-expiry.md) - Temporary and time-limited URLs
- [Analytics](04-features/02-analytics.md) - Track clicks and visitor data
- [Events](04-features/03-events.md) - Listen to URL lifecycle events
- [Health Monitoring](04-features/04-health-monitoring.md) - Validate URL health
- [Middleware](04-features/05-middleware.md) - Customize request handling
- [Commands](04-features/06-commands.md) - Artisan commands for maintenance

### 05. API Reference

Complete API documentation for Shrinkr classes and interfaces.

- [Facades](05-api-reference/01-facades.md) - Shrinkr facade methods
- [Actions](05-api-reference/02-actions.md) - Action class reference
- [Models](05-api-reference/03-models.md) - Model properties and methods
- [Events](05-api-reference/04-events.md) - Event class reference
- [Exceptions](05-api-reference/05-exceptions.md) - Exception handling
- [Contracts](05-api-reference/06-contracts.md) - Interfaces and contracts

### 06. Development

Resources for testing, contributing, and maintaining Shrinkr.

- [Testing](06-development/01-testing.md) - Testing guidelines
- [Contributing](06-development/02-contributing.md) - How to contribute
- [Security](06-development/03-security.md) - Security best practices

### 07. API & Integrations

RESTful API, webhooks, and third-party integrations.

- [Getting Started](07-api/01-getting-started.md) - API setup and authentication
- [URL Endpoints](07-api/02-url-endpoints.md) - CRUD operations via API
- [Webhooks](07-api/03-webhooks.md) - Real-time event notifications
- [Notifications](07-api/04-notifications.md) - Slack, Discord, and email alerts

## Common Tasks

### Creating a Shortened URL

```php
use CleaniqueCoders\Shrinkr\Facades\Shrinkr;

$shortUrl = Shrinkr::shorten('https://example.com/long-url', auth()->id());
```

[Learn more →](03-usage/01-shortening-urls.md)

### With Custom Slug and Expiry

```php
$shortUrl = Shrinkr::shorten('https://example.com', auth()->id(), [
    'custom_slug' => 'my-link',
    'expiry_duration' => 60, // minutes
]);
```

[Learn more →](04-features/01-url-expiry.md)

### Viewing Analytics

```php
$url = Url::find(1);
$analytics = [
    'total_clicks' => $url->logs()->count(),
    'clicks_today' => $url->logs()->whereDate('created_at', today())->count(),
];
```

[Learn more →](04-features/02-analytics.md)

### Scheduling Maintenance

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('shrinkr:check-expiry')->hourly();
    $schedule->command('shrinkr:check-health')->daily();
}
```

[Learn more →](04-features/06-commands.md)

## Package Information

- **Package:** `cleaniquecoders/shrinkr`
- **License:** MIT
- **Repository:** [github.com/cleaniquecoders/shrinkr](https://github.com/cleaniquecoders/shrinkr)
- **Requirements:** PHP 8.2+, Laravel 10+

## Getting Help

- [GitHub Issues](https://github.com/cleaniquecoders/shrinkr/issues) - Report bugs or request features
- [GitHub Discussions](https://github.com/cleaniquecoders/shrinkr/discussions) - Ask questions
- [Security Policy](https://github.com/cleaniquecoders/shrinkr/security/policy) - Report security issues

## Contributing

We welcome contributions! See the [Contributing Guide](06-development/02-contributing.md) for details.

## Credits

- [Nasrul Hazim Bin Mohamad](https://github.com/nasrulhazim)
- [All Contributors](https://github.com/cleaniquecoders/shrinkr/contributors)

## License

Shrinkr is open-sourced software licensed under the [MIT license](../LICENSE.md).
