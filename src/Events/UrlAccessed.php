<?php

namespace CleaniqueCoders\Shrinkr\Events;

use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UrlAccessed
{
    use Dispatchable, SerializesModels;

    public $url;

    public function __construct(Url $url)
    {
        $this->url = $url;
    }
}
