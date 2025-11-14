<?php

namespace CleaniqueCoders\Shrinkr\Events;

use CleaniqueCoders\Shrinkr\Models\Webhook;
use CleaniqueCoders\Shrinkr\Models\WebhookCall;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebhookDelivered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Webhook $webhook,
        public WebhookCall $webhookCall,
        public bool $successful
    ) {}
}
