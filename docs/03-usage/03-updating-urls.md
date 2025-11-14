# Updating URLs

Learn how to update existing shortened URLs with new slugs, expiry times, or other properties.

## Using the Facade

### Basic Update

Update a URL with a new slug:

```php
use CleaniqueCoders\Shrinkr\Facades\Shrinkr;
use CleaniqueCoders\Shrinkr\Models\Url;

$url = Url::find(1);

$updatedUrl = Shrinkr::update($url, [
    'custom_slug' => 'new-slug',
]);

echo $updatedUrl->shortened_url; // https://yourdomain.com/s/new-slug
```

### Update Expiry

Extend or modify the expiry time:

```php
$updatedUrl = Shrinkr::update($url, [
    'expiry_duration' => 120, // 2 hours from now
]);
```

### Update Multiple Properties

```php
$updatedUrl = Shrinkr::update($url, [
    'custom_slug' => 'updated-slug',
    'expiry_duration' => 1440, // 24 hours from now
]);
```

## Using the Action Class

For more control, use the `UpdateShortUrlAction`:

```php
use CleaniqueCoders\Shrinkr\Actions\UpdateShortUrlAction;
use CleaniqueCoders\Shrinkr\Models\Url;

$url = Url::find(1);

$action = new UpdateShortUrlAction();

$updatedUrl = $action->execute($url, [
    'custom_slug' => 'better-slug',
    'expiry_duration' => 60,
]);
```

## Direct Model Updates

You can also update URLs directly using Eloquent:

```php
$url = Url::find(1);

$url->update([
    'slug' => 'direct-update',
    'expires_at' => now()->addHours(24),
]);
```

**Note:** When updating directly, ensure slug uniqueness and recalculate expiry times.

## Common Update Patterns

### Extend Expiry Before Expiration

```php
$url = Url::find(1);

if ($url->expires_at && $url->expires_at->isPast()) {
    // URL already expired, set new expiry
    $url->update([
        'expires_at' => now()->addDays(7),
    ]);
} else {
    // Extend current expiry
    $url->update([
        'expires_at' => $url->expires_at->addDays(7),
    ]);
}
```

### Remove Expiry

Make a URL permanent:

```php
$url->update([
    'expires_at' => null,
]);
```

### Reactivate Expired URL

```php
$expiredUrl = Url::where('slug', 'expired-link')->first();

if ($expiredUrl->expires_at && $expiredUrl->expires_at->isPast()) {
    $expiredUrl->update([
        'expires_at' => now()->addDays(30),
    ]);
}
```

### Change Original URL

```php
$url->update([
    'original_url' => 'https://example.com/new-destination',
]);
```

**Warning:** Changing the original URL will redirect to a different destination while keeping the same short URL.

### Batch Updates

Update multiple URLs at once:

```php
// Extend all URLs expiring in the next 24 hours
Url::whereBetween('expires_at', [now(), now()->addDay()])
    ->each(function ($url) {
        Shrinkr::update($url, [
            'expiry_duration' => 10080, // Add 7 days
        ]);
    });

// Remove expiry from all permanent campaign URLs
Url::where('slug', 'like', 'campaign-%')
    ->update(['expires_at' => null]);
```

## Error Handling

### Slug Already Exists

```php
use CleaniqueCoders\Shrinkr\Exceptions\SlugAlreadyExistsException;

try {
    $updatedUrl = Shrinkr::update($url, [
        'custom_slug' => 'existing-slug',
    ]);
} catch (SlugAlreadyExistsException $e) {
    // Handle duplicate slug error
    return back()->withErrors([
        'slug' => 'This slug is already taken.',
    ]);
}
```

## Controller Example

```php
<?php

namespace App\Http\Controllers;

use CleaniqueCoders\Shrinkr\Facades\Shrinkr;
use CleaniqueCoders\Shrinkr\Models\Url;
use CleaniqueCoders\Shrinkr\Exceptions\SlugAlreadyExistsException;
use Illuminate\Http\Request;

class UrlController extends Controller
{
    public function update(Request $request, Url $url)
    {
        // Authorize
        $this->authorize('update', $url);

        // Validate
        $validated = $request->validate([
            'custom_slug' => 'nullable|string|alpha_dash|max:255',
            'expiry_duration' => 'nullable|integer|min:1',
        ]);

        try {
            $updatedUrl = Shrinkr::update($url, [
                'custom_slug' => $validated['custom_slug'] ?? null,
                'expiry_duration' => $validated['expiry_duration'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'URL updated successfully',
                'data' => [
                    'slug' => $updatedUrl->slug,
                    'shortened_url' => $updatedUrl->shortened_url,
                    'expires_at' => $updatedUrl->expires_at,
                ],
            ]);

        } catch (SlugAlreadyExistsException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Slug already exists',
            ], 422);
        }
    }
}
```

## Form Example

```blade
{{-- resources/views/urls/edit.blade.php --}}
<form action="{{ route('urls.update', $url) }}" method="POST">
    @csrf
    @method('PUT')

    <div>
        <label for="custom_slug">Custom Slug</label>
        <input
            type="text"
            name="custom_slug"
            id="custom_slug"
            value="{{ old('custom_slug', $url->slug) }}"
        >
        @error('custom_slug')
            <span>{{ $message }}</span>
        @enderror
    </div>

    <div>
        <label for="expiry_duration">Expiry Duration (minutes)</label>
        <input
            type="number"
            name="expiry_duration"
            id="expiry_duration"
            value="{{ old('expiry_duration') }}"
        >
        @error('expiry_duration')
            <span>{{ $message }}</span>
        @enderror
    </div>

    <div>
        <label>
            <input type="checkbox" name="remove_expiry" value="1">
            Make permanent (no expiry)
        </label>
    </div>

    <button type="submit">Update URL</button>
</form>
```

## API Example

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::put('/urls/{url}', [ApiUrlController::class, 'update']);
});

// app/Http/Controllers/Api/ApiUrlController.php
class ApiUrlController extends Controller
{
    public function update(Request $request, Url $url)
    {
        $this->authorize('update', $url);

        $validated = $request->validate([
            'slug' => 'nullable|alpha_dash',
            'expiry_minutes' => 'nullable|integer',
            'original_url' => 'nullable|url',
        ]);

        try {
            // Update slug and expiry
            if (isset($validated['slug']) || isset($validated['expiry_minutes'])) {
                $url = Shrinkr::update($url, [
                    'custom_slug' => $validated['slug'] ?? null,
                    'expiry_duration' => $validated['expiry_minutes'] ?? null,
                ]);
            }

            // Update original URL directly
            if (isset($validated['original_url'])) {
                $url->update(['original_url' => $validated['original_url']]);
            }

            return response()->json([
                'data' => [
                    'id' => $url->id,
                    'original_url' => $url->original_url,
                    'shortened_url' => $url->shortened_url,
                    'slug' => $url->slug,
                    'expires_at' => $url->expires_at?->toIso8601String(),
                ],
            ]);

        } catch (SlugAlreadyExistsException $e) {
            return response()->json([
                'message' => 'The slug is already taken.',
            ], 422);
        }
    }
}
```

## Testing Updates

```php
// tests/Feature/UrlUpdateTest.php
use CleaniqueCoders\Shrinkr\Facades\Shrinkr;
use CleaniqueCoders\Shrinkr\Models\Url;

test('can update url slug', function () {
    $url = Url::factory()->create(['slug' => 'old-slug']);

    $updated = Shrinkr::update($url, ['custom_slug' => 'new-slug']);

    expect($updated->slug)->toBe('new-slug');
});

test('can update url expiry', function () {
    $url = Url::factory()->create();

    $updated = Shrinkr::update($url, ['expiry_duration' => 60]);

    expect($updated->expires_at)->not->toBeNull()
        ->and($updated->expires_at->isFuture())->toBeTrue();
});

test('throws exception for duplicate slug', function () {
    Url::factory()->create(['slug' => 'existing']);
    $url = Url::factory()->create(['slug' => 'another']);

    Shrinkr::update($url, ['custom_slug' => 'existing']);
})->throws(\CleaniqueCoders\Shrinkr\Exceptions\SlugAlreadyExistsException::class);
```

## Next Steps

- [Deleting URLs](04-deleting-urls.md) - Remove shortened URLs
- [Working with Users](05-working-with-users.md) - User-specific management
- [Analytics](../04-features/02-analytics.md) - Track URL performance
