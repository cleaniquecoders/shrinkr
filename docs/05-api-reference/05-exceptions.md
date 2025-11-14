# Exceptions

Shrinkr provides custom exceptions for handling specific error cases in URL shortening operations.

## Base Exception

### ShrinkrException

The base exception class for all Shrinkr-related errors.

#### Namespace

```php
use CleaniqueCoders\Shrinkr\Exceptions\ShrinkrException;
```

#### Extends

```php
class ShrinkrException extends \Exception
```

#### Usage

Use as a base class for custom Shrinkr exceptions:

```php
namespace App\Exceptions;

use CleaniqueCoders\Shrinkr\Exceptions\ShrinkrException;

class CustomUrlException extends ShrinkrException
{
    public static function invalidDomain(string $domain): self
    {
        return new self("Invalid domain: {$domain}");
    }
}
```

Catch all Shrinkr exceptions:

```php
use CleaniqueCoders\Shrinkr\Exceptions\ShrinkrException;

try {
    $shortUrl = Shrinkr::shorten('https://example.com', $user);
} catch (ShrinkrException $e) {
    // Handle any Shrinkr-related error
    Log::error('Shrinkr error: ' . $e->getMessage());
}
```

## Specific Exceptions

### SlugAlreadyExistsException

Thrown when attempting to create a URL with a slug that already exists.

#### Namespace

```php
use CleaniqueCoders\Shrinkr\Exceptions\SlugAlreadyExistsException;
```

#### Extends

```php
class SlugAlreadyExistsException extends \Exception
```

#### When Thrown

- Creating a short URL with a custom slug that's already in use
- The `CreateShortUrlAction` detects slug collision

#### Example

```php
use CleaniqueCoders\Shrinkr\Exceptions\SlugAlreadyExistsException;
use CleaniqueCoders\Shrinkr\Facades\Shrinkr;

try {
    $shortUrl = Shrinkr::shorten('https://example.com', $user, 'my-slug');
} catch (SlugAlreadyExistsException $e) {
    return response()->json([
        'error' => 'Slug already exists. Please choose a different one.',
    ], 422);
}
```

With action directly:

```php
use CleaniqueCoders\Shrinkr\Actions\CreateShortUrlAction;
use CleaniqueCoders\Shrinkr\Exceptions\SlugAlreadyExistsException;

$action = new CreateShortUrlAction();

try {
    $url = $action->execute([
        'user_id' => $user->id,
        'original_url' => 'https://example.com',
        'custom_slug' => 'existing-slug',
    ]);
} catch (SlugAlreadyExistsException $e) {
    // Suggest an alternative
    $suggestedSlug = 'existing-slug-' . uniqid();

    return back()->withErrors([
        'custom_slug' => "Slug already exists. Try: {$suggestedSlug}",
    ]);
}
```

## Exception Handling Patterns

### Controller Exception Handling

```php
namespace App\Http\Controllers;

use CleaniqueCoders\Shrinkr\Exceptions\SlugAlreadyExistsException;
use CleaniqueCoders\Shrinkr\Exceptions\ShrinkrException;
use CleaniqueCoders\Shrinkr\Facades\Shrinkr;
use Illuminate\Http\Request;

class UrlController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|url',
            'custom_slug' => 'nullable|string|alpha_dash',
        ]);

        try {
            $shortUrl = Shrinkr::shorten(
                $validated['url'],
                $request->user(),
                $validated['custom_slug'] ?? null
            );

            return response()->json([
                'short_url' => $shortUrl,
            ], 201);

        } catch (SlugAlreadyExistsException $e) {
            return response()->json([
                'error' => 'The custom slug is already taken.',
                'message' => $e->getMessage(),
            ], 422);

        } catch (ShrinkrException $e) {
            return response()->json([
                'error' => 'Failed to create short URL.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
```

### Global Exception Handler

Register in `App\Exceptions\Handler`:

```php
use CleaniqueCoders\Shrinkr\Exceptions\SlugAlreadyExistsException;
use CleaniqueCoders\Shrinkr\Exceptions\ShrinkrException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    public function register(): void
    {
        $this->renderable(function (SlugAlreadyExistsException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Slug already exists',
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withErrors([
                'custom_slug' => 'This slug is already taken.',
            ])->withInput();
        });

        $this->renderable(function (ShrinkrException $e, $request) {
            Log::error('Shrinkr Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'URL shortening failed',
                ], 500);
            }

            return back()->with('error', 'Failed to shorten URL. Please try again.');
        });
    }
}
```

### Retry Logic

```php
use CleaniqueCoders\Shrinkr\Exceptions\SlugAlreadyExistsException;
use CleaniqueCoders\Shrinkr\Actions\CreateShortUrlAction;

$action = new CreateShortUrlAction();
$attempts = 0;
$maxAttempts = 3;

while ($attempts < $maxAttempts) {
    try {
        $url = $action->execute([
            'user_id' => $user->id,
            'original_url' => 'https://example.com',
            'custom_slug' => null, // Auto-generate
        ]);

        break; // Success

    } catch (SlugAlreadyExistsException $e) {
        $attempts++;

        if ($attempts >= $maxAttempts) {
            throw new \RuntimeException('Failed to generate unique slug after ' . $maxAttempts . ' attempts');
        }

        // Retry with different slug generation
        continue;
    }
}
```

## Creating Custom Exceptions

### Domain-Specific Exceptions

```php
namespace App\Exceptions;

use CleaniqueCoders\Shrinkr\Exceptions\ShrinkrException;

class UrlExpiredException extends ShrinkrException
{
    public static function forUrl(string $slug): self
    {
        return new self("URL '{$slug}' has expired and is no longer accessible.");
    }
}

class UrlQuotaExceededException extends ShrinkrException
{
    public static function forUser(int $userId, int $limit): self
    {
        return new self("User {$userId} has exceeded their URL quota of {$limit}.");
    }
}

class InvalidUrlException extends ShrinkrException
{
    public static function malformed(string $url): self
    {
        return new self("Invalid URL format: {$url}");
    }

    public static function blacklisted(string $domain): self
    {
        return new self("Domain '{$domain}' is blacklisted.");
    }
}
```

### Using Custom Exceptions

```php
use App\Exceptions\UrlQuotaExceededException;
use App\Exceptions\InvalidUrlException;
use CleaniqueCoders\Shrinkr\Facades\Shrinkr;

// Check URL quota
$userUrlCount = Url::where('user_id', $user->id)->count();

if ($userUrlCount >= $user->url_limit) {
    throw UrlQuotaExceededException::forUser($user->id, $user->url_limit);
}

// Validate domain
$blockedDomains = ['spam.com', 'phishing.net'];
$domain = parse_url($originalUrl, PHP_URL_HOST);

if (in_array($domain, $blockedDomains)) {
    throw InvalidUrlException::blacklisted($domain);
}
```

## Testing Exceptions

### Assert Exceptions Thrown

```php
use CleaniqueCoders\Shrinkr\Exceptions\SlugAlreadyExistsException;
use CleaniqueCoders\Shrinkr\Actions\CreateShortUrlAction;

test('throws exception when slug exists', function () {
    $action = new CreateShortUrlAction();

    // Create first URL
    $action->execute([
        'user_id' => 1,
        'original_url' => 'https://example.com',
        'custom_slug' => 'test-slug',
    ]);

    // Attempt duplicate
    expect(fn() => $action->execute([
        'user_id' => 1,
        'original_url' => 'https://another.com',
        'custom_slug' => 'test-slug',
    ]))->toThrow(SlugAlreadyExistsException::class);
});
```

### Assert Exception Messages

```php
test('exception has correct message', function () {
    $action = new CreateShortUrlAction();

    $action->execute([
        'user_id' => 1,
        'original_url' => 'https://example.com',
        'custom_slug' => 'duplicate',
    ]);

    try {
        $action->execute([
            'user_id' => 1,
            'original_url' => 'https://another.com',
            'custom_slug' => 'duplicate',
        ]);

        $this->fail('Expected SlugAlreadyExistsException');
    } catch (SlugAlreadyExistsException $e) {
        expect($e->getMessage())->toContain('duplicate');
    }
});
```

## Best Practices

### 1. Specific Exception Types

Create specific exceptions for different error conditions:

```php
// Good
throw UrlExpiredException::forUrl($slug);
throw UrlQuotaExceededException::forUser($userId, $limit);

// Avoid generic exceptions
throw new \Exception('Error occurred');
```

### 2. Meaningful Messages

Provide context in exception messages:

```php
// Good
throw new SlugAlreadyExistsException("Slug '{$slug}' is already taken");

// Less helpful
throw new SlugAlreadyExistsException("Duplicate slug");
```

### 3. Catch Specific Before General

```php
try {
    $url = Shrinkr::shorten($originalUrl, $user, $customSlug);
} catch (SlugAlreadyExistsException $e) {
    // Handle slug collision
} catch (ShrinkrException $e) {
    // Handle other Shrinkr errors
} catch (\Exception $e) {
    // Handle unexpected errors
}
```

### 4. Log Exceptions

```php
catch (ShrinkrException $e) {
    Log::error('Shrinkr operation failed', [
        'exception' => get_class($e),
        'message' => $e->getMessage(),
        'user_id' => $user->id,
        'url' => $originalUrl,
    ]);

    throw $e; // Re-throw if needed
}
```

## Next Steps

- [Actions](02-actions.md) - Actions that may throw exceptions
- [Usage](../03-usage/README.md) - Practical exception handling
- [Configuration](../02-configuration/README.md) - Error handling configuration
