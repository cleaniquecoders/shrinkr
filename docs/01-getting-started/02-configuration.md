# Basic Configuration

This guide covers the essential configuration options to get Shrinkr up and running.

## Configuration File

After installation, your configuration file is located at `config/shrinkr.php`.

## Essential Settings

### URL Prefix

The prefix used for all shortened URLs:

```php
'prefix' => 's',
```

With this setting, shortened URLs will be accessed at `https://yourdomain.com/s/{slug}`.

### Domain Constraint

Bind shortened URLs to a specific domain:

```php
'domain' => null, // Works on any domain
// or
'domain' => 'bite.ly', // Only works on bite.ly
```

Set to `null` to allow URLs to work on any domain.

### Middleware

Configure middleware for URL redirection routes:

```php
'middleware' => ['throttle:60,1'],
```

Default rate limit is 60 requests per minute. You can add additional middleware:

```php
'middleware' => ['throttle:60,1', 'auth', 'verified'],
```

### Logger

Choose how redirect events are logged:

```php
// Log to file (default)
'logger' => \CleaniqueCoders\Shrinkr\Actions\Logger\LogToFile::class,

// Log to database
'logger' => \CleaniqueCoders\Shrinkr\Actions\Logger\LogToDatabase::class,
```

## Model Configuration

Customize the models used by Shrinkr:

```php
'models' => [
    'url' => \CleaniqueCoders\Shrinkr\Models\Url::class,
    'redirect_log' => \CleaniqueCoders\Shrinkr\Models\RedirectLog::class,
],
```

You can extend these models with your own implementations.

## Route Configuration

### Route Name

The named route for URL redirection:

```php
'route-name' => 'shrnkr.redirect',
```

This allows you to reference the route by name:

```php
route('shrnkr.redirect', ['slug' => 'abc123']);
```

### Redirect Controller

Customize the controller handling redirects:

```php
'controller' => \CleaniqueCoders\Shrinkr\Http\Controllers\RedirectController::class,
```

## Quick Configuration Examples

### Example 1: Custom Domain with Rate Limiting

```php
return [
    'prefix' => 'go',
    'domain' => 'mysite.com',
    'middleware' => ['throttle:100,1'],
    'logger' => \CleaniqueCoders\Shrinkr\Actions\Logger\LogToDatabase::class,
];
```

Shortened URLs: `https://mysite.com/go/{slug}`

### Example 2: Simple Setup with File Logging

```php
return [
    'prefix' => 's',
    'domain' => null,
    'middleware' => ['throttle:60,1'],
    'logger' => \CleaniqueCoders\Shrinkr\Actions\Logger\LogToFile::class,
];
```

Shortened URLs: `https://yourdomain.com/s/{slug}`

## Next Steps

With configuration complete, you're ready to:

- [Quick Start](03-quick-start.md) - Create your first shortened URL
- [Advanced Configuration](../02-configuration/README.md) - Explore all configuration options
