<?php

namespace CleaniqueCoders\Shrinkr\Actions;

use CleaniqueCoders\Shrinkr\Models\Url;

class DeleteShortUrlAction
{
    public function execute(Url $url): bool
    {
        return $url->delete();
    }
}
