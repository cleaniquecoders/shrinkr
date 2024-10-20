<?php

namespace CleaniqueCoders\Shrinkr\Http\Controllers;

use CleaniqueCoders\Shrinkr\Actions\Logger\LogToFile;
use CleaniqueCoders\Shrinkr\Events\UrlAccessed;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

class RedirectController
{
    public function __invoke(Request $request, string $shortenedUrl)
    {
        // Look for the URL in the database by shortened URL or custom slug
        $url = Url::where('shortened_url', $shortenedUrl)
            ->orWhere('custom_slug', $shortenedUrl)
            ->first();

        if (! $url) {
            return response()->view('shrinkr::errors.404', [], 404);
        }

        if ($url->hasExpired()) {
            return response()->view('shrinkr::errors.404', [], 404);
        }

        $agent = new Agent;
        $agent->setUserAgent($request->userAgent());

        app(config('shrinkr.logger', LogToFile::class))->log($url, $request, $agent);

        UrlAccessed::dispatch($url);

        return redirect()->away($url->original_url, 302);
    }
}
