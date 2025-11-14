<?php

use CleaniqueCoders\Shrinkr\Actions\Logger\LogToFile;
use CleaniqueCoders\Shrinkr\Http\Controllers\RedirectController;
use CleaniqueCoders\Shrinkr\Models\RedirectLog;
use CleaniqueCoders\Shrinkr\Models\Url;
use Illuminate\Contracts\Auth\Authenticatable;

return [

    /*
    |--------------------------------------------------------------------------
    | URL Prefix
    |--------------------------------------------------------------------------
    |
    | This value is the prefix used for all shortened URLs. Setting this value
    | will prefix the route, allowing you to access shortened URLs at
    | `/s/{code}`. Feel free to change this to your preferred prefix.
    |
    */

    'prefix' => 's',

    /*
    |--------------------------------------------------------------------------
    | Redirect Controller
    |--------------------------------------------------------------------------
    |
    | This option defines the controller that will handle the redirect logic
    | for shortened URLs. By default, it uses the RedirectController, which
    | can be swapped with any controller implementing the redirect logic.
    |
    */

    'controller' => RedirectController::class,

    /*
    |--------------------------------------------------------------------------
    | Domain Constraint
    |--------------------------------------------------------------------------
    |
    | If your shortened URLs are bound to a specific domain, you can set that
    | domain here. Setting this value to null will allow shortened URLs to
    | work on any domain, making this configuration more flexible.
    |
    */

    'domain' => null,

    /*
    |--------------------------------------------------------------------------
    | Route Name
    |--------------------------------------------------------------------------
    |
    | The name of the route that handles URL redirection. This allows you to
    | reference the route elsewhere in your application using its name rather
    | than its URL. Customizing this name is optional.
    |
    */

    'route-name' => 'shrnkr.redirect',

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Here you can define the middleware for the Shrinkr routes. By default,
    | it includes a throttle setting, allowing up to 60 requests per minute.
    | Adjust this or add additional middleware as needed.
    |
    */

    'middleware' => ['throttle:60,1'],

    /*
    |--------------------------------------------------------------------------
    | Logger
    |--------------------------------------------------------------------------
    |
    | This setting specifies the logger class used to log each redirection
    | event. The default implementation, LogToFile, records logs in a file.
    | You may replace this with any logger class that meets your needs.
    |
    */

    'logger' => LogToFile::class,

    /*
    |--------------------------------------------------------------------------
    | Model Classes
    |--------------------------------------------------------------------------
    |
    | The models used by Shrinkr. You can customize these to use your own
    | models. Ensure that they implement the required methods or interfaces
    | used by the package.
    |
    */

    'models' => [

        // The user model, required for compatibility with authentication.
        'user' => Authenticatable::class,

        // The URL model representing each shortened URL.
        'url' => Url::class,

        // The model used to log each redirection event.
        'redirect-log' => RedirectLog::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the RESTful API endpoints for Shrinkr. The API provides
    | programmatic access to create, read, update, and delete shortened URLs.
    | You can enable/disable the API and customize its prefix and middleware.
    |
    */

    'api' => [

        // Enable or disable API routes
        'enabled' => true,

        // API route prefix (e.g., /api/shrinkr/urls)
        'prefix' => 'api/shrinkr',

        // Middleware applied to API routes. Add your own authentication,
        // rate limiting, or other middleware as needed.
        // Example: ['api', 'auth:sanctum', 'throttle:60,1']
        'middleware' => ['api'],

        // Route name prefix for API routes (e.g., shrinkr.api.urls.index)
        'route_name_prefix' => 'shrinkr.api',
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhooks Configuration
    |--------------------------------------------------------------------------
    |
    | Webhooks allow you to receive HTTP callbacks when certain events occur,
    | such as when a URL is accessed or expires. Configure webhook settings,
    | including middleware and signature verification.
    |
    */

    'webhooks' => [

        // Enable or disable webhook functionality
        'enabled' => true,

        // Middleware for webhook management endpoints
        'middleware' => ['api'],

        // Secret key for webhook signature verification (HMAC-SHA256)
        // Set this in your .env file: SHRINKR_WEBHOOK_SECRET=your-secret-key
        'secret' => env('SHRINKR_WEBHOOK_SECRET'),

        // Maximum retry attempts for failed webhook deliveries
        'max_retries' => 3,

        // Timeout for webhook HTTP requests (in seconds)
        'timeout' => 10,

        // Events that can trigger webhooks
        'events' => [
            'url.accessed',
            'url.expired',
            'url.created',
            'url.updated',
            'url.deleted',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications Configuration
    |--------------------------------------------------------------------------
    |
    | Configure third-party notification channels for URL events.
    | Shrinkr can send notifications to Slack, Discord, email, and more.
    |
    */

    'notifications' => [

        // Enable or disable notifications
        'enabled' => true,

        // Slack notifications
        'slack' => [
            'enabled' => env('SHRINKR_SLACK_ENABLED', false),
            'webhook_url' => env('SHRINKR_SLACK_WEBHOOK_URL'),
        ],

        // Discord notifications
        'discord' => [
            'enabled' => env('SHRINKR_DISCORD_ENABLED', false),
            'webhook_url' => env('SHRINKR_DISCORD_WEBHOOK_URL'),
        ],

        // Email notifications
        'email' => [
            'enabled' => env('SHRINKR_EMAIL_ENABLED', false),
            'recipients' => env('SHRINKR_EMAIL_RECIPIENTS', ''), // Comma-separated emails
        ],
    ],

];
