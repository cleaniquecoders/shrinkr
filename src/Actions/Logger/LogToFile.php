<?php

namespace CleaniqueCoders\Shrinkr\Actions\Logger;

use CleaniqueCoders\Shrinkr\Contracts\Logger;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;

class LogToFile implements Logger
{
    public function log(Url $url, Request $request, Agent $agent)
    {
        try {
            Log::info('Redirect Log', [
                'uuid' => $url->uuid,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referrer' => $request->header('referer', 'Direct'),
                'headers' => $request->headers->all(),
                'query_params' => $request->query(),
                'browser' => $agent->browser(),
                'browser_version' => $agent->version($agent->browser()),
                'platform' => $agent->platform(),
                'platform_version' => $agent->version($agent->platform()),
                'is_mobile' => $agent->isMobile(),
                'is_desktop' => $agent->isDesktop(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log redirect', [
                'uuid' => $url->uuid,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
