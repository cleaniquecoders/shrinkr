<?php

namespace CleaniqueCoders\Shrinkr\Http\Controllers;

use CleaniqueCoders\Shrinkr\Actions\Logger\LogToFile;
use CleaniqueCoders\Shrinkr\Contracts\Logger;
use CleaniqueCoders\Shrinkr\Events\UrlAccessed;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Jenssegers\Agent\Agent;

/**
 * Class RedirectController
 *
 * Handles redirection of shortened URLs to their original URLs.
 */
class RedirectController
{
    /**
     * Handles the incoming redirection request.
     *
     * @param  Request  $request  The HTTP request instance.
     * @param  string  $shortenedUrl  The shortened URL or custom slug to redirect.
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse The response, either a redirect or a 404 view.
     */
    public function __invoke(Request $request, string $shortenedUrl): Response|\Illuminate\Http\RedirectResponse
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

        if (is_string(config('shrinkr.logger', LogToFile::class))) {
            $logger = app(config('shrinkr.logger', LogToFile::class));
            if ($logger instanceof Logger) {
                $logger->log($url, $request, $agent);
            }
        }

        UrlAccessed::dispatch($url);

        return redirect()->away($url->original_url, 302);
    }
}
