<?php

namespace CleaniqueCoders\Shrinkr\Notifications;

use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MailUrlExpiredNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Url $url
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Shortened URL Expired')
            ->line('One of your shortened URLs has expired.')
            ->line('**Short URL:** '.$this->url->shortened_url)
            ->line('**Original URL:** '.$this->url->original_url)
            ->line('**Expired At:** '.now()->format('Y-m-d H:i:s'))
            ->line('If you need to continue using this URL, you can create a new shortened URL.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'url_id' => $this->url->id,
            'shortened_url' => $this->url->shortened_url,
            'original_url' => $this->url->original_url,
            'expired_at' => now()->toIso8601String(),
        ];
    }
}
