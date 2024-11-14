<?php

namespace CleaniqueCoders\Shrinkr\Commands;

use CleaniqueCoders\Shrinkr\Actions\CheckUrlHealthAction;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Console\Command;

/**
 * Class CheckHealthCommand
 *
 * Command to check the health of all URLs in the database.
 * Uses the CheckUrlHealthAction to assess each URL's status and updates it as active or expired.
 */
class CheckHealthCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'shrinkr:check-health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the health of all URLs';

    /**
     * The action used to check the health of a URL.
     */
    protected CheckUrlHealthAction $checkUrlHealthAction;

    /**
     * CheckHealthCommand constructor.
     *
     * @param  CheckUrlHealthAction  $checkUrlHealthAction  The action used to check the URL's health.
     */
    public function __construct(CheckUrlHealthAction $checkUrlHealthAction)
    {
        parent::__construct();
        $this->checkUrlHealthAction = $checkUrlHealthAction;
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
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
