<?php

namespace CleaniqueCoders\Shrinkr\Models;

use CleaniqueCoders\Shrinkr\Database\Factories\WebhookFactory;
use CleaniqueCoders\Traitify\Concerns\InteractsWithUser;
use CleaniqueCoders\Traitify\Concerns\InteractsWithUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Webhook
 *
 * Represents a webhook subscription for receiving event notifications.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $uuid
 * @property string $url
 * @property array $events
 * @property string|null $secret
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $last_triggered_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Webhook extends Model
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
        'url',
        'events',
        'secret',
        'is_active',
        'last_triggered_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'secret',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Webhook $webhook) {
            if (empty($webhook->secret)) {
                $webhook->secret = bin2hex(random_bytes(32));
            }
        });
    }

    /**
     * Get all webhook calls for this webhook.
     *
     * @return HasMany<WebhookCall>
     */
    public function calls(): HasMany
    {
        return $this->hasMany(WebhookCall::class);
    }

    /**
     * Check if the webhook is subscribed to a specific event.
     */
    public function isSubscribedTo(string $event): bool
    {
        return in_array($event, $this->events);
    }

    /**
     * Update the last triggered timestamp.
     */
    public function markAsTriggered(): void
    {
        $this->update(['last_triggered_at' => now()]);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): WebhookFactory
    {
        return WebhookFactory::new();
    }
}
