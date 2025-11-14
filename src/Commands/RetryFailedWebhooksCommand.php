<?php

namespace CleaniqueCoders\Shrinkr\Commands;

use CleaniqueCoders\Shrinkr\Jobs\DispatchWebhookJob;
use CleaniqueCoders\Shrinkr\Models\WebhookCall;
use Illuminate\Console\Command;

class RetryFailedWebhooksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shrinkr:retry-webhooks
                            {--force : Force retry all failed webhooks regardless of retry schedule}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry failed webhook deliveries';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $query = WebhookCall::with('webhook')
            ->where('status', 'pending');

        if (! $this->option('force')) {
            $query->readyForRetry();
        }

        $calls = $query->get();

        if ($calls->isEmpty()) {
            $this->info('No webhook calls ready for retry.');

            return self::SUCCESS;
        }

        $this->info("Retrying {$calls->count()} webhook calls...");

        $bar = $this->output->createProgressBar($calls->count());
        $bar->start();

        foreach ($calls as $call) {
            DispatchWebhookJob::dispatch(
                $call->webhook,
                $call->event,
                $call->payload
            );

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info('Webhook retry jobs dispatched successfully.');

        return self::SUCCESS;
    }
}
