<?php

namespace CleaniqueCoders\Shrinkr\Http\Controllers\Api;

use CleaniqueCoders\Shrinkr\Http\Resources\AnalyticsResource;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class AnalyticsController extends Controller
{
    /**
     * Get analytics for a specific URL.
     */
    public function show(Request $request, string $id): AnonymousResourceCollection
    {
        $url = Url::where('id', $id)
            ->orWhere('uuid', $id)
            ->orWhere('shortened_url', $id)
            ->firstOrFail();

        $query = $url->redirectLogs();

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->input('start_date'));
        }

        if ($request->has('end_date')) {
            $query->where('created_at', '<=', $request->input('end_date'));
        }

        // Order by
        $orderBy = $request->input('order_by', 'created_at');
        $orderDirection = $request->input('order_direction', 'desc');
        $query->orderBy($orderBy, $orderDirection);

        // Paginate
        $perPage = min($request->input('per_page', 15), 100);
        $logs = $query->paginate($perPage);

        return AnalyticsResource::collection($logs);
    }

    /**
     * Get analytics summary for a specific URL.
     */
    public function summary(Request $request, string $id): JsonResponse
    {
        $url = Url::where('id', $id)
            ->orWhere('uuid', $id)
            ->orWhere('shortened_url', $id)
            ->firstOrFail();

        $query = $url->redirectLogs();

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->input('start_date'));
        }

        if ($request->has('end_date')) {
            $query->where('created_at', '<=', $request->input('end_date'));
        }

        $summary = [
            'url_id' => $url->id,
            'url_uuid' => $url->uuid,
            'shortened_url' => $url->shortened_url,
            'total_clicks' => (clone $query)->count(),
            'unique_ips' => (clone $query)->distinct('ip_address')->count('ip_address'),
            'clicks_today' => (clone $query)->whereDate('created_at', today())->count(),
            'clicks_this_week' => (clone $query)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'clicks_this_month' => (clone $query)->whereMonth('created_at', now()->month)->count(),
            'top_referrers' => (clone $query)
                ->selectRaw('referrer, COUNT(*) as count')
                ->whereNotNull('referrer')
                ->groupBy('referrer')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->pluck('count', 'referrer'),
            'top_browsers' => (clone $query)
                ->selectRaw('browser, COUNT(*) as count')
                ->whereNotNull('browser')
                ->groupBy('browser')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->pluck('count', 'browser'),
            'top_platforms' => (clone $query)
                ->selectRaw('platform, COUNT(*) as count')
                ->whereNotNull('platform')
                ->groupBy('platform')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->pluck('count', 'platform'),
        ];

        return response()->json($summary);
    }
}
