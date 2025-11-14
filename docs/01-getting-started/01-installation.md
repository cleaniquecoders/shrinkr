# Installation

This guide walks you through installing Shrinkr in your Laravel application.

## Requirements

Before installing Shrinkr, ensure your system meets these requirements:

- PHP 8.2, 8.3, or 8.4
- Laravel 10.x, 11.x, or 12.x
- Composer

## Installation Steps

### 1. Install via Composer

Install the package using Composer:

```bash
composer require cleaniquecoders/shrinkr
```

### 2. Publish Migration Files

Publish the migration files to your application:

```bash
php artisan vendor:publish --tag="shrinkr-migrations"
```

This will create two migration files in your `database/migrations` directory:

- `create_shrinkr_table.php.stub` - Creates the main URLs table
- `create_redirect_logs_table.php.stub` - Creates the redirect logs table

### 3. Run Migrations

Execute the migrations to create the necessary database tables:

```bash
php artisan migrate
```

This will create two tables:

- `urls` - Stores shortened URLs and their metadata
- `redirect_logs` - Stores analytics data for URL redirects

### 4. Publish Configuration File

Publish the configuration file to customize Shrinkr settings:

```bash
php artisan vendor:publish --tag="shrinkr-config"
```

This will create a `config/shrinkr.php` file in your application.

## Verify Installation

To verify that Shrinkr is installed correctly:

1. Check that the configuration file exists at `config/shrinkr.php`
2. Verify that the `urls` and `redirect_logs` tables exist in your database
3. Ensure the Shrinkr facade is available:

```php
use CleaniqueCoders\Shrinkr\Facades\Shrinkr;

// This should not throw any errors
Shrinkr::class;
```

## Next Steps

Now that Shrinkr is installed, proceed to:

- [Configuration](02-configuration.md) - Configure Shrinkr for your needs
- [Quick Start](03-quick-start.md) - Create your first shortened URL
