<?php

// config for CleaniqueCoders/Shrinkr

use CleaniqueCoders\Shrinkr\Actions\Logger\LogToFile;
use CleaniqueCoders\Shrinkr\Http\Controllers\RedirectController;
use CleaniqueCoders\Shrinkr\Models\RedirectLog;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Contracts\Auth\Authenticatable;

return [
    'prefix' => 's',
    'controller' => RedirectController::class,
    'domain' => null,
    'route-name' => 'shrnkr.redirect',
    'logger' => LogToFile::class,
    'models' => [
        'user' => Authenticatable::class,
        'url' => Url::class,
        'redirect-log' => RedirectLog::class,
    ],
];
