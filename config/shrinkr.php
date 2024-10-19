<?php

// config for CleaniqueCoders/Shrinkr

use CleaniqueCoders\Shrinkr\Actions\Logger\LogToFile;
use CleaniqueCoders\Shrinkr\Http\Controllers\RedirectController;

return [
    'prefix' => 's',
    'controller' => RedirectController::class,
    'route-name' => 'shrnkr.redirect',
    'logger' => LogToFile::class,
];
