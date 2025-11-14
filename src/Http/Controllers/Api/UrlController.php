<?php

namespace CleaniqueCoders\Shrinkr\Http\Controllers\Api;

use CleaniqueCoders\Shrinkr\Actions\CreateShortUrlAction;
use CleaniqueCoders\Shrinkr\Actions\DeleteShortUrlAction;
use CleaniqueCoders\Shrinkr\Actions\UpdateShortUrlAction;
use CleaniqueCoders\Shrinkr\Events\UrlCreated;
use CleaniqueCoders\Shrinkr\Events\UrlDeleted;
use CleaniqueCoders\Shrinkr\Events\UrlUpdated;
use CleaniqueCoders\Shrinkr\Http\Requests\StoreUrlRequest;
use CleaniqueCoders\Shrinkr\Http\Requests\UpdateUrlRequest;
use CleaniqueCoders\Shrinkr\Http\Resources\UrlCollection;
use CleaniqueCoders\Shrinkr\Http\Resources\UrlResource;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class UrlController extends Controller
{
    /**
     * Display a listing of URLs.
     */
    public function index(Request $request): UrlCollection
    {
        $query = Url::query();

        // Filter by user if provided
        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Filter by expired status
        if ($request->has('is_expired')) {
            $query->where('is_expired', $request->boolean('is_expired'));
        }

        // Search by original URL or slug
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('original_url', 'like', "%{$search}%")
                    ->orWhere('shortened_url', 'like', "%{$search}%")
                    ->orWhere('custom_slug', 'like', "%{$search}%");
            });
        }

        // Order by
        $orderBy = $request->input('order_by', 'created_at');
        $orderDirection = $request->input('order_direction', 'desc');
        $query->orderBy($orderBy, $orderDirection);

        // Paginate
        $perPage = min($request->input('per_page', 15), 100);
        $urls = $query->paginate($perPage);

        return new UrlCollection($urls);
    }

    /**
     * Store a newly created URL.
     */
    public function store(StoreUrlRequest $request): JsonResponse
    {
        $url = (new CreateShortUrlAction)->execute($request->validated());

        event(new UrlCreated($url));

        return (new UrlResource($url))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified URL.
     */
    public function show(string $id): UrlResource
    {
        $url = Url::where('id', $id)
            ->orWhere('uuid', $id)
            ->orWhere('shortened_url', $id)
            ->firstOrFail();

        return new UrlResource($url);
    }

    /**
     * Update the specified URL.
     */
    public function update(UpdateUrlRequest $request, string $id): UrlResource
    {
        $url = Url::where('id', $id)
            ->orWhere('uuid', $id)
            ->firstOrFail();

        $updatedUrl = (new UpdateShortUrlAction)->execute($url, $request->validated());

        event(new UrlUpdated($updatedUrl));

        return new UrlResource($updatedUrl);
    }

    /**
     * Remove the specified URL.
     */
    public function destroy(string $id): JsonResponse
    {
        $url = Url::where('id', $id)
            ->orWhere('uuid', $id)
            ->firstOrFail();

        (new DeleteShortUrlAction)->execute($url);

        event(new UrlDeleted($url));

        return response()->json([
            'message' => 'URL deleted successfully',
        ], 200);
    }

    /**
     * Get URL statistics summary.
     */
    public function stats(Request $request): JsonResponse
    {
        $query = Url::query();

        // Filter by user if provided
        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        $stats = [
            'total_urls' => $query->count(),
            'active_urls' => (clone $query)->where('is_expired', false)->count(),
            'expired_urls' => (clone $query)->where('is_expired', true)->count(),
            'urls_with_custom_slug' => (clone $query)->whereNotNull('custom_slug')->count(),
            'urls_with_expiry' => (clone $query)->whereNotNull('expires_at')->count(),
        ];

        return response()->json($stats);
    }
}
