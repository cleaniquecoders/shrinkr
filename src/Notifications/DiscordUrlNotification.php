<?php

namespace CleaniqueCoders\Shrinkr\Notifications;

use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class DiscordUrlNotification extends Notification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Url $url,
        public string $event,
        public ?string $message = null
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['discord'];
    }

    /**
     * Send the notification to Discord.
     */
    public function toDiscord(object $notifiable): void
    {
        $webhookUrl = config('shrinkr.notifications.discord.webhook_url');

        if (! $webhookUrl) {
            return;
        }

        $payload = $this->buildPayload();

        Http::post($webhookUrl, $payload);
    }

    /**
     * Build the Discord payload.
     *
     * @return array<string, mixed>
     */
    protected function buildPayload(): array
    {
        return match ($this->event) {
            'url.created' => $this->urlCreatedPayload(),
            'url.updated' => $this->urlUpdatedPayload(),
            'url.expired' => $this->urlExpiredPayload(),
            'url.deleted' => $this->urlDeletedPayload(),
            default => ['content' => $this->message ?? 'URL event occurred'],
        };
    }

    /**
     * Build payload for URL created event.
     *
     * @return array<string, mixed>
     */
    protected function urlCreatedPayload(): array
    {
        return [
            'embeds' => [
                [
                    'title' => '🎉 New Shortened URL Created',
                    'color' => 3066993, // Green
                    'fields' => [
                        ['name' => 'Short URL', 'value' => $this->url->shortened_url, 'inline' => false],
                        ['name' => 'Original URL', 'value' => $this->url->original_url, 'inline' => false],
                        ['name' => 'Custom Slug', 'value' => $this->url->custom_slug ?? 'N/A', 'inline' => true],
                        ['name' => 'Expires At', 'value' => $this->url->expires_at?->format('Y-m-d H:i:s') ?? 'Never', 'inline' => true],
                    ],
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
        ];
    }

    /**
     * Build payload for URL updated event.
     *
     * @return array<string, mixed>
     */
    protected function urlUpdatedPayload(): array
    {
        return [
            'embeds' => [
                [
                    'title' => 'ℹ️ Shortened URL Updated',
                    'color' => 3447003, // Blue
                    'fields' => [
                        ['name' => 'Short URL', 'value' => $this->url->shortened_url, 'inline' => false],
                        ['name' => 'Original URL', 'value' => $this->url->original_url, 'inline' => false],
                        ['name' => 'Status', 'value' => $this->url->is_expired ? 'Expired' : 'Active', 'inline' => true],
                    ],
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
        ];
    }

    /**
     * Build payload for URL expired event.
     *
     * @return array<string, mixed>
     */
    protected function urlExpiredPayload(): array
    {
        return [
            'embeds' => [
                [
                    'title' => '⚠️ Shortened URL Expired',
                    'color' => 15105570, // Orange
                    'fields' => [
                        ['name' => 'Short URL', 'value' => $this->url->shortened_url, 'inline' => false],
                        ['name' => 'Original URL', 'value' => $this->url->original_url, 'inline' => false],
                        ['name' => 'Expired At', 'value' => now()->format('Y-m-d H:i:s'), 'inline' => true],
                    ],
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
        ];
    }

    /**
     * Build payload for URL deleted event.
     *
     * @return array<string, mixed>
     */
    protected function urlDeletedPayload(): array
    {
        return [
            'embeds' => [
                [
                    'title' => '🗑️ Shortened URL Deleted',
                    'color' => 15158332, // Red
                    'fields' => [
                        ['name' => 'Short URL', 'value' => $this->url->shortened_url, 'inline' => false],
                        ['name' => 'Original URL', 'value' => $this->url->original_url, 'inline' => false],
                    ],
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
        ];
    }
}
