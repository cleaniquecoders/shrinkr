<?php

namespace CleaniqueCoders\Shrinkr\Actions\Logger;

use CleaniqueCoders\Shrinkr\Contracts\Logger;
use CleaniqueCoders\Shrinkr\Models\Url;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;

/**
 * Class LogToFile
 *
 * Logs redirect information to a file, capturing request details and user agent data.
 */
class LogToFile implements Logger
{
    /**
     * Logs redirect information to a file.
     *
     * @param  Url  $url  The URL model instance for which to log the redirect.
     * @param  Request  $request  The HTTP request data.
     * @param  Agent  $agent  The agent instance for retrieving browser and platform information.
     */
    public function log(Url $url, Request $request, Agent $agent): void
    {
        try {
            $browser = $agent->browser() ?: 'Unknown';
            $platform = $agent->platform() ?: 'Unknown';

            Log::info('Redirect Log', [
                'uuid' => $url->uuid,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referrer' => $request->header('referer', 'Direct'),
                'headers' => $request->headers->all(),
                'query_params' => $request->query(),
                'browser' => $browser,
                'browser_version' => $agent->version((string) $browser) ?: 'N/A',
                'platform' => $platform,
                'platform_version' => $agent->version((string) $platform) ?: 'N/A',
                'is_mobile' => $agent->isMobile(),
                'is_desktop' => $agent->isDesktop(),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to log redirect', [
                'uuid' => $url->uuid,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
