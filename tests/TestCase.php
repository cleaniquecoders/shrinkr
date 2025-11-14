<?php

namespace CleaniqueCoders\Shrinkr\Tests;

use CleaniqueCoders\Shrinkr\ShrinkrServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase as Orchestra;

#[WithMigration]
class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => str_starts_with($modelName, 'CleaniqueCoders\\Shrinkr\\')
                ? 'CleaniqueCoders\\Shrinkr\\Database\\Factories\\'.class_basename($modelName).'Factory'
                : 'Workbench\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

    }

    protected function getPackageProviders($app)
    {
        return [
            ShrinkrServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        $migration = include __DIR__.'/../database/migrations/create_shrinkr_table.php.stub';
        $migration->up();

        $migration = include __DIR__.'/../database/migrations/create_redirect_logs_table.php.stub';
        $migration->up();

        $migration = include __DIR__.'/../database/migrations/create_webhooks_table.php.stub';
        $migration->up();

        $migration = include __DIR__.'/../database/migrations/create_webhook_calls_table.php.stub';
        $migration->up();
    }
}
