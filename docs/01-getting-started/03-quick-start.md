# Quick Start

Get started with Shrinkr in minutes by creating your first shortened URL.

## Your First Shortened URL

### Basic URL Shortening

Create a shortened URL with an auto-generated slug:

```php
use CleaniqueCoders\Shrinkr\Facades\Shrinkr;

$shortUrl = Shrinkr::shorten('https://example.com/very-long-url', auth()->id());
echo $shortUrl; // Outputs: https://yourdomain.com/s/abc123
```

### Custom Slug

Create a shortened URL with a custom slug:

```php
$shortUrl = Shrinkr::shorten('https://example.com/long-url', auth()->id(), [
    'custom_slug' => 'my-promo',
]);
echo $shortUrl; // Outputs: https://yourdomain.com/s/my-promo
```

### With Expiry

Create a URL that expires after a specified duration:

```php
$shortUrl = Shrinkr::shorten('https://example.com/limited-offer', auth()->id(), [
    'custom_slug' => 'sale2024',
    'expiry_duration' => 60, // Expires in 60 minutes
]);
```

## Resolving URLs

Retrieve the original URL from a shortened slug:

```php
$originalUrl = Shrinkr::resolve('abc123');
echo $originalUrl; // Outputs: https://example.com/very-long-url
```

When accessed, this automatically:

- Tracks the visit (IP, browser, referrer, etc.)
- Dispatches the `UrlAccessed` event
- Redirects to the original URL

## Testing Your Setup

### 1. Create a Test URL

In your Laravel Tinker or a test controller:

```php
use CleaniqueCoders\Shrinkr\Facades\Shrinkr;

$url = Shrinkr::shorten('https://github.com', 1, [
    'custom_slug' => 'github',
]);
```

### 2. Access the Shortened URL

Visit `https://yourdomain.com/s/github` in your browser. You should be redirected to GitHub.

### 3. Check Analytics

If using database logging, check the `redirect_logs` table:

```php
use CleaniqueCoders\Shrinkr\Models\RedirectLog;

$logs = RedirectLog::latest()->take(5)->get();
dd($logs);
```

## Common Use Cases

### Marketing Campaigns

```php
$campaignUrl = Shrinkr::shorten('https://mysite.com/summer-sale', auth()->id(), [
    'custom_slug' => 'summer2024',
    'expiry_duration' => 43200, // 30 days
]);
```

### Social Media Sharing

```php
$socialUrl = Shrinkr::shorten('https://mysite.com/blog/new-post', auth()->id(), [
    'custom_slug' => 'new-blog-post',
]);
```

### Temporary Links

```php
$tempUrl = Shrinkr::shorten('https://mysite.com/download/file.pdf', auth()->id(), [
    'expiry_duration' => 1440, // 24 hours
]);
```

## What's Next?

Now that you've created your first shortened URL, explore more features:

- [Usage Guide](../03-usage/README.md) - Detailed usage examples
- [Features](../04-features/README.md) - Analytics, health monitoring, events
- [Configuration](../02-configuration/README.md) - Advanced configuration options
