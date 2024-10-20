<?php

namespace CleaniqueCoders\Shrinkr\Commands;

use CleaniqueCoders\Shrinkr\Events\UrlExpired;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Console\Command;

class CheckExpireCommand extends Command
{
    protected $signature = 'shrinkr:check-expiry';

    protected $description = 'Check and mark expired URLs';

    public function handle()
    {
        // Query for URLs that have passed their expiry and are not marked as expired
        $expiredUrls = Url::where('expires_at', '<=', now())
            ->where('is_expired', false)
            ->get();

        if ($expiredUrls->isEmpty()) {
            $this->components->info('No expired URLs found.');

            return;
        }

        foreach ($expiredUrls as $url) {
            $url->update(['is_expired' => true]);

            UrlExpired::dispatch($url);
        }

        $this->components->info('Expired URLs have been marked successfully.');
    }
}
