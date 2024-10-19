<?php

namespace CleaniqueCoders\Shrinkr;

use Illuminate\Support\Facades\Route;

class Shrinkr
{
    public static function routes()
    {
        Route::get(
            config('shrinkr.prefix').'/{shortenedUrl}',
            config('shrinkr.controller')
        )
            ->name(
                config('shrinkr.route-name')
            );
    }
}
