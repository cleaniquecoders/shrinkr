<?php

use Illuminate\Support\Facades\Route;

Route::get(
    config('shrinkr.prefix').'/{shortenedUrl}',
    config('shrinkr.controller')
)->name(config('shrinkr.route-name'));