# Changelog

All notable changes to `shrinkr` will be documented in this file.

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
