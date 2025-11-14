# Complete Configuration Reference

This document provides a comprehensive reference for all configuration options in Shrinkr.

## Configuration File Location

The configuration file is located at `config/shrinkr.php` in your Laravel application.

## Configuration Options

### URL Prefix

**Key:** `prefix`
**Type:** `string`
**Default:** `'s'`

The prefix used for all shortened URLs.

```php
'prefix' => 's',
```

**Examples:**

```php
'prefix' => 's',      // URLs: https://yourdomain.com/s/abc123
'prefix' => 'go',     // URLs: https://yourdomain.com/go/abc123
'prefix' => 'link',   // URLs: https://yourdomain.com/link/abc123
```

### Domain Constraint

**Key:** `domain`
**Type:** `string|null`
**Default:** `null`

Bind shortened URLs to a specific domain. Set to `null` to work on any domain.

```php
'domain' => null,
```

**Examples:**

```php
'domain' => null,           // Works on any domain
'domain' => 'bite.ly',      // Only works on bite.ly
'domain' => 'short.io',     // Only works on short.io
```

### Route Name

**Key:** `route-name`
**Type:** `string`
**Default:** `'shrnkr.redirect'`

The name of the route that handles URL redirection.

```php
'route-name' => 'shrnkr.redirect',
```

**Usage:**

```php
// Generate URL using route name
$url = route('shrnkr.redirect', ['slug' => 'abc123']);
```

### Middleware

**Key:** `middleware`
**Type:** `array`
**Default:** `['throttle:60,1']`

Middleware applied to the Shrinkr routes.

```php
'middleware' => ['throttle:60,1'],
```

**Examples:**

```php
// Default rate limiting
'middleware' => ['throttle:60,1'],

// Higher rate limit
'middleware' => ['throttle:100,1'],

// With authentication
'middleware' => ['auth', 'throttle:60,1'],

// Multiple middleware
'middleware' => ['auth', 'verified', 'throttle:600,1'],
```

### Logger

**Key:** `logger`
**Type:** `string` (class name)
**Default:** `\CleaniqueCoders\Shrinkr\Actions\Logger\LogToFile::class`

The logger class used to log redirection events.

```php
'logger' => \CleaniqueCoders\Shrinkr\Actions\Logger\LogToFile::class,
```

**Available Loggers:**

```php
// Log to file
'logger' => \CleaniqueCoders\Shrinkr\Actions\Logger\LogToFile::class,

// Log to database
'logger' => \CleaniqueCoders\Shrinkr\Actions\Logger\LogToDatabase::class,
```

**Custom Logger:**

You can create your own logger by implementing the `CleaniqueCoders\Shrinkr\Contracts\Logger` interface.

### Redirect Controller

**Key:** `controller`
**Type:** `string` (class name)
**Default:** `\CleaniqueCoders\Shrinkr\Http\Controllers\RedirectController::class`

The controller that handles redirect logic for shortened URLs.

```php
'controller' => \CleaniqueCoders\Shrinkr\Http\Controllers\RedirectController::class,
```

**Custom Controller:**

```php
'controller' => \App\Http\Controllers\CustomRedirectController::class,
```

### Model Classes

**Key:** `models`
**Type:** `array`
**Default:** See below

The models used by Shrinkr.

```php
'models' => [
    'user' => \Illuminate\Contracts\Auth\Authenticatable::class,
    'url' => \CleaniqueCoders\Shrinkr\Models\Url::class,
    'redirect-log' => \CleaniqueCoders\Shrinkr\Models\RedirectLog::class,
],
```

**Custom Models:**

```php
'models' => [
    'user' => \App\Models\User::class,
    'url' => \App\Models\CustomUrl::class,
    'redirect-log' => \App\Models\CustomRedirectLog::class,
],
```

## Complete Configuration Example

Here's a complete configuration file with all options:

```php
<?php

use CleaniqueCoders\Shrinkr\Actions\Logger\LogToDatabase;
use CleaniqueCoders\Shrinkr\Http\Controllers\RedirectController;
use CleaniqueCoders\Shrinkr\Models\RedirectLog;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Contracts\Auth\Authenticatable;

return [
    'prefix' => 's',
    'controller' => RedirectController::class,
    'domain' => null,
    'route-name' => 'shrnkr.redirect',
    'middleware' => ['throttle:60,1'],
    'logger' => LogToDatabase::class,
    'models' => [
        'user' => Authenticatable::class,
        'url' => Url::class,
        'redirect-log' => RedirectLog::class,
    ],
];
```

## Environment-Specific Configuration

You can use environment variables for dynamic configuration:

```php
return [
    'prefix' => env('SHRINKR_PREFIX', 's'),
    'domain' => env('SHRINKR_DOMAIN', null),
    'middleware' => explode(',', env('SHRINKR_MIDDLEWARE', 'throttle:60,1')),
];
```

In your `.env` file:

```env
SHRINKR_PREFIX=go
SHRINKR_DOMAIN=mysite.com
SHRINKR_MIDDLEWARE=auth,throttle:100,1
```

## Next Steps

- [Logging Configuration](02-logging.md) - Detailed logging setup
- [Routing Configuration](03-routing.md) - Advanced routing options
- [Model Customization](04-models.md) - Extend Shrinkr models
