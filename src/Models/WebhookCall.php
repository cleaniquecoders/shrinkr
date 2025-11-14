<?php

namespace CleaniqueCoders\Shrinkr\Models;

use CleaniqueCoders\Shrinkr\Database\Factories\WebhookCallFactory;
use CleaniqueCoders\Traitify\Concerns\InteractsWithUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class WebhookCall
 *
 * Represents a single webhook delivery attempt.
 *
 * @property int $id
 * @property string $uuid
 * @property int $webhook_id
 * @property string $event
 * @property array $payload
 * @property string $status
 * @property int $attempts
 * @property int|null $response_status_code
 * @property string|null $response_body
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $delivered_at
 * @property \Illuminate\Support\Carbon|null $failed_at
 * @property \Illuminate\Support\Carbon|null $next_retry_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class WebhookCall extends Model
{
    use HasFactory, InteractsWithUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'webhook_id',
        'uuid',
        'event',
        'payload',
        'status',
        'attempts',
        'response_status_code',
        'response_body',
        'error_message',
        'delivered_at',
        'failed_at',
        'next_retry_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'array',
        'attempts' => 'integer',
        'response_status_code' => 'integer',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
        'next_retry_at' => 'datetime',
    ];

    /**
     * Get the webhook that owns this call.
     *
     * @return BelongsTo<Webhook, WebhookCall>
     */
    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    /**
     * Mark the webhook call as successful.
     */
    public function markAsSuccessful(int $statusCode, ?string $responseBody = null): void
    {
        $this->update([
            'status' => 'success',
            'response_status_code' => $statusCode,
            'response_body' => $responseBody,
            'delivered_at' => now(),
            'next_retry_at' => null,
        ]);
    }

    /**
     * Mark the webhook call as failed.
     */
    public function markAsFailed(string $errorMessage, ?int $statusCode = null, ?string $responseBody = null): void
    {
        $maxRetries = config('shrinkr.webhooks.max_retries', 3);
        $shouldRetry = $this->attempts < $maxRetries;

        $this->update([
            'status' => $shouldRetry ? 'pending' : 'failed',
            'error_message' => $errorMessage,
            'response_status_code' => $statusCode,
            'response_body' => $responseBody,
            'failed_at' => $shouldRetry ? null : now(),
            'next_retry_at' => $shouldRetry ? $this->calculateNextRetryTime() : null,
        ]);
    }

    /**
     * Increment the attempt counter.
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    /**
     * Calculate the next retry time using exponential backoff.
     */
    protected function calculateNextRetryTime(): \Illuminate\Support\Carbon
    {
        // Exponential backoff: 1 min, 5 min, 15 min
        $delays = [60, 300, 900];
        $delay = $delays[$this->attempts] ?? 900;

        return now()->addSeconds($delay);
    }

    /**
     * Scope to get only pending calls that are ready for retry.
     */
    public function scopeReadyForRetry($query)
    {
        return $query->where('status', 'pending')
            ->where('next_retry_at', '<=', now());
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): WebhookCallFactory
    {
        return WebhookCallFactory::new();
    }
}
