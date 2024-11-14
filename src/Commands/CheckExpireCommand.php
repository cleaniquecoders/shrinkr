<?php

namespace CleaniqueCoders\Shrinkr\Commands;

use CleaniqueCoders\Shrinkr\Events\UrlExpired;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Console\Command;

/**
 * Class CheckExpireCommand
 *
 * Command to check for expired URLs and mark them as expired.
 * Dispatches a UrlExpired event for each expired URL.
 */
class CheckExpireCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'shrinkr:check-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and mark expired URLs';

    /**
     * Execute the console command.
     */
    public function handle(): void
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
