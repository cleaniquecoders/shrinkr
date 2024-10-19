<?php

namespace CleaniqueCoders\Shrinkr\Contracts;

use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Http\Request;

/**
 * Possible to log to database, elasticsearch, etc.
 */
interface Logger
{
    public function log(Url $url, Request $request);
}
