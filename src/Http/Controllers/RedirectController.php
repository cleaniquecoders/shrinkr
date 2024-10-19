<?php

namespace CleaniqueCoders\Shrinkr\Http\Controllers;

use CleaniqueCoders\Shrinkr\Actions\LogToFile;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RedirectController
{
    public function __invoke(Request $request, string $shortenedUrl)
    {
        // Look for the URL in the database by shortened URL or custom slug
        $url = Url::where('shortened_url', $shortenedUrl)
            ->orWhere('custom_slug', $shortenedUrl)
            ->first();

        // If the URL is not found or is expired, return a 404 response
        if (! $url || $url->is_expired) {
            return response()->view('shrinkr::errors.404', [], 404);
        }

        // Optional: Log click analytics (you can enhance this as needed)
        app(config('shrinkr.logger', LogToFile::class))->log($url, $request);

        // Redirect the user to the original URL (301 or 302 redirect)
        return redirect()->away($url->original_url, 302);
    }
}
