<?php

namespace CleaniqueCoders\Shrinkr\Http\Controllers\Api;

use CleaniqueCoders\Shrinkr\Http\Requests\StoreWebhookRequest;
use CleaniqueCoders\Shrinkr\Http\Requests\UpdateWebhookRequest;
use CleaniqueCoders\Shrinkr\Http\Resources\WebhookResource;
use CleaniqueCoders\Shrinkr\Jobs\DispatchWebhookJob;
use CleaniqueCoders\Shrinkr\Models\Webhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    /**
     * Display a listing of webhooks.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Webhook::query();

        // Filter by user if provided
        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Order by
        $orderBy = $request->input('order_by', 'created_at');
        $orderDirection = $request->input('order_direction', 'desc');
        $query->orderBy($orderBy, $orderDirection);

        // Paginate
        $perPage = min($request->input('per_page', 15), 100);
        $webhooks = $query->paginate($perPage);

        return WebhookResource::collection($webhooks);
    }

    /**
     * Store a newly created webhook.
     */
    public function store(StoreWebhookRequest $request): JsonResponse
    {
        $webhook = Webhook::create([
            'uuid' => Str::orderedUuid(),
            'user_id' => $request->input('user_id'),
            'url' => $request->input('url'),
            'events' => $request->input('events'),
            'is_active' => $request->input('is_active', true),
        ]);

        return (new WebhookResource($webhook))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified webhook.
     */
    public function show(Request $request, string $id): WebhookResource
    {
        $webhook = Webhook::where('id', $id)
            ->orWhere('uuid', $id)
            ->firstOrFail();

        return new WebhookResource($webhook);
    }

    /**
     * Update the specified webhook.
     */
    public function update(UpdateWebhookRequest $request, string $id): WebhookResource
    {
        $webhook = Webhook::where('id', $id)
            ->orWhere('uuid', $id)
            ->firstOrFail();

        $webhook->update($request->validated());

        return new WebhookResource($webhook);
    }

    /**
     * Remove the specified webhook.
     */
    public function destroy(string $id): JsonResponse
    {
        $webhook = Webhook::where('id', $id)
            ->orWhere('uuid', $id)
            ->firstOrFail();

        $webhook->delete();

        return response()->json([
            'message' => 'Webhook deleted successfully',
        ], 200);
    }

    /**
     * Test webhook delivery.
     */
    public function test(string $id): JsonResponse
    {
        $webhook = Webhook::where('id', $id)
            ->orWhere('uuid', $id)
            ->firstOrFail();

        $testPayload = [
            'event' => 'webhook.test',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'message' => 'This is a test webhook delivery',
                'webhook_id' => $webhook->id,
                'webhook_uuid' => $webhook->uuid,
            ],
        ];

        DispatchWebhookJob::dispatch($webhook, 'webhook.test', $testPayload);

        return response()->json([
            'message' => 'Test webhook dispatched successfully',
            'webhook_id' => $webhook->id,
        ], 200);
    }

    /**
     * Get webhook delivery history.
     */
    public function calls(Request $request, string $id): JsonResponse
    {
        $webhook = Webhook::where('id', $id)
            ->orWhere('uuid', $id)
            ->firstOrFail();

        $query = $webhook->calls();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Order by
        $orderBy = $request->input('order_by', 'created_at');
        $orderDirection = $request->input('order_direction', 'desc');
        $query->orderBy($orderBy, $orderDirection);

        // Paginate
        $perPage = min($request->input('per_page', 15), 100);
        $calls = $query->paginate($perPage);

        return response()->json([
            'webhook_id' => $webhook->id,
            'data' => $calls->items(),
            'meta' => [
                'total' => $calls->total(),
                'per_page' => $calls->perPage(),
                'current_page' => $calls->currentPage(),
                'last_page' => $calls->lastPage(),
            ],
        ]);
    }
}
