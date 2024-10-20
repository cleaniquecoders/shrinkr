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

- [x] **Shorten URLs** with or without custom slugs.
- [x] **Analytics tracking**: Track clicks, referrers, IP addresses, and devices.
- [ ] **Branded domains**: Use custom domains for shortened URLs.
- [x] **Configurable logging**: Store logs in files or databases.
- [ ] **API support**: Programmatically shorten and resolve URLs.
- [ ] **Rate Limiting**: To prevent abuse (e.g., spamming requests to shorten URLs or resolve them).
- [ ] **Protected URLs**: Add password protection or other access restrictions to certain URLs, ensuring only authorized users can access the content.
- [ ] **QR Codes**: Provide users with QR codes for easy sharing of URLs, especially on mobile devices.
- [ ] **Link Health Monitoring**: Ensure that the original URLs are still reachable and valid & automatically disable or notify users if a link becomes broken or inactive.
- [x] **Event & Listeners**: Improve system decoupling by using events to trigger actions asynchronously (e.g., logging clicks, sending notifications).
- [x] **Expiry**: Allow expiry to be set - in minutes.

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
