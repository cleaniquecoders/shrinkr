# Notifications

Shrinkr supports third-party notifications to keep you informed about URL events via Slack, Discord, and email.

## Table of Contents

- [Configuration](#configuration)
- [Slack Notifications](#slack-notifications)
- [Discord Notifications](#discord-notifications)
- [Email Notifications](#email-notifications)
- [Custom Notifications](#custom-notifications)

## Configuration

Configure notifications in `config/shrinkr.php`:

```php
'notifications' => [
    // Enable or disable notifications
    'enabled' => true,

    // Slack notifications
    'slack' => [
        'enabled' => env('SHRINKR_SLACK_ENABLED', false),
        'webhook_url' => env('SHRINKR_SLACK_WEBHOOK_URL'),
    ],

    // Discord notifications
    'discord' => [
        'enabled' => env('SHRINKR_DISCORD_ENABLED', false),
        'webhook_url' => env('SHRINKR_DISCORD_WEBHOOK_URL'),
    ],

    // Email notifications
    'email' => [
        'enabled' => env('SHRINKR_EMAIL_ENABLED', false),
        'recipients' => env('SHRINKR_EMAIL_RECIPIENTS', ''),
    ],
],
```

### Environment Variables

Add to your `.env`:

```env
# Slack
SHRINKR_SLACK_ENABLED=true
SHRINKR_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL

# Discord
SHRINKR_DISCORD_ENABLED=true
SHRINKR_DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/YOUR/WEBHOOK/URL

# Email
SHRINKR_EMAIL_ENABLED=true
SHRINKR_EMAIL_RECIPIENTS="admin@example.com,manager@example.com"
```

## Slack Notifications

### Setup

1. Create a Slack App:
   - Go to <https://api.slack.com/apps>
   - Click "Create New App" → "From scratch"
   - Name your app and select your workspace

2. Enable Incoming Webhooks:
   - In your app settings, go to "Incoming Webhooks"
   - Toggle "Activate Incoming Webhooks" to On
   - Click "Add New Webhook to Workspace"
   - Select a channel and authorize

3. Copy the webhook URL and add to `.env`:

   ```env
   SHRINKR_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXX
   SHRINKR_SLACK_ENABLED=true
   ```

### Notification Events

Slack notifications are sent for:

- URL created
- URL updated
- URL expired
- URL deleted

### Example Notification

```
🎉 New Shortened URL Created

Short URL: abc123
Original URL: https://example.com/very-long-url
Custom Slug: my-link
Expires At: 2025-01-16 10:30:00
```

### Customizing Slack Notifications

Create a custom listener:

```php
namespace App\Listeners;

use CleaniqueCoders\Shrinkr\Events\UrlCreated;
use CleaniqueCoders\Shrinkr\Notifications\SlackUrlNotification;
use Illuminate\Support\Facades\Notification;

class SendCustomSlackNotification
{
    public function handle(UrlCreated $event)
    {
        if (config('shrinkr.notifications.slack.enabled')) {
            Notification::route(
                'slack',
                config('shrinkr.notifications.slack.webhook_url')
            )->notify(new SlackUrlNotification(
                $event->url,
                'url.created',
                'Custom message here'
            ));
        }
    }
}
```

## Discord Notifications

### Setup

1. Open Discord and go to your server
2. Click on Server Settings → Integrations → Webhooks
3. Click "New Webhook"
4. Choose a channel and name your webhook
5. Copy the Webhook URL

6. Add to `.env`:

   ```env
   SHRINKR_DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/1234567890/XXXXXXXXXXXXXXXXXXXX
   SHRINKR_DISCORD_ENABLED=true
   ```

### Notification Events

Discord notifications support:

- URL created (Green embed)
- URL updated (Blue embed)
- URL expired (Orange embed)
- URL deleted (Red embed)

### Example Discord Embed

```
🎉 New Shortened URL Created

Short URL: abc123
Original URL: https://example.com/very-long-url
Custom Slug: my-link
Expires At: 2025-01-16 10:30:00
```

### Customizing Discord Notifications

Extend the notification class:

```php
namespace App\Notifications;

use CleaniqueCoders\Shrinkr\Notifications\DiscordUrlNotification as BaseNotification;

class CustomDiscordNotification extends BaseNotification
{
    protected function urlCreatedPayload(): array
    {
        return [
            'embeds' => [
                [
                    'title' => '🚀 New URL Added!',
                    'description' => 'A new shortened URL has been created',
                    'color' => 3066993,
                    'fields' => [
                        [
                            'name' => 'Short Code',
                            'value' => $this->url->shortened_url,
                            'inline' => true
                        ],
                        [
                            'name' => 'Destination',
                            'value' => $this->url->original_url,
                            'inline' => false
                        ],
                    ],
                    'footer' => [
                        'text' => 'Powered by Shrinkr'
                    ],
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
        ];
    }
}
```

## Email Notifications

### Setup

Configure your mail settings in `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourapp.com
MAIL_FROM_NAME="Shrinkr Notifications"

# Shrinkr Email Notifications
SHRINKR_EMAIL_ENABLED=true
SHRINKR_EMAIL_RECIPIENTS="admin@example.com,manager@example.com"
```

### Notification Events

Currently, email notifications are available for:

- URL expired events

### Example Email

```
Subject: Shortened URL Expired

Hello,

One of your shortened URLs has expired.

Short URL: abc123
Original URL: https://example.com/long-url
Expired At: 2025-01-15 10:30:00

If you need to continue using this URL, you can create a new shortened URL.

Best regards,
Your Application Team
```

### Customizing Email Notifications

Create a custom notification:

```php
namespace App\Notifications;

use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomUrlExpiredNotification extends Notification
{
    public function __construct(public Url $url) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('⚠️ URL Expired - '.config('app.name'))
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your shortened URL has expired.')
            ->line('**Short URL:** '.$this->url->shortened_url)
            ->line('**Original URL:** '.$this->url->original_url)
            ->action('Create New URL', url('/dashboard/urls/create'))
            ->line('Thank you for using our service!');
    }
}
```

## Custom Notifications

### Create Your Own Notification Channel

You can create notifications for any service:

```php
namespace App\Notifications;

use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class TelegramUrlNotification extends Notification
{
    public function __construct(
        public Url $url,
        public string $event
    ) {}

    public function via($notifiable): array
    {
        return ['telegram'];
    }

    public function toTelegram($notifiable): void
    {
        $token = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        $message = match ($this->event) {
            'url.created' => "🎉 New URL created\n\n".
                "Short: {$this->url->shortened_url}\n".
                "Original: {$this->url->original_url}",
            'url.expired' => "⚠️ URL expired\n\n".
                "Short: {$this->url->shortened_url}",
            default => "URL event: {$this->event}",
        };

        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
        ]);
    }
}
```

### Register Custom Listener

```php
// app/Providers/EventServiceProvider.php

use App\Listeners\SendTelegramNotification;
use CleaniqueCoders\Shrinkr\Events\UrlCreated;

protected $listen = [
    UrlCreated::class => [
        SendTelegramNotification::class,
    ],
];
```

## Disabling Notifications

### Disable All Notifications

```php
// config/shrinkr.php
'notifications' => [
    'enabled' => false,
],
```

### Disable Specific Channels

```env
SHRINKR_SLACK_ENABLED=false
SHRINKR_DISCORD_ENABLED=false
SHRINKR_EMAIL_ENABLED=false
```

## Best Practices

1. **Rate Limiting** - Be mindful of notification frequency to avoid overwhelming channels
2. **Error Handling** - Wrap notification logic in try-catch blocks
3. **Queue Notifications** - Use Laravel queues for non-blocking notifications
4. **Test First** - Test notifications in development before enabling in production
5. **Monitor Failures** - Log notification failures for debugging
6. **Respect Limits** - Check API rate limits for Slack/Discord

## Troubleshooting

### Notifications Not Sending

1. Check if notifications are enabled:

   ```php
   dd(config('shrinkr.notifications.enabled'));
   ```

2. Verify webhook URLs:

   ```php
   dd(config('shrinkr.notifications.slack.webhook_url'));
   ```

3. Check Laravel logs:

   ```bash
   tail -f storage/logs/laravel.log
   ```

4. Test webhook URLs manually:

   ```bash
   curl -X POST YOUR_WEBHOOK_URL \
        -H "Content-Type: application/json" \
        -d '{"text": "Test message"}'
   ```

### Slack/Discord Webhook Errors

- **400 Bad Request**: Check payload format
- **404 Not Found**: Webhook URL is incorrect or deleted
- **Rate Limited**: Too many requests, implement queuing

### Email Not Delivered

- Check mail configuration in `.env`
- Verify SMTP credentials
- Check spam folder
- Review Laravel mail logs
