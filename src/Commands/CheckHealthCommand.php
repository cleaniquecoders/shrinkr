<?php

namespace CleaniqueCoders\Shrinkr\Commands;

use CleaniqueCoders\Shrinkr\Actions\CheckUrlHealthAction;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Console\Command;

class CheckHealthCommand extends Command
{
    protected $signature = 'shrinkr:check-health';

    protected $description = 'Check the health of all URLs';

    protected $checkUrlHealthAction;

    public function __construct(CheckUrlHealthAction $checkUrlHealthAction)
    {
        parent::__construct();
        $this->checkUrlHealthAction = $checkUrlHealthAction;
    }

    public function handle()
    {
        Url::chunk(100, function ($urls) {
            $urls->each(function ($url) {
                $result = $this->checkUrlHealthAction->execute($url);
                $status = $result ? 'active' : 'expired';
                $this->components->info("URL {$url->shortened_url} is now marked as {$status}.");
            });
        });

        $this->components->info('URL health check completed.');
    }
}
