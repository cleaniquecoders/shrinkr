# Testing

Shrinkr is thoroughly tested using Pest, Laravel's elegant testing framework. This guide will help you write tests for your Shrinkr implementation.

## Test Setup

### Test Case Base

Shrinkr provides a base test case that sets up the testing environment:

```php
namespace CleaniqueCoders\Shrinkr\Tests;

use CleaniqueCoders\Shrinkr\ShrinkrServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase as Orchestra;

#[WithMigration]
class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'CleaniqueCoders\\Shrinkr\\Database\\Factories\\'
                .class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            ShrinkrServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        // Run migrations
        $migration = include __DIR__.'/../database/migrations/create_shrinkr_table.php.stub';
        $migration->up();

        $migration = include __DIR__.'/../database/migrations/create_redirect_logs_table.php.stub';
        $migration->up();
    }
}
```

### Pest Configuration

Configure Pest in `tests/Pest.php`:

```php
<?php

use CleaniqueCoders\Shrinkr\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);
```

## Testing Actions

### CreateShortUrlAction

```php
use CleaniqueCoders\Shrinkr\Actions\CreateShortUrlAction;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Support\Str;

beforeEach(function () {
    Url::truncate();
});

test('can create a shortened URL', function () {
    $data = [
        'uuid' => Str::orderedUuid(),
        'original_url' => 'https://example.com/long-url',
        'user_id' => User::factory()->create()->id,
    ];

    $shortUrl = (new CreateShortUrlAction)->execute($data);

    expect($shortUrl)->toBeInstanceOf(Url::class)
        ->and($shortUrl->original_url)->toBe($data['original_url'])
        ->and(strlen($shortUrl->shortened_url))->toBe(6);
});

test('can create URL with custom slug', function () {
    $data = [
        'original_url' => 'https://example.com',
        'custom_slug' => 'my-custom-slug',
        'user_id' => User::factory()->create()->id,
    ];

    $shortUrl = (new CreateShortUrlAction)->execute($data);

    expect($shortUrl->custom_slug)->toBe('my-custom-slug')
        ->and($shortUrl->shortened_url)->toBe('my-custom-slug');
});

test('throws exception if slug already exists', function () {
    $data = [
        'original_url' => 'https://example.com',
        'custom_slug' => 'existing-slug',
        'user_id' => User::factory()->create()->id,
    ];

    (new CreateShortUrlAction)->execute($data);

    expect(fn () => (new CreateShortUrlAction)->execute($data))
        ->toThrow(Exception::class, 'The slug already exists');
});
```

### UpdateShortUrlAction

```php
use CleaniqueCoders\Shrinkr\Actions\UpdateShortUrlAction;

test('can update shortened URL with new custom slug', function () {
    $url = Url::factory()->create([
        'uuid' => Str::orderedUuid(),
        'original_url' => 'https://example.com/old-url',
        'custom_slug' => 'oldslug',
    ]);

    $data = ['custom_slug' => 'newslug'];
    $updatedUrl = (new UpdateShortUrlAction)->execute($url, $data);

    expect($updatedUrl->custom_slug)->toBe('newslug');
});

test('can update expiry date', function () {
    $url = Url::factory()->create();

    $newExpiry = now()->addDays(30);
    $updatedUrl = (new UpdateShortUrlAction)->execute($url, [
        'expires_at' => $newExpiry,
    ]);

    expect($updatedUrl->expires_at->isSameDay($newExpiry))->toBeTrue();
});
```

### DeleteShortUrlAction

```php
use CleaniqueCoders\Shrinkr\Actions\DeleteShortUrlAction;

test('can delete a shortened URL', function () {
    $url = Url::factory()->create([
        'original_url' => 'https://example.com',
    ]);

    expect(Url::count())->toBe(1);

    (new DeleteShortUrlAction)->execute($url);

    expect(Url::count())->toBe(0);
});

test('deletes associated logs when URL is deleted', function () {
    $url = Url::factory()->hasLogs(5)->create();

    expect(RedirectLog::count())->toBe(5);

    (new DeleteShortUrlAction)->execute($url);

    expect(RedirectLog::count())->toBe(0);
});
```

### CheckUrlHealthAction

```php
use CleaniqueCoders\Shrinkr\Actions\CheckUrlHealthAction;
use Illuminate\Support\Facades\Http;

test('marks URL as healthy when reachable', function () {
    Http::fake(['https://example.com' => Http::response('', 200)]);

    $url = Url::factory()->create([
        'original_url' => 'https://example.com',
    ]);

    $action = new CheckUrlHealthAction();
    $isHealthy = $action->execute($url);

    expect($isHealthy)->toBeTrue();
});

test('marks URL as expired when unreachable', function () {
    Http::fake(['https://example.com' => Http::response('', 404)]);

    $url = Url::factory()->create([
        'original_url' => 'https://example.com',
        'is_expired' => false,
    ]);

    $action = new CheckUrlHealthAction();
    $isHealthy = $action->execute($url);

    expect($isHealthy)->toBeFalse()
        ->and($url->fresh()->is_expired)->toBeTrue();
});
```

## Testing Facades

### Shrinkr Facade

```php
use CleaniqueCoders\Shrinkr\Facades\Shrinkr;

test('facade can shorten URLs', function () {
    $user = User::factory()->create();
    $shortUrl = Shrinkr::shorten('https://example.com', $user);

    expect($shortUrl)->toBeString()
        ->and(Url::count())->toBe(1);
});

test('facade can resolve URLs', function () {
    $url = Url::factory()->create([
        'original_url' => 'https://example.com',
    ]);

    $resolved = Shrinkr::resolve($url->shortened_url);

    expect($resolved)->toBe('https://example.com');
});
```

## Testing Models

### Url Model

```php
use CleaniqueCoders\Shrinkr\Models\Url;

test('url has many logs', function () {
    $url = Url::factory()->hasLogs(3)->create();

    expect($url->logs)->toHaveCount(3);
});

test('url can check if expired', function () {
    $url = Url::factory()->create([
        'expires_at' => now()->subDay(),
    ]);

    expect($url->hasExpired())->toBeTrue();
});

test('active scope returns only non-expired URLs', function () {
    Url::factory()->create(['is_expired' => false]);
    Url::factory()->create(['is_expired' => true]);

    expect(Url::active()->count())->toBe(1);
});

test('expired scope returns only expired URLs', function () {
    Url::factory()->create(['is_expired' => false]);
    Url::factory()->create(['is_expired' => true]);

    expect(Url::expired()->count())->toBe(1);
});
```

### RedirectLog Model

```php
use CleaniqueCoders\Shrinkr\Models\RedirectLog;

test('log belongs to url', function () {
    $url = Url::factory()->create();
    $log = RedirectLog::factory()->create(['url_id' => $url->id]);

    expect($log->url->id)->toBe($url->id);
});

test('can query logs by browser', function () {
    RedirectLog::factory()->create(['browser' => 'Chrome']);
    RedirectLog::factory()->create(['browser' => 'Firefox']);

    expect(RedirectLog::where('browser', 'Chrome')->count())->toBe(1);
});
```

## Testing Events

### UrlAccessed Event

```php
use CleaniqueCoders\Shrinkr\Events\UrlAccessed;
use Illuminate\Support\Facades\Event;

test('dispatches url accessed event on redirect', function () {
    Event::fake([UrlAccessed::class]);

    $url = Url::factory()->create();

    $this->get(route('shrnkr.redirect', $url->shortened_url));

    Event::assertDispatched(UrlAccessed::class, function ($event) use ($url) {
        return $event->url->id === $url->id;
    });
});
```

### UrlExpired Event

```php
use CleaniqueCoders\Shrinkr\Events\UrlExpired;

test('dispatches url expired event when marked expired', function () {
    Event::fake([UrlExpired::class]);

    $url = Url::factory()->create([
        'expires_at' => now()->subDay(),
    ]);

    $this->artisan('shrinkr:check-expiry');

    Event::assertDispatched(UrlExpired::class);
});
```

## Testing Commands

### Check Expiry Command

```php
test('check expiry command marks expired URLs', function () {
    Url::factory()->create([
        'is_expired' => false,
        'expires_at' => now()->subDay(),
    ]);

    $this->artisan('shrinkr:check-expiry')
        ->assertSuccessful();

    expect(Url::first()->is_expired)->toBeTrue();
});
```

### Check Health Command

```php
test('check health command verifies URL accessibility', function () {
    Http::fake(['https://example.com' => Http::response('', 200)]);

    Url::factory()->create([
        'original_url' => 'https://example.com',
        'is_expired' => true,
    ]);

    $this->artisan('shrinkr:check-health')
        ->assertSuccessful();

    expect(Url::first()->is_expired)->toBeFalse();
});
```

## Testing Middleware

```php
test('throttle middleware limits requests', function () {
    $url = Url::factory()->create();

    // First 60 requests should succeed
    for ($i = 0; $i < 60; $i++) {
        $this->get(route('shrnkr.redirect', $url->shortened_url))
            ->assertStatus(302);
    }

    // 61st request should be throttled
    $this->get(route('shrnkr.redirect', $url->shortened_url))
        ->assertStatus(429);
});
```

## Integration Tests

### Full URL Shortening Flow

```php
test('complete url shortening workflow', function () {
    $user = User::factory()->create();

    // Create short URL
    $shortUrl = Shrinkr::shorten('https://example.com', $user);
    expect(Url::count())->toBe(1);

    // Access the URL
    $url = Url::first();
    $response = $this->get(route('shrnkr.redirect', $url->shortened_url));
    $response->assertRedirect('https://example.com');

    // Check log created
    expect(RedirectLog::count())->toBe(1);
    $log = RedirectLog::first();
    expect($log->url_id)->toBe($url->id);

    // Update URL
    Shrinkr::update($url, ['custom_slug' => 'new-slug']);
    expect($url->fresh()->custom_slug)->toBe('new-slug');

    // Delete URL
    Shrinkr::delete($url);
    expect(Url::count())->toBe(0);
});
```

## Performance Tests

```php
test('can handle bulk URL creation', function () {
    $user = User::factory()->create();

    $startTime = microtime(true);

    for ($i = 0; $i < 1000; $i++) {
        Shrinkr::shorten("https://example.com/page{$i}", $user);
    }

    $duration = microtime(true) - $startTime;

    expect(Url::count())->toBe(1000)
        ->and($duration)->toBeLessThan(10); // Should complete in under 10 seconds
});
```

## Running Tests

### Run All Tests

```bash
./vendor/bin/pest
```

### Run Specific Test File

```bash
./vendor/bin/pest tests/UrlTest.php
```

### Run Tests with Coverage

```bash
./vendor/bin/pest --coverage
```

### Run Tests in Parallel

```bash
./vendor/bin/pest --parallel
```

### Filter Tests

```bash
./vendor/bin/pest --filter="can create"
```

## Architecture Tests

Using Pest's architecture testing:

```php
arch('models')
    ->expect('CleaniqueCoders\Shrinkr\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model')
    ->toOnlyBeUsedIn([
        'CleaniqueCoders\Shrinkr\Actions',
        'CleaniqueCoders\Shrinkr\Http\Controllers',
    ]);

arch('actions')
    ->expect('CleaniqueCoders\Shrinkr\Actions')
    ->toHaveMethod('execute');

arch('events')
    ->expect('CleaniqueCoders\Shrinkr\Events')
    ->toImplement('Illuminate\Foundation\Events\Dispatchable');
```

## Best Practices

1. **Use Factories** - Create test data with factories instead of manual creation
2. **Fake External Calls** - Use `Http::fake()` for external API calls
3. **Test Edge Cases** - Test boundary conditions and error scenarios
4. **Clean State** - Use `RefreshDatabase` or `truncate()` between tests
5. **Descriptive Names** - Use clear, descriptive test names
6. **One Assertion** - Focus each test on a single behavior
7. **Mock Events** - Use `Event::fake()` to test event dispatching

## Next Steps

- [Contributing](02-contributing.md) - Contribute to Shrinkr
- [API Reference](../05-api-reference/README.md) - API documentation
- [Usage Examples](../03-usage/README.md) - Practical usage patterns
