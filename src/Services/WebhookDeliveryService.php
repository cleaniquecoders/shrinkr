<?php

namespace CleaniqueCoders\Shrinkr\Services;

use CleaniqueCoders\Shrinkr\Events\WebhookDelivered;
use CleaniqueCoders\Shrinkr\Models\Webhook;
use CleaniqueCoders\Shrinkr\Models\WebhookCall;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WebhookDeliveryService
{
    /**
     * Deliver a webhook to its endpoint.
     *
     * @param  array<string, mixed>  $payload
     */
    public function deliver(Webhook $webhook, string $event, array $payload): WebhookCall
    {
        // Create webhook call record
        $webhookCall = WebhookCall::create([
            'uuid' => Str::orderedUuid(),
            'webhook_id' => $webhook->id,
            'event' => $event,
            'payload' => $payload,
            'status' => 'pending',
            'attempts' => 0,
        ]);

        // Attempt delivery
        $this->attemptDelivery($webhook, $webhookCall, $payload);

        return $webhookCall;
    }

    /**
     * Attempt to deliver the webhook.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function attemptDelivery(Webhook $webhook, WebhookCall $webhookCall, array $payload): void
    {
        try {
            $webhookCall->incrementAttempts();

            $signature = $this->generateSignature($payload, $webhook->secret);
            $timeout = config('shrinkr.webhooks.timeout', 10);

            $response = Http::timeout($timeout)
                ->withHeaders([
                    'X-Shrinkr-Signature' => $signature,
                    'X-Shrinkr-Event' => $webhookCall->event,
                    'X-Shrinkr-Delivery-ID' => $webhookCall->uuid,
                    'User-Agent' => 'Shrinkr-Webhook/1.0',
                ])
                ->post($webhook->url, $payload);

            if ($response->successful()) {
                $webhookCall->markAsSuccessful(
                    $response->status(),
                    $response->body()
                );

                $webhook->markAsTriggered();

                event(new WebhookDelivered($webhook, $webhookCall, true));
            } else {
                $webhookCall->markAsFailed(
                    "HTTP {$response->status()}: {$response->body()}",
                    $response->status(),
                    $response->body()
                );

                event(new WebhookDelivered($webhook, $webhookCall, false));
            }
        } catch (\Exception $e) {
            $webhookCall->markAsFailed($e->getMessage());

            event(new WebhookDelivered($webhook, $webhookCall, false));
        }
    }

    /**
     * Generate HMAC signature for webhook payload.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function generateSignature(array $payload, ?string $secret = null): string
    {
        $secret = $secret ?? config('shrinkr.webhooks.secret', '');

        return hash_hmac('sha256', json_encode($payload), $secret);
    }

    /**
     * Verify webhook signature.
     *
     * @param  array<string, mixed>  $payload
     */
    public function verifySignature(array $payload, string $signature, string $secret): bool
    {
        $expectedSignature = $this->generateSignature($payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
