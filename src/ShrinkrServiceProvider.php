<?php

namespace CleaniqueCoders\Shrinkr;

use CleaniqueCoders\Shrinkr\Commands\CheckExpireCommand;
use CleaniqueCoders\Shrinkr\Commands\CheckHealthCommand;
use CleaniqueCoders\Shrinkr\Commands\RetryFailedWebhooksCommand;
use CleaniqueCoders\Shrinkr\Listeners\DispatchWebhooksForUrlEvents;
use Illuminate\Support\Facades\Event;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ShrinkrServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('shrinkr')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations(
                'create_shrinkr_table',
                'create_redirect_logs_table',
                'create_webhooks_table',
                'create_webhook_calls_table'
            )
            ->hasRoutes(['shrinkr', 'api'])
            ->hasCommands(
                CheckExpireCommand::class,
                CheckHealthCommand::class,
                RetryFailedWebhooksCommand::class
            );
    }

    public function packageBooted(): void
    {
        // Register event subscribers for webhooks
        if (config('shrinkr.webhooks.enabled', true)) {
            Event::subscribe(DispatchWebhooksForUrlEvents::class);
        }
    }
}
