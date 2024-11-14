<?php

namespace CleaniqueCoders\Shrinkr\Models;

use CleaniqueCoders\Shrinkr\Database\Factories\UrlFactory;
use CleaniqueCoders\Shrinkr\Events\UrlExpired;
use CleaniqueCoders\Traitify\Concerns\InteractsWithUser;
use CleaniqueCoders\Traitify\Concerns\InteractsWithUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Class Url
 *
 * Represents a shortened URL with expiration and health check capabilities.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $uuid
 * @property string $original_url
 * @property string $shortened_url
 * @property string|null $custom_slug
 * @property bool $is_expired
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $recheck_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Url newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Url newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Url query()
 * @method static \Illuminate\Database\Eloquent\Builder|Url whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Url whereIsExpired($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Url whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Url whereRecheckAt($value)
 */
class Url extends Model
{
    use HasFactory, InteractsWithUser, InteractsWithUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'uuid',
        'original_url',
        'shortened_url',
        'custom_slug',
        'is_expired',
        'expires_at',
        'recheck_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_expired' => 'boolean',
        'recheck_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Gets the route key name for Laravel routing.
     */
    public function getRouteKeyName(): string
    {
        return 'shortened_url';
    }

    /**
     * Get all redirect logs associated with the URL.
     *
     * @return HasMany<RedirectLog>
     */
    public function redirectLogs(): HasMany
    {
        /** @var class-string<RedirectLog> $redirectLogModel */
        $redirectLogModel = config('shrinkr.models.redirect-log', RedirectLog::class);

        /** @phpstan-return HasMany<RedirectLog> */
        return $this->hasMany($redirectLogModel);
    }

    /**
     * Determines if the URL has expired based on the 'expires_at' timestamp.
     *
     * @return bool True if expired, false otherwise.
     */
    public function hasExpired(): bool
    {
        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            $this->markAsExpired();

            return true;
        }

        return false;
    }

    /**
     * Checks the health status of the original URL.
     *
     * @param  bool  $forceCheck  Whether to force a recheck if the URL is expired.
     */
    public function checkHealthStatus(bool $forceCheck = false): void
    {
        if ($this->is_expired && ! $forceCheck) {
            return;
        }

        try {
            $response = Http::timeout(10)->get($this->original_url);

            if ($response->ok()) {
                $this->markAsActive();
            } else {
                $this->markAsExpired();
            }
        } catch (RequestException|ConnectionException $e) {
            $this->markAsExpired();
        }
    }

    /**
     * Marks the URL as expired and dispatches the UrlExpired event.
     */
    protected function markAsExpired(): void
    {
        $this->update([
            'is_expired' => true,
        ]);

        UrlExpired::dispatch($this);
    }

    /**
     * Marks the URL as active and resets the recheck timestamp.
     */
    protected function markAsActive(): void
    {
        $this->update([
            'is_expired' => false,
            'recheck_at' => null,
        ]);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): UrlFactory
    {
        return UrlFactory::new();
    }
}
