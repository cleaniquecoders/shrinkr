<?php

use Illuminate\Support\Facades\Route;

Route::middleware(config('shrinkr.middleware'))
    ->get(
        config('shrinkr.prefix', 's').'/{shortenedUrl}',
        config('shrinkr.controller')
    )
    ->name(config('shrinkr.route-name'))
    ->domain(config('shrinkr.domain'));
