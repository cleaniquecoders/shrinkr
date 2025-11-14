<?php

use CleaniqueCoders\Shrinkr\Http\Controllers\Api\AnalyticsController;
use CleaniqueCoders\Shrinkr\Http\Controllers\Api\UrlController;
use CleaniqueCoders\Shrinkr\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Shrinkr API Routes
|--------------------------------------------------------------------------
|
| These routes provide a RESTful API for managing shortened URLs,
| analytics, and webhooks. All routes are prefixed with the value
| configured in config/shrinkr.php under 'api.prefix'.
|
| Middleware is configurable via config/shrinkr.php under 'api.middleware'.
| By default, only the 'api' middleware is applied. You should add your
| own authentication and authorization middleware as needed.
|
*/

$prefix = config('shrinkr.api.prefix', 'api/shrinkr');
$middleware = config('shrinkr.api.middleware', ['api']);
$routeNamePrefix = config('shrinkr.api.route_name_prefix', 'shrinkr.api');

if (config('shrinkr.api.enabled', true)) {
    Route::prefix($prefix)
        ->middleware($middleware)
        ->name($routeNamePrefix.'.')
        ->group(function () {
            // URL Management Routes
            Route::get('/urls', [UrlController::class, 'index'])->name('urls.index');
            Route::post('/urls', [UrlController::class, 'store'])->name('urls.store');
            Route::get('/urls/stats', [UrlController::class, 'stats'])->name('urls.stats');
            Route::get('/urls/{id}', [UrlController::class, 'show'])->name('urls.show');
            Route::patch('/urls/{id}', [UrlController::class, 'update'])->name('urls.update');
            Route::put('/urls/{id}', [UrlController::class, 'update'])->name('urls.update.put');
            Route::delete('/urls/{id}', [UrlController::class, 'destroy'])->name('urls.destroy');

            // Analytics Routes
            Route::get('/urls/{id}/analytics', [AnalyticsController::class, 'show'])->name('analytics.show');
            Route::get('/urls/{id}/analytics/summary', [AnalyticsController::class, 'summary'])->name('analytics.summary');

            // Webhook Management Routes (if webhooks are enabled)
            if (config('shrinkr.webhooks.enabled', true)) {
                Route::prefix('webhooks')
                    ->name('webhooks.')
                    ->group(function () {
                        Route::get('/', [WebhookController::class, 'index'])->name('index');
                        Route::post('/', [WebhookController::class, 'store'])->name('store');
                        Route::get('/{id}', [WebhookController::class, 'show'])->name('show');
                        Route::patch('/{id}', [WebhookController::class, 'update'])->name('update');
                        Route::put('/{id}', [WebhookController::class, 'update'])->name('update.put');
                        Route::delete('/{id}', [WebhookController::class, 'destroy'])->name('destroy');
                        Route::post('/{id}/test', [WebhookController::class, 'test'])->name('test');
                        Route::get('/{id}/calls', [WebhookController::class, 'calls'])->name('calls');
                    });
            }
        });
}
