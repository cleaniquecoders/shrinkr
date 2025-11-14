<?php

namespace CleaniqueCoders\Shrinkr\Listeners;

use CleaniqueCoders\Shrinkr\Events\UrlAccessed;
use CleaniqueCoders\Shrinkr\Events\UrlCreated;
use CleaniqueCoders\Shrinkr\Events\UrlDeleted;
use CleaniqueCoders\Shrinkr\Events\UrlExpired;
use CleaniqueCoders\Shrinkr\Events\UrlUpdated;
use CleaniqueCoders\Shrinkr\Jobs\DispatchWebhookJob;
use CleaniqueCoders\Shrinkr\Models\Webhook;
use CleaniqueCoders\Shrinkr\Services\WebhookPayloadBuilder;

class DispatchWebhooksForUrlEvents
{
    /**
     * Handle URL created event.
     */
    public function handleUrlCreated(UrlCreated $event): void
    {
        if (! config('shrinkr.webhooks.enabled', true)) {
            return;
        }

        $payload = WebhookPayloadBuilder::forUrlCreated($event->url);
        $this->dispatchToWebhooks('url.created', $payload);
    }

    /**
     * Handle URL updated event.
     */
    public function handleUrlUpdated(UrlUpdated $event): void
    {
        if (! config('shrinkr.webhooks.enabled', true)) {
            return;
        }

        $payload = WebhookPayloadBuilder::forUrlUpdated($event->url);
        $this->dispatchToWebhooks('url.updated', $payload);
    }

    /**
     * Handle URL deleted event.
     */
    public function handleUrlDeleted(UrlDeleted $event): void
    {
        if (! config('shrinkr.webhooks.enabled', true)) {
            return;
        }

        $payload = WebhookPayloadBuilder::forUrlDeleted($event->url);
        $this->dispatchToWebhooks('url.deleted', $payload);
    }

    /**
     * Handle URL accessed event.
     */
    public function handleUrlAccessed(UrlAccessed $event): void
    {
        if (! config('shrinkr.webhooks.enabled', true)) {
            return;
        }

        $payload = WebhookPayloadBuilder::forUrlAccessed($event->url);
        $this->dispatchToWebhooks('url.accessed', $payload);
    }

    /**
     * Handle URL expired event.
     */
    public function handleUrlExpired(UrlExpired $event): void
    {
        if (! config('shrinkr.webhooks.enabled', true)) {
            return;
        }

        $payload = WebhookPayloadBuilder::forUrlExpired($event->url);
        $this->dispatchToWebhooks('url.expired', $payload);
    }

    /**
     * Dispatch webhooks for the given event.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function dispatchToWebhooks(string $event, array $payload): void
    {
        Webhook::where('is_active', true)
            ->get()
            ->filter(fn (Webhook $webhook) => $webhook->isSubscribedTo($event))
            ->each(fn (Webhook $webhook) => DispatchWebhookJob::dispatch($webhook, $event, $payload));
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @return array<string, string>
     */
    public function subscribe(): array
    {
        return [
            UrlCreated::class => 'handleUrlCreated',
            UrlUpdated::class => 'handleUrlUpdated',
            UrlDeleted::class => 'handleUrlDeleted',
            UrlAccessed::class => 'handleUrlAccessed',
            UrlExpired::class => 'handleUrlExpired',
        ];
    }
}
