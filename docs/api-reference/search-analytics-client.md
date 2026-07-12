---
title: SearchAnalyticsClient
---

# `SearchAnalyticsClient`

FQCN: `ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsClient`

Registered as a singleton. Behaviors and container binding covered in depth at [Reporting/Search Analytics Client](Reporting-Search-Analytics-Client). This page is the class-level reference only.

## Constructor

```php
public function __construct(
    protected ConfigRepository $config,
    protected HttpFactory $http,
    protected ?TokenManager $tokens = null,
    protected ?CacheRepository $cache = null,
)
```

## Public methods

### `query( SearchAnalyticsRequest $request, GoogleConnection $connection, ?string $siteUrl = null ): SearchAnalyticsResponse`

Executes a `searchAnalytics.query` request. Returns a `SearchAnalyticsResponse`.

Throws:

| Exception | Trigger |
|---|---|
| `BaseNotInstalledException::forReporting()` | `BaseInstalled::check() === false` or `$tokens === null`. |
| `ReportingException::missingConfiguration( 'google-search-console.reporting.site_url' )` | Neither `$siteUrl` nor config supplied a value. |
| `ReportingException::authenticationFailed( TokenRefreshException $e )` | `TokenManager::getValidAccessToken()` threw. `getPrevious()` returns the original. |
| `ReportingException::transportFailure( ConnectionException $e )` | Guzzle raised a connection failure. `getPrevious()` returns the original. |
| `ReportingException::apiError( int $status, string $body )` | Google returned non-2xx. Message includes status and body. |

### `isAvailable(): bool`

`true` iff `BaseInstalled::check() === true` and `$tokens !== null`.

## Protected methods

### `cacheKey( GoogleConnection $connection, string $site, array $payload ): string`

Returns `google-search-console:query:<sha256( site | connectionId | jsonPayload )>`.

### `ensureBaseInstalled(): void`

Throws `BaseNotInstalledException::forReporting()` when `BaseInstalled::check() === false`.

## See also

- [Reporting/Search Analytics Client](Reporting-Search-Analytics-Client) — full behavior + examples.
- [Reporting/Caching](Reporting-Caching) — cache TTL, keys, disabling.
- [API Reference/Search Analytics Request](API-Reference-Search-Analytics-Request) — the request DTO.
- [API Reference/Search Analytics Response](API-Reference-Search-Analytics-Response) — the response DTO.
- [API Reference/Exceptions](API-Reference-Exceptions) — `BaseNotInstalledException`, `ReportingException`.
