<?php

namespace CleaniqueCoders\Shrinkr\Notifications;

use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class SlackUrlNotification extends Notification
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
        return ['slack'];
    }

    /**
     * Get the Slack representation of the notification.
     */
    public function toSlack(object $notifiable): SlackMessage
    {
        $webhookUrl = config('shrinkr.notifications.slack.webhook_url');

        $message = SlackMessage::create()
            ->to($webhookUrl);

        return match ($this->event) {
            'url.created' => $this->urlCreatedMessage($message),
            'url.updated' => $this->urlUpdatedMessage($message),
            'url.expired' => $this->urlExpiredMessage($message),
            'url.deleted' => $this->urlDeletedMessage($message),
            default => $message->content($this->message ?? 'URL event occurred'),
        };
    }

    /**
     * Build message for URL created event.
     */
    protected function urlCreatedMessage(SlackMessage $message): SlackMessage
    {
        return $message
            ->success()
            ->content('New shortened URL created!')
            ->attachment(function ($attachment) {
                $attachment
                    ->title('URL Details', $this->url->original_url)
                    ->fields([
                        'Short URL' => $this->url->shortened_url,
                        'Original URL' => $this->url->original_url,
                        'Custom Slug' => $this->url->custom_slug ?? 'N/A',
                        'Expires At' => $this->url->expires_at?->format('Y-m-d H:i:s') ?? 'Never',
                    ]);
            });
    }

    /**
     * Build message for URL updated event.
     */
    protected function urlUpdatedMessage(SlackMessage $message): SlackMessage
    {
        return $message
            ->content('Shortened URL updated')
            ->attachment(function ($attachment) {
                $attachment
                    ->title('URL Details')
                    ->fields([
                        'Short URL' => $this->url->shortened_url,
                        'Original URL' => $this->url->original_url,
                        'Status' => $this->url->is_expired ? 'Expired' : 'Active',
                    ]);
            });
    }

    /**
     * Build message for URL expired event.
     */
    protected function urlExpiredMessage(SlackMessage $message): SlackMessage
    {
        return $message
            ->warning()
            ->content('Shortened URL has expired!')
            ->attachment(function ($attachment) {
                $attachment
                    ->title('Expired URL')
                    ->fields([
                        'Short URL' => $this->url->shortened_url,
                        'Original URL' => $this->url->original_url,
                        'Expired At' => now()->format('Y-m-d H:i:s'),
                    ]);
            });
    }

    /**
     * Build message for URL deleted event.
     */
    protected function urlDeletedMessage(SlackMessage $message): SlackMessage
    {
        return $message
            ->error()
            ->content('Shortened URL deleted')
            ->attachment(function ($attachment) {
                $attachment
                    ->title('Deleted URL')
                    ->fields([
                        'Short URL' => $this->url->shortened_url,
                        'Original URL' => $this->url->original_url,
                    ]);
            });
    }
}
