<?php

namespace CleaniqueCoders\Shrinkr\Jobs;

use CleaniqueCoders\Shrinkr\Models\Webhook;
use CleaniqueCoders\Shrinkr\Services\WebhookDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1; // Retries are handled by WebhookCall model

    /**
     * Create a new job instance.
     *
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public Webhook $webhook,
        public string $event,
        public array $payload
    ) {}

    /**
     * Execute the job.
     */
    public function handle(WebhookDeliveryService $service): void
    {
        if (! $this->webhook->is_active) {
            return;
        }

        if (! $this->webhook->isSubscribedTo($this->event)) {
            return;
        }

        $service->deliver($this->webhook, $this->event, $this->payload);
    }
}
