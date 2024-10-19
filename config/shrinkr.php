<?php

// config for CleaniqueCoders/Shrinkr

use CleaniqueCoders\Shrinkr\Actions\Logger\LogToFile;
use CleaniqueCoders\Shrinkr\Http\Controllers\RedirectController;
use CleaniqueCoders\Shrinkr\Models\RedirectLog;
use CleaniqueCoders\Shrinkr\Models\Url;

return [
    'prefix' => 's',
    'controller' => RedirectController::class,
    'route-name' => 'shrnkr.redirect',
    'logger' => LogToFile::class,
    'models' => [
        'url' => Url::class,
        'redirect-log' => RedirectLog::class,
    ],
];
