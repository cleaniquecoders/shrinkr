<?php

namespace CleaniqueCoders\Shrinkr\Actions;

use CleaniqueCoders\Shrinkr\Contracts\Logger;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogToFile implements Logger
{
    public function log(Url $url, Request $request)
    {
        Log::info($url->uuid, [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referrer' => $request->header('referer', 'Direct'),
            'headers' => $request->headers->all(),
            'query_params' => $request->query(),
        ]);
    }
}
