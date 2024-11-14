<?php

namespace CleaniqueCoders\Shrinkr\Events;

use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class UrlAccessed
 *
 * Event triggered when a URL is accessed.
 */
class UrlAccessed
{
    use Dispatchable, SerializesModels;

    /**
     * The URL model instance that was accessed.
     */
    public Url $url;

    /**
     * Create a new event instance.
     *
     * @param  Url  $url  The accessed URL model instance.
     */
    public function __construct(Url $url)
    {
        $this->url = $url;
    }
}
