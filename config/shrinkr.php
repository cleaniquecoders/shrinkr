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

];
