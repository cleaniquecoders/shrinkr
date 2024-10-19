<?php

namespace CleaniqueCoders\Shrinkr\Actions\Logger;

use CleaniqueCoders\Shrinkr\Contracts\Logger;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;

class LogToDatabase implements Logger
{
    public function log(Url $url, Request $request, Agent $agent)
    {
        try {
            $url->redirectLogs()->create([
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referrer' => $request->header('referer', 'Direct'),
                'headers' => json_encode($request->headers->all()),
                'query_params' => json_encode($request->query()),
                'browser' => $agent->browser() ?: 'Unknown',
                'browser_version' => $agent->version($agent->browser()) ?: 'N/A',
                'platform' => $agent->platform() ?: 'Unknown',
                'platform_version' => $agent->version($agent->platform()) ?: 'N/A',
                'is_mobile' => $agent->isMobile(),
                'is_desktop' => $agent->isDesktop(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to log redirect', [
                'uuid' => $url->uuid,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
