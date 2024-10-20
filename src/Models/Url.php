<?php

namespace CleaniqueCoders\Shrinkr\Models;

use CleaniqueCoders\Shrinkr\Events\UrlExpired;
use CleaniqueCoders\Traitify\Concerns\InteractsWithUser;
use CleaniqueCoders\Traitify\Concerns\InteractsWithUuid;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Http;

class Url extends Model
{
    use HasFactory, InteractsWithUser, InteractsWithUuid;

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

    protected $casts = [
        'is_expired' => 'boolean',
        'recheck_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function getRouteKeyName()
    {
        return 'shortened_url';
    }

    public function redirectLogs(): HasMany
    {
        return $this->hasMany(
            config('shrinkr.models.redirect-log')
        );
    }

    public function hasExpired(): bool
    {
        // Check if the link has expired based on 'expires_at' timestamp
        if ($this->expires_at && $this->expires_at->isPast()) {
            $this->markAsExpired();

            return true;
        }

        return false;
    }

    public function checkHealthStatus(bool $forceCheck = false)
    {
        // If the URL is expired and not forced to recheck, skip it
        if ($this->is_expired && ! $forceCheck) {
            return;
        }

        try {
            $response = Http::timeout(10)->get($this->original_url);

            if ($response->ok()) {
                // If the URL is accessible again, mark it as active
                $this->markAsActive();
            } else {
                $this->markAsExpired();
            }
        } catch (Exception $e) {
            // Handle exceptions (e.g., timeouts, connection issues)
            $this->markAsExpired();
        }
    }

    protected function markAsExpired()
    {
        $this->update([
            'is_expired' => true,
        ]);

        UrlExpired::dispatch($this);
    }

    protected function markAsActive()
    {
        $this->update([
            'is_expired' => false,
            'recheck_at' => null, // Reset recheck timestamp
        ]);
    }
}
