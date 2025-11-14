# Security

Security is a top priority for Shrinkr. This guide covers security best practices, vulnerability reporting, and secure implementation patterns.

## Security Best Practices

### URL Validation

Always validate URLs before shortening:

```php
use Illuminate\Support\Facades\Validator;

$validator = Validator::make($request->all(), [
    'url' => 'required|url|max:2048',
    'custom_slug' => 'nullable|alpha_dash|max:50',
]);

if ($validator->fails()) {
    return response()->json(['errors' => $validator->errors()], 422);
}
```

### Blacklist Dangerous Domains

Block malicious or sensitive domains:

```php
namespace App\Services;

class UrlValidator
{
    protected array $blacklistedDomains = [
        'localhost',
        '127.0.0.1',
        'internal.company.com',
    ];

    public function isAllowed(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return !in_array($host, $this->blacklistedDomains);
    }
}
```

Usage:

```php
$validator = new UrlValidator();

if (!$validator->isAllowed($originalUrl)) {
    throw new InvalidUrlException('This domain is not allowed');
}

$shortUrl = Shrinkr::shorten($originalUrl, $user);
```

### Rate Limiting

Implement strict rate limiting to prevent abuse:

```php
// config/shrinkr.php
return [
    'middleware' => [
        'throttle:60,1', // 60 requests per minute
    ],
];
```

Custom rate limiting:

```php
// routes/web.php
Route::middleware(['throttle:create-url'])->group(function () {
    Route::post('/shorten', [UrlController::class, 'store']);
});

// app/Providers/RouteServiceProvider.php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('create-url', function (Request $request) {
    return $request->user()
        ? Limit::perMinute(100)->by($request->user()->id)
        : Limit::perMinute(10)->by($request->ip());
});
```

### Authentication & Authorization

Require authentication for URL creation:

```php
// config/shrinkr.php
return [
    'middleware' => [
        'auth',
        'verified',
        'throttle:60,1',
    ],
];
```

Implement authorization policies:

```php
// app/Policies/UrlPolicy.php
namespace App\Policies;

use App\Models\User;
use CleaniqueCoders\Shrinkr\Models\Url;

class UrlPolicy
{
    public function update(User $user, Url $url): bool
    {
        return $user->id === $url->user_id;
    }

    public function delete(User $user, Url $url): bool
    {
        return $user->id === $url->user_id || $user->isAdmin();
    }
}
```

Register policy:

```php
// app/Providers/AuthServiceProvider.php
protected $policies = [
    Url::class => UrlPolicy::class,
];
```

Use in controllers:

```php
public function update(Request $request, Url $url)
{
    $this->authorize('update', $url);

    return Shrinkr::update($url, $request->validated());
}
```

### SQL Injection Prevention

Shrinkr uses Eloquent ORM, which prevents SQL injection by default. However, always use parameter binding:

```php
// Good - Uses parameter binding
Url::where('shortened_url', $slug)->first();

// Bad - Vulnerable to SQL injection
DB::select("SELECT * FROM urls WHERE shortened_url = '{$slug}'");
```

### XSS Protection

Escape output in views:

```blade
{{-- Blade templates automatically escape --}}
<a href="{{ $url->original_url }}">
    {{ $url->shortened_url }}
</a>

{{-- Manual escaping if needed --}}
<div>{{ e($url->custom_slug) }}</div>
```

Validate and sanitize input:

```php
$validated = $request->validate([
    'custom_slug' => 'alpha_dash|max:50',
    'original_url' => 'url|max:2048',
]);
```

### CSRF Protection

Laravel's CSRF protection is enabled by default. Ensure forms include CSRF tokens:

```blade
<form method="POST" action="/shorten">
    @csrf
    <input type="url" name="url" required>
    <button type="submit">Shorten</button>
</form>
```

For API requests, use Sanctum or Passport:

```php
// api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/shorten', [UrlController::class, 'store']);
});
```

### Secure Headers

Add security headers in middleware:

```php
// app/Http/Middleware/SecurityHeaders.php
namespace App\Http\Middleware;

use Closure;

class SecurityHeaders
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }
}
```

Register in `app/Http/Kernel.php`:

```php
protected $middleware = [
    \App\Http\Middleware\SecurityHeaders::class,
];
```

## Data Privacy

### IP Address Anonymization

Anonymize IP addresses for GDPR compliance:

```php
namespace App\Services;

class AnonymousLogger implements Logger
{
    public function log(Url $url, Request $request, Agent $agent): void
    {
        RedirectLog::create([
            'url_id' => $url->id,
            'ip' => $this->anonymizeIp($request->ip()),
            'browser' => $agent->browser(),
            'platform' => $agent->platform(),
            'device' => $agent->device(),
        ]);
    }

    protected function anonymizeIp(string $ip): string
    {
        // IPv4: Mask last octet
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\.\d+$/', '.0', $ip);
        }

        // IPv6: Mask last 80 bits
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return substr($ip, 0, strpos($ip, ':', strpos($ip, ':') + 1)) . '::';
        }

        return $ip;
    }
}
```

### Data Retention

Implement automatic data cleanup:

```php
// app/Console/Commands/CleanupOldLogs.php
namespace App\Console\Commands;

use CleaniqueCoders\Shrinkr\Models\RedirectLog;
use Illuminate\Console\Command;

class CleanupOldLogs extends Command
{
    protected $signature = 'shrinkr:cleanup-logs {--days=90}';
    protected $description = 'Delete redirect logs older than specified days';

    public function handle()
    {
        $days = $this->option('days');

        $deleted = RedirectLog::where('created_at', '<', now()->subDays($days))
            ->delete();

        $this->info("Deleted {$deleted} old log entries.");
    }
}
```

Schedule in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('shrinkr:cleanup-logs --days=90')->weekly();
}
```

### Sensitive Data Handling

Don't store unnecessary sensitive data:

```php
// Good - Only store necessary data
RedirectLog::create([
    'url_id' => $url->id,
    'ip' => $this->anonymizeIp($request->ip()),
    'browser' => $agent->browser(),
]);

// Bad - Storing all headers may include auth tokens
RedirectLog::create([
    'url_id' => $url->id,
    'headers' => $request->headers->all(), // May include sensitive data
]);
```

## Vulnerability Reporting

### Reporting Security Issues

**DO NOT** open public issues for security vulnerabilities.

Report security vulnerabilities via email to:
**[security@cleaniquecoders.com](mailto:security@cleaniquecoders.com)**

### What to Include

When reporting a security issue, include:

1. **Description** - Clear description of the vulnerability
2. **Impact** - Potential security impact
3. **Steps to Reproduce** - Detailed reproduction steps
4. **Proof of Concept** - Code or screenshots demonstrating the issue
5. **Suggested Fix** - Proposed solution (if any)
6. **Contact Info** - How we can reach you for follow-up

### Example Report

```
Subject: [SECURITY] SQL Injection in URL Search

Description:
The URL search functionality is vulnerable to SQL injection through
the 'query' parameter.

Impact:
An attacker can extract sensitive data from the database or modify
existing records.

Steps to Reproduce:
1. Navigate to /search
2. Enter the following in the search box: '; DROP TABLE urls; --
3. Submit the form

Proof of Concept:
[Screenshot or code demonstrating the vulnerability]

Suggested Fix:
Use parameter binding instead of string concatenation in the search
query.

Contact: researcher@example.com
```

### Response Timeline

- **24 hours** - Initial acknowledgment
- **7 days** - Preliminary assessment
- **30 days** - Fix development and testing
- **90 days** - Public disclosure (after fix is released)

## Security Checklist

### For Production Deployment

- [ ] **HTTPS Enabled** - All traffic encrypted
- [ ] **Environment Variables** - Sensitive config in `.env`
- [ ] **Debug Mode Off** - `APP_DEBUG=false`
- [ ] **Rate Limiting** - Throttle configured
- [ ] **Authentication** - Required for URL creation
- [ ] **Authorization** - Policies implemented
- [ ] **Input Validation** - All inputs validated
- [ ] **Output Escaping** - XSS prevention enabled
- [ ] **CSRF Protection** - Tokens required
- [ ] **Security Headers** - Headers configured
- [ ] **Database Backups** - Regular backups scheduled
- [ ] **Logging** - Security events logged
- [ ] **Updates** - Dependencies up to date

### Regular Maintenance

- [ ] **Update Dependencies** - Monthly security updates
- [ ] **Review Logs** - Check for suspicious activity
- [ ] **Audit Permissions** - Review user access
- [ ] **Test Backups** - Verify backup integrity
- [ ] **Rotate Secrets** - Update API keys and passwords
- [ ] **Monitor Performance** - Watch for DDoS attempts

## Common Vulnerabilities

### Open Redirect

**Vulnerability:**
```php
// Bad - Redirects to any URL
return redirect($request->input('url'));
```

**Fix:**
```php
// Good - Validate before redirect
$url = Url::where('shortened_url', $slug)->firstOrFail();

if (!$url->hasExpired() && $url->checkHealth()) {
    return redirect($url->original_url);
}

abort(404);
```

### Mass Assignment

**Vulnerability:**
```php
// Bad - Allows any field to be set
Url::create($request->all());
```

**Fix:**
```php
// Good - Only allow specific fields
Url::create($request->only(['original_url', 'custom_slug', 'user_id']));

// Or use validation
$validated = $request->validate([
    'original_url' => 'required|url',
    'custom_slug' => 'nullable|alpha_dash',
]);

Url::create($validated);
```

### Insecure Direct Object Reference (IDOR)

**Vulnerability:**
```php
// Bad - No authorization check
public function delete($id)
{
    Url::findOrFail($id)->delete();
}
```

**Fix:**
```php
// Good - Check authorization
public function delete(Url $url)
{
    $this->authorize('delete', $url);

    $url->delete();
}
```

## Security Updates

### Staying Informed

- Watch the [GitHub repository](https://github.com/cleaniquecoders/shrinkr) for security advisories
- Subscribe to security mailing list
- Follow [@cleaniquecoders](https://twitter.com/cleaniquecoders) on Twitter

### Applying Updates

```bash
# Check for updates
composer outdated

# Update Shrinkr
composer update cleaniquecoders/shrinkr

# Update all dependencies
composer update

# Review CHANGELOG
cat vendor/cleaniquecoders/shrinkr/CHANGELOG.md
```

## Security Resources

- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Guide](https://phptherightway.com/#security)
- [Composer Security Checker](https://github.com/Roave/SecurityAdvisories)

## Next Steps

- [Testing](01-testing.md) - Test security features
- [Contributing](02-contributing.md) - Report security issues
- [Configuration](../02-configuration/README.md) - Secure configuration
