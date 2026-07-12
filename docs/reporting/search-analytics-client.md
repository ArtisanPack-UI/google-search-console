---
title: SearchAnalyticsClient
---

# `SearchAnalyticsClient`

The typed wrapper over Google's `searchAnalytics.query` endpoint. Registered as a singleton in the container.

FQCN: `ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsClient`.

## Constructor

```php
public function __construct(
    protected ConfigRepository $config,
    protected HttpFactory $http,
    protected ?TokenManager $tokens = null,
    protected ?CacheRepository $cache = null,
)
```

The service provider builds it with:

- `$config` — Laravel's `Config` repository (reads `google-search-console.reporting.*`).
- `$http` — Laravel's `Http` client factory.
- `$tokens` — the base package's `TokenManager`, or `null` when the base package isn't installed.
- `$cache` — Laravel's default cache store (`app('cache.store')`).

`$tokens = null` is a legal state — it corresponds to "the base package isn't installed", and every call will throw `BaseNotInstalledException` until the base package is present.

## Public methods

### `query( SearchAnalyticsRequest $request, GoogleConnection $connection, ?string $siteUrl = null ): SearchAnalyticsResponse`

Execute a `searchAnalytics.query` request against the configured (or overridden) property.

- `$request` — a `SearchAnalyticsRequest` describing dimensions, date range, filters, and paging.
- `$connection` — the connected Google account whose token authorizes the call.
- `$siteUrl` — optional per-call override. Falls back to `config('google-search-console.reporting.site_url')` when null. **The controllers deliberately never pass this** — see [[HTTP Endpoints#Shared behavior]].

Returns a `SearchAnalyticsResponse` DTO. Throws:

| Exception | When |
|---|---|
| `BaseNotInstalledException` | `BaseInstalled::check() === false`, or `$tokens === null`. |
| `ReportingException::missingConfiguration()` | Neither `$siteUrl` nor config supplied a value. |
| `ReportingException::authenticationFailed()` | `TokenManager::getValidAccessToken()` threw a `TokenRefreshException`. Original exception is available via `getPrevious()`. |
| `ReportingException::transportFailure()` | Guzzle raised a `ConnectionException`. Original available via `getPrevious()`. |
| `ReportingException::apiError()` | Google returned a non-2xx status. Message includes the status code and response body. |

### `isAvailable(): bool`

`true` when both `BaseInstalled::check()` is true and `$tokens !== null`. False otherwise. The service provider uses this to decide whether to boot the routes and Livewire component registrations.

## What it caches

Successful responses are cached with a per-connection, per-query-payload key:

```
google-search-console:query:<sha256( siteUrl | connectionId | jsonPayload )>
```

TTL from `google-search-console.reporting.cache_ttl` (default `300` seconds). Set to `0` to disable. Details: [[Reporting/Caching]].

## What it does not cache

- **Failed responses.** A 4xx or 5xx from Google always round-trips on the next call — no negative caching, no backoff.
- **Requests where `$cache === null`.** The service provider passes `app('cache.store')` by default, so this only bites custom bindings.

## Timeout handling

```php
$timeoutRaw = $this->config->get( 'google-search-console.reporting.timeout', 30 );
$timeout    = is_numeric( $timeoutRaw ) && (int) $timeoutRaw > 0 ? (int) $timeoutRaw : 30;
```

Any of `0`, `null`, `false`, or a non-numeric string falls back to `30`. Guzzle treats `timeout(0)` as "no timeout" — a stray `env('GSC_TIMEOUT')` with the var unset would otherwise hang the worker on a stuck endpoint. The guard specifically protects against that.

## Container binding

```php
$this->app->singleton( SearchAnalyticsClient::class, fn ( Application $app ): SearchAnalyticsClient => new SearchAnalyticsClient(
    $app[ 'config' ],
    $app->make( HttpFactory::class ),
    BaseInstalled::check() ? $app->make( TokenManager::class ) : null,
    $app->make( 'cache.store' ),
) );
```

**Singleton**, so a runtime `config()->set('google-search-console.reporting.site_url', '...')` only affects the client's per-call behavior (the property URL is re-read on every call). Swapping the client itself per tenant requires `->instance()` — see [[Reporting#Multi-tenant apps]].

## Example: custom dimensions

```php
use ArtisanPackUI\GoogleSearchConsole\Reporting\DateRange;
use ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsRequest;

$response = app( SearchAnalyticsClient::class )->query(
    new SearchAnalyticsRequest(
        dateRange:  DateRange::lastDays( 28 ),
        dimensions: [ 'query', 'device' ],
        rowLimit:   500,
    ),
    $connection,
);

foreach ( $response->rows() as $row ) {
    echo "{$row['query']} on {$row['device']}: {$row['clicks']} clicks\n";
}
```

## Example: dimension filters

```php
use ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsRequest;

$response = app( SearchAnalyticsClient::class )->query(
    new SearchAnalyticsRequest(
        dateRange:  DateRange::lastDays( 28 ),
        dimensions: [ 'query' ],
        dimensionFilterGroups: [
            [
                'filters' => [
                    [
                        'dimension'  => 'country',
                        'operator'   => 'equals',
                        'expression' => 'usa',
                    ],
                    [
                        'dimension'  => 'page',
                        'operator'   => 'contains',
                        'expression' => '/blog/',
                    ],
                ],
            ],
        ],
        rowLimit:   500,
    ),
    $connection,
);
```

## See also

- [[API Reference/Search Analytics Client]] — full method reference.
- [[API Reference/Search Analytics Request]] — request payload builder.
- [[API Reference/Search Analytics Response]] — response accessor.
- [[Reporting/Fetchers]] — higher-level helpers that build the canned requests.
