---
title: Exceptions
---

# Exceptions

Two exception classes live under `ArtisanPackUI\GoogleSearchConsole\Exceptions\*`.

## `BaseNotInstalledException`

Thrown when the `artisanpack-ui/google` base package isn't loadable or hasn't been wired.

FQCN: `ArtisanPackUI\GoogleSearchConsole\Exceptions\BaseNotInstalledException`.

Extends `RuntimeException`.

### `BaseNotInstalledException::forReporting(): self`

Named constructor with a human-readable message:

> The `artisanpack-ui/google` base package is required for Search Console reporting but is not installed or is not fully bootable.

Thrown by:

- `SearchAnalyticsClient::query()` — when `BaseInstalled::check()` fails or `$tokens === null`.
- `SearchAnalyticsClient::ensureBaseInstalled()`.

Livewire components catch it and flip `$baseInstalled` to `false` rather than surfacing the message. The three HTTP controllers surface it as `501` with `error = 'base_not_installed'`.

## `ReportingException`

Thrown when a Search Console API call fails — config error, transport error, auth error, or non-2xx from Google.

FQCN: `ArtisanPackUI\GoogleSearchConsole\Exceptions\ReportingException`.

Extends `RuntimeException`.

### Named constructors

Every named constructor sets the message and (where applicable) chains the underlying exception via `$previous`.

#### `ReportingException::missingConfiguration( string $key ): self`

Config value missing or empty. Message references the config key so callers know what to set.

Thrown by `SearchAnalyticsClient::query()` when `google-search-console.reporting.site_url` is neither passed nor configured.

#### `ReportingException::authenticationFailed( TokenRefreshException $e ): self`

Token refresh failed. `getPrevious()` returns the original `TokenRefreshException` from the base package's `TokenManager`.

Thrown by `SearchAnalyticsClient::query()` when `TokenManager::getValidAccessToken()` throws.

#### `ReportingException::transportFailure( ConnectionException $e ): self`

Guzzle connection error (DNS failure, timeout, connection refused, etc.). `getPrevious()` returns the original `ConnectionException`.

Thrown by `SearchAnalyticsClient::query()` when the HTTP client's `->post()` throws.

#### `ReportingException::apiError( int $status, string $body ): self`

Google returned a non-2xx response. Message includes the status code and (truncated) response body for diagnosis.

Thrown by `SearchAnalyticsClient::query()` after `!$response->successful()`.

### Handling

The Livewire components' `refresh()` catches `ReportingException` and stores the message on `$errorMessage`. The three HTTP controllers surface it as `502` with `error = 'reporting_error'`.

If you're calling `SearchAnalyticsClient` directly, decide per-case:

```php
try {
    $response = googleSearchConsole()->client()->query( $request, $connection );
} catch ( BaseNotInstalledException $e ) {
    // No base package — degraded mode, skip.
    return null;
} catch ( ReportingException $e ) {
    if ( $prev = $e->getPrevious() ) {
        Log::warning( 'Search Console API failed', [
            'previous' => $prev::class,
            'message'  => $prev->getMessage(),
        ] );
    }
    throw $e;  // let the caller decide whether to retry
}
```

## See also

- [API Reference/Search Analytics Client](API-Reference-Search-Analytics-Client) — the class that throws these.
- [FAQ](FAQ#runtime) — common causes for each exception.
- [Troubleshooting](Troubleshooting) — decoded error messages and fixes.
