<?php

namespace CleaniqueCoders\Shrinkr;

use CleaniqueCoders\Shrinkr\Commands\ShrinkrCommand;
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
                'create_redirect_logs_table'
            )
            ->hasRoute('shrinkr');
    }
}
