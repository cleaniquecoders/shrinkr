<?php

namespace CleaniqueCoders\Shrinkr\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use CleaniqueCoders\Shrinkr\ShrinkrServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration]
class TestCase extends Orchestra
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'CleaniqueCoders\\Shrinkr\\Database\\Factories\\'.class_basename($modelName).'Factory'
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

        /*
        $migration = include __DIR__.'/../database/migrations/create_shrinkr_table.php.stub';
        $migration->up();
        */
    }
}
