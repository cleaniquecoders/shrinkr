<?php

namespace CleaniqueCoders\Shrinkr\Events;

use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class UrlExpired
 *
 * Event triggered when a URL has expired.
 */
class UrlExpired
{
    use Dispatchable, SerializesModels;

    /**
     * The URL model instance that has expired.
     */
    public Url $url;

    /**
     * Create a new event instance.
     *
     * @param  Url  $url  The expired URL model instance.
     */
    public function __construct(Url $url)
    {
        $this->url = $url;
    }
}
