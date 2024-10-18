<?php

namespace CleaniqueCoders\Shrinkr\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \CleaniqueCoders\Shrinkr\Shrinkr
 */
class Shrinkr extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CleaniqueCoders\Shrinkr\Shrinkr::class;
    }
}
