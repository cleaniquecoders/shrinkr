[![Latest Version on Packagist](https://img.shields.io/packagist/v/cleaniquecoders/shrinkr.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/shrinkr)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/cleaniquecoders/shrinkr/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/cleaniquecoders/shrinkr/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/cleaniquecoders/shrinkr/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/cleaniquecoders/shrinkr/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/cleaniquecoders/shrinkr.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/shrinkr)

# Shrinkr

**Shrinkr** is a powerful Laravel package for shortening URLs with custom slugs, comprehensive analytics, health monitoring, and seamless integration.

Transform long URLs into short, shareable links with features like **custom slugs**, **click tracking**, **branded domains**, **expiry management**, and **health monitoring**.

---

## Features

✅ **URL Shortening** - Create short URLs with auto-generated or custom slugs
✅ **Analytics Tracking** - Track clicks, referrers, IP addresses, browsers, and devices
✅ **Branded Domains** - Use custom domains for your shortened URLs
✅ **Expiry Management** - Set expiration times for temporary links
✅ **Health Monitoring** - Automatically check if original URLs are still reachable
✅ **Event System** - React to URL access and expiry events
✅ **Flexible Logging** - Log to files or database
✅ **Rate Limiting** - Built-in middleware to prevent abuse
✅ **Artisan Commands** - Automate maintenance tasks

---

## Quick Start

### Installation

```bash
composer require cleaniquecoders/shrinkr
php artisan vendor:publish --tag="shrinkr-migrations"
php artisan migrate
php artisan vendor:publish --tag="shrinkr-config"
```

### Basic Usage

```php
use CleaniqueCoders\Shrinkr\Facades\Shrinkr;

// Shorten a URL
$shortUrl = Shrinkr::shorten('https://example.com/long-url', auth()->id());
// Result: https://yourdomain.com/s/abc123

// With custom slug
$shortUrl = Shrinkr::shorten('https://example.com', auth()->id(), [
    'custom_slug' => 'my-link',
]);
// Result: https://yourdomain.com/s/my-link

// With expiry (60 minutes)
$shortUrl = Shrinkr::shorten('https://example.com', auth()->id(), [
    'expiry_duration' => 60,
]);

// Resolve short URL to original
$originalUrl = Shrinkr::resolve('abc123');
```

---

## Documentation

For comprehensive documentation, see the [docs](docs/) directory:

- **[Getting Started](docs/01-getting-started/)** - Installation, configuration, and quick start
- **[Configuration](docs/02-configuration/)** - Complete configuration reference
- **[Usage](docs/03-usage/)** - Shortening, resolving, updating, and deleting URLs
- **[Features](docs/04-features/)** - Analytics, expiry, events, health monitoring
- **[API Reference](docs/05-api-reference/)** - Complete API documentation
- **[Development](docs/06-development/)** - Testing and contributing

### Key Documentation Pages

- [Installation Guide](docs/01-getting-started/01-installation.md)
- [Quick Start Guide](docs/01-getting-started/03-quick-start.md)
- [Configuration Reference](docs/02-configuration/01-complete-reference.md)
- [Shortening URLs](docs/03-usage/01-shortening-urls.md)
- [Analytics Tracking](docs/04-features/02-analytics.md)
- [URL Expiry](docs/04-features/01-url-expiry.md)
- [Health Monitoring](docs/04-features/04-health-monitoring.md)

---

---

## **Usage**

Here’s a basic usage example using the Shrinkr facade, actions, and events.

---

### **1. Shorten a URL**

You can shorten a URL with or without a **custom slug** and **expiry duration**.

```php
use CleaniqueCoders\Shrinkr\Facades\Shrinkr;

// Shorten a URL with default random slug
$shortUrl = Shrinkr::shorten('https://example.com/long-url', auth()->id());
echo $shortUrl; // Outputs: https://yourdomain.com/abc123

// Shorten a URL with a custom slug and expiry duration (e.g., 60 minutes)
$shortUrlWithCustomSlug = Shrinkr::shorten('https://example.com/long-url', auth()->id(), [
    'custom_slug' => 'my-slug',
    'expiry_duration' => 60, // Expires in 60 minutes
]);
echo $shortUrlWithCustomSlug; // Outputs: https://yourdomain.com/my-slug
```

---

### **2. Retrieve the Original URL**

Use the `resolve()` method to retrieve the **original URL** from a shortened one.

```php
$originalUrl = Shrinkr::resolve('abc123');
echo $originalUrl; // Outputs: https://example.com/long-url
```

When the URL is accessed, the **`UrlAccessed` event** will be dispatched automatically to track the visit.

---

### **3. Update an Existing Short URL**

You can update an existing short URL with a new **custom slug** or **expiry duration**.

```php
use CleaniqueCoders\Shrinkr\Models\Url;
use CleaniqueCoders\Shrinkr\Facades\Shrinkr;

$url = Url::find(1);

// Update the short URL with a new slug and expiry duration
$updatedUrl = Shrinkr::update($url, [
    'custom_slug' => 'updated-slug',
    'expiry_duration' => 120, // 2 hours from now
]);
echo $updatedUrl->shortened_url; // Outputs: https://yourdomain.com/updated-slug
```

---

### **4. Event Handling**

#### **UrlAccessed Event**

The **`UrlAccessed` event** is dispatched whenever a shortened URL is accessed. You can listen for this event to **log analytics or trigger notifications**.

**Example: Log URL Access in a Listener**

```php
namespace CleaniqueCoders\Shrinkr\Listeners;

use CleaniqueCoders\Shrinkr\Events\UrlAccessed;
use Illuminate\Support\Facades\Log;

class LogUrlAccess
{
    public function handle(UrlAccessed $event)
    {
        $url = $event->url;

        Log::info('URL accessed', [
            'url_id' => $url->id,
            'shortened_url' => $url->shortened_url,
            'accessed_at' => now(),
        ]);
    }
}
```

#### **UrlExpired Event**

The **`UrlExpired` event** is dispatched when a URL has expired, either through a scheduled check or upon access. You can listen to this event to **notify the user or perform other actions**.

**Example: Notify on URL Expiry in a Listener**

```php
namespace CleaniqueCoders\Shrinkr\Listeners;

use CleaniqueCoders\Shrinkr\Events\UrlExpired;
use Illuminate\Support\Facades\Log;

class NotifyUrlExpired
{
    public function handle(UrlExpired $event)
    {
        $url = $event->url;

        Log::warning('URL expired', [
            'url_id' => $url->id,
            'shortened_url' => $url->shortened_url,
            'expired_at' => now(),
        ]);

        // Optionally, notify the user about the expired URL.
    }
}
```

---

### **5. Automatically Expire URLs**

If you’ve set an **expiry duration**, the URL will be marked as expired once that time has passed. You can also run the **expiry command** manually or schedule it.

**Run the Expiry Command Manually:**

```bash
php artisan shrinkr:check-expiry
```

**Schedule the Expiry Command:**

In your **`app/Console/Kernel.php`**:

```php
$schedule->command('shrinkr:check-expiry')->hourly();
```

---

### **6. Monitor URL Health**

The **Link Health Monitoring** feature allows you to **check if URLs are reachable** and mark them as **active or expired**.

#### **Check Health Action**

Use the `CheckUrlHealthAction` to **manually check the health** of a specific URL.

```php
use CleaniqueCoders\Shrinkr\Actions\CheckUrlHealthAction;
use CleaniqueCoders\Shrinkr\Models\Url;

$url = Url::find(1); // Retrieve URL instance

$action = new CheckUrlHealthAction();
$isHealthy = $action->execute($url);

if ($isHealthy) {
    echo "URL is active.";
} else {
    echo "URL is expired.";
}
```

#### **Check Health Command**

Use the Artisan command to **check the health of all URLs** in bulk.

```bash
php artisan shrinkr:check-health
```

This command will:

1. **Check the status** of all URLs.
2. **Mark expired URLs** and dispatch the `UrlExpired` event.
3. **Provide real-time output** on the status of each URL.

Example output:

```
URL abc123 is now marked as active.
URL xyz456 is now marked as expired.
URL health check completed.
```

#### **Schedule the Health Check Command**

You can **automatically run the health check** at regular intervals using Laravel’s scheduler.

In your **`app/Console/Kernel.php`**:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('shrinkr:check-health')->hourly();
}
```

This will ensure that all URLs are **continuously monitored** and marked as expired when necessary.

---

### **7. Redirect Tracking**

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

### **8. Routing**

You can configure custom domain for the URL by configuring:

```php
'domain' => 'bite.ly',
```

You may also change the middleware or add new one by configuring:

```php
'middleware' => ['auth', 'verified', 'throttle:600,1']
```

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
