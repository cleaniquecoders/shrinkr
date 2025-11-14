# Model Customization

This guide shows you how to extend and customize the models used by Shrinkr.

## Available Models

Shrinkr uses three models:

1. **Url** - Represents shortened URLs
2. **RedirectLog** - Stores redirect analytics
3. **User** - References the authenticated user

## Model Configuration

Models are configured in `config/shrinkr.php`:

```php
'models' => [
    'user' => \Illuminate\Contracts\Auth\Authenticatable::class,
    'url' => \CleaniqueCoders\Shrinkr\Models\Url::class,
    'redirect-log' => \CleaniqueCoders\Shrinkr\Models\RedirectLog::class,
],
```

## Extending the Url Model

### Basic Extension

Create a custom Url model that extends Shrinkr's base model:

```php
<?php

namespace App\Models;

use CleaniqueCoders\Shrinkr\Models\Url as BaseUrl;

class Url extends BaseUrl
{
    // Add custom properties
    protected $appends = ['full_url', 'is_popular'];

    // Add custom methods
    public function getFullUrlAttribute(): string
    {
        return config('app.url') . '/' . $this->slug;
    }

    public function getIsPopularAttribute(): bool
    {
        return $this->visits_count > 1000;
    }

    // Add custom relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
```

### Configure Custom Model

Update `config/shrinkr.php`:

```php
'models' => [
    'url' => \App\Models\Url::class,
    // ... other models
],
```

### Add Custom Fields

**Step 1:** Create a migration:

```bash
php artisan make:migration add_custom_fields_to_urls_table
```

```php
public function up()
{
    Schema::table('urls', function (Blueprint $table) {
        $table->string('category')->nullable();
        $table->json('metadata')->nullable();
        $table->boolean('is_featured')->default(false);
    });
}
```

**Step 2:** Update the model:

```php
class Url extends BaseUrl
{
    protected $fillable = [
        'original_url',
        'slug',
        'user_id',
        'expires_at',
        'category',
        'metadata',
        'is_featured',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_featured' => 'boolean',
    ];
}
```

## Extending the RedirectLog Model

### Basic Extension

```php
<?php

namespace App\Models;

use CleaniqueCoders\Shrinkr\Models\RedirectLog as BaseRedirectLog;

class RedirectLog extends BaseRedirectLog
{
    // Add custom scopes
    public function scopeMobile($query)
    {
        return $query->where('device', 'mobile');
    }

    public function scopeFromReferrer($query, string $referrer)
    {
        return $query->where('referrer', 'like', "%{$referrer}%");
    }

    // Add custom methods
    public function getLocationAttribute(): ?string
    {
        // Use a service to get location from IP
        return app(GeoLocationService::class)->locate($this->ip);
    }
}
```

### Configure Custom Model

```php
'models' => [
    'redirect-log' => \App\Models\RedirectLog::class,
    // ... other models
],
```

## Custom Relationships

### Add Tags to URLs

**Step 1:** Create Tag model and pivot table:

```php
// app/Models/Tag.php
class Tag extends Model
{
    public function urls()
    {
        return $this->belongsToMany(Url::class);
    }
}
```

**Step 2:** Add relationship to Url model:

```php
class Url extends BaseUrl
{
    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function syncTags(array $tags): void
    {
        $this->tags()->sync($tags);
    }
}
```

**Step 3:** Use the relationship:

```php
$url = Shrinkr::shorten('https://example.com', auth()->id());
$url->syncTags([1, 2, 3]);
```

### Add Categories to URLs

```php
// app/Models/Category.php
class Category extends Model
{
    public function urls()
    {
        return $this->hasMany(Url::class);
    }
}

// app/Models/Url.php
class Url extends BaseUrl
{
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
```

## Custom Scopes and Queries

### Add Query Scopes

```php
class Url extends BaseUrl
{
    public function scopeActive($query)
    {
        return $query->whereNull('expires_at')
            ->orWhere('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    public function scopePopular($query, int $minViews = 100)
    {
        return $query->where('visits_count', '>=', $minViews);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
```

### Using Custom Scopes

```php
// Get active URLs
$activeUrls = Url::active()->get();

// Get popular URLs by user
$popularUserUrls = Url::byUser(auth()->id())
    ->popular(500)
    ->get();

// Get expired URLs
$expiredUrls = Url::expired()->get();
```

## Custom Accessors and Mutators

### Add Accessors

```php
class Url extends BaseUrl
{
    public function getShortenedUrlAttribute(): string
    {
        $domain = config('shrinkr.domain') ?? config('app.url');
        $prefix = config('shrinkr.prefix');

        return $domain . '/' . $prefix . '/' . $this->slug;
    }

    public function getQrCodeUrlAttribute(): string
    {
        return "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data="
            . urlencode($this->shortened_url);
    }

    public function getTimeRemainingAttribute(): ?string
    {
        if (!$this->expires_at) {
            return null;
        }

        return $this->expires_at->diffForHumans();
    }
}
```

### Using Accessors

```php
$url = Url::find(1);

echo $url->shortened_url;    // https://yourdomain.com/s/abc123
echo $url->qr_code_url;       // QR code image URL
echo $url->time_remaining;    // "2 days from now"
```

## Model Events

### Add Model Observers

```php
<?php

namespace App\Observers;

use App\Models\Url;

class UrlObserver
{
    public function created(Url $url): void
    {
        // Send notification when URL is created
        \Notification::send($url->user, new UrlCreatedNotification($url));
    }

    public function updated(Url $url): void
    {
        // Log URL updates
        \Log::info("URL {$url->slug} updated", $url->getDirty());
    }

    public function deleting(Url $url): void
    {
        // Clean up related data
        $url->logs()->delete();
    }
}
```

### Register Observer

In `AppServiceProvider`:

```php
use App\Models\Url;
use App\Observers\UrlObserver;

public function boot(): void
{
    Url::observe(UrlObserver::class);
}
```

## Complete Custom Model Example

Here's a complete example with all customizations:

```php
<?php

namespace App\Models;

use CleaniqueCoders\Shrinkr\Models\Url as BaseUrl;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Url extends BaseUrl
{
    protected $fillable = [
        'original_url',
        'slug',
        'user_id',
        'expires_at',
        'category_id',
        'metadata',
        'is_featured',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_featured' => 'boolean',
        'expires_at' => 'datetime',
    ];

    protected $appends = [
        'shortened_url',
        'qr_code_url',
        'is_active',
    ];

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNull('expires_at')
            ->orWhere('expires_at', '>', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    // Accessors
    public function getShortenedUrlAttribute(): string
    {
        return route(config('shrinkr.route-name'), ['slug' => $this->slug]);
    }

    public function getQrCodeUrlAttribute(): string
    {
        return "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data="
            . urlencode($this->shortened_url);
    }

    public function getIsActiveAttribute(): bool
    {
        return !$this->expires_at || $this->expires_at->isFuture();
    }

    // Custom Methods
    public function markAsFeatured(): void
    {
        $this->update(['is_featured' => true]);
    }

    public function syncTags(array $tagIds): void
    {
        $this->tags()->sync($tagIds);
    }
}
```

## Next Steps

- [Usage Guide](../03-usage/README.md) - Use your custom models
- [API Reference](../05-api-reference/README.md) - Model API documentation
- [Development](../06-development/README.md) - Testing custom models
