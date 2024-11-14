# Changelog

All notable changes to `shrinkr` will be documented in this file.

## v1.0.2 - 2024-10-20

### **Monitor URL Health**

The **Link Health Monitoring** feature allows you to **check if URLs are reachable** and mark them as **active or expired**.

#### **Check Health Action**

Use the `CheckUrlHealthAction` to **manually check the health** of a specific URL.

```php
use CleaniqueCoders\Shrinkr\Actions\CheckUrlHealthAction;
use CleaniqueCoders\Shrinkr\Models\Url;

$url = Url::find(1); // Retrieve URL instance

$action = new CheckUrlHealthAction();
$isHealthy = $action->execute($url);

if ($isHealthy) {
    echo "URL is active.";
} else {
    echo "URL is expired.";
}

```
#### **Check Health Command**

Use the Artisan command to **check the health of all URLs** in bulk.

```bash
php artisan shrinkr:check-health

```
This command will:

1. **Check the status** of all URLs.
2. **Mark expired URLs** and dispatch the `UrlExpired` event.
3. **Provide real-time output** on the status of each URL.

Example output:

```
URL abc123 is now marked as active.
URL xyz456 is now marked as expired.
URL health check completed.

```
#### **Schedule the Health Check Command**

You can **automatically run the health check** at regular intervals using Laravel’s scheduler.

In your **`app/Console/Kernel.php`**:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('shrinkr:check-health')->hourly();
}

```
This will ensure that all URLs are **continuously monitored** and marked as expired when necessary.

**Full Changelog**: https://github.com/cleaniquecoders/shrinkr/compare/v1.0.1...v1.0.2

## Expiry & Event - 2024-10-20

- **Shorten URLs** with optional **custom slugs** and **expiry durations**.
  
- **Retrieve original URLs** using the short code with `resolve()`.
  
- **Update URLs**: Modify slugs and expiry times as needed.
  
- **UrlAccessed Event**: Track when a URL is accessed.
  
- **UrlExpired Event**: Trigger actions when a URL expires.
  
- **Expiry Command**:
  
  - **Manually run** with: `php artisan shrinkr:check-expiry`
  - **Schedule it** to run hourly or daily.
  
- **Exception Handling**: Custom exception for **duplicate slugs** (`SlugAlreadyExistsException`).
  

Manage URLs efficiently with **automatic expiry**, **logging**, and **event-based notifications**! 🎉

**Full Changelog**: https://github.com/cleaniquecoders/shrinkr/compare/v1.0.0...v1.0.1

## v1.0.0 - 2024-10-20

**Full Changelog**: https://github.com/cleaniquecoders/shrinkr/commits/v1.0.0
