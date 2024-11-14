<?php

namespace CleaniqueCoders\Shrinkr\Actions\Logger;

use CleaniqueCoders\Shrinkr\Contracts\Logger;
use CleaniqueCoders\Shrinkr\Models\Url;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;

/**
 * Class LogToDatabase
 *
 * Logs redirect information to the database, capturing request details and user agent information.
 */
class LogToDatabase implements Logger
{
    /**
     * Logs redirect information to the URL's redirect logs.
     *
     * @param  Url  $url  The URL model instance associated with the redirect log.
     * @param  Request  $request  The HTTP request data.
     * @param  Agent  $agent  The agent instance for retrieving user agent details.
     */
    public function log(Url $url, Request $request, Agent $agent): void
    {
        try {
            $browser = $agent->browser() ?: 'Unknown';
            $platform = $agent->platform() ?: 'Unknown';

            $url->redirectLogs()->create([
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referrer' => $request->header('referer', 'Direct'),
                'headers' => json_encode($request->headers->all(), JSON_THROW_ON_ERROR),
                'query_params' => json_encode($request->query(), JSON_THROW_ON_ERROR),
                'browser' => $browser,
                'browser_version' => $agent->version((string) $browser) ?: 'N/A',
                'platform' => $platform,
                'platform_version' => $agent->version((string) $platform) ?: 'N/A',
                'is_mobile' => $agent->isMobile(),
                'is_desktop' => $agent->isDesktop(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to log redirect', [
                'uuid' => $url->uuid,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
