<?php

namespace CleaniqueCoders\Shrinkr;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use CleaniqueCoders\Shrinkr\Commands\ShrinkrCommand;

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
            ->hasMigration('create_shrinkr_table')
            ->hasCommand(ShrinkrCommand::class);
    }
}
