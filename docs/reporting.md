---
title: Reporting
---

# Reporting

The server-side surface for calling Google Search Console. Every UI shipped by this package is a thin renderer over these primitives — you can use them directly from controllers, jobs, artisan commands, or any other server-side context.

Two layers:

- **Low level**: `SearchAnalyticsClient` — a typed wrapper over Google's `searchAnalytics.query` endpoint.
- **High level**: the three fetchers — `PerformanceOverviewFetcher`, `TopQueriesFetcher`, `TopPagesFetcher` — that build the exact queries the shipped UIs need and hand back typed DTOs.

Sub-pages:

- [[Reporting/Search Analytics Client|`SearchAnalyticsClient`]] — the client itself.
- [[Reporting/Fetchers|Fetchers]] — the three high-level fetchers.
- [[Reporting/Date Range|`DateRange`]] — the value object every fetcher takes.
- [[Reporting/Caching|Caching]] — how the client caches, cache keys, when to disable.

## Quick reference

```php
use ArtisanPackUI\GoogleSearchConsole\Facades\GoogleSearchConsole;
use ArtisanPackUI\GoogleSearchConsole\Reporting\DateRange;
use ArtisanPackUI\GoogleSearchConsole\Reporting\PerformanceOverviewFetcher;

// 1. Confirm the reporting side is usable.
if ( ! googleSearchConsole()->hasReporting() ) {
    // artisanpack-ui/google isn't installed. Bail cleanly.
    return;
}

// 2. Resolve the client (singleton).
$client = googleSearchConsole()->client();

// 3. Wrap it in a fetcher for one of the three canned reports.
$fetcher = new PerformanceOverviewFetcher( $client );
$data    = $fetcher->fetch( $connection, DateRange::lastDays( 28 ) );

// 4. Read the DTO.
$data->totals;   // ['clicks' => 1234.0, 'impressions' => 45678.0, ...]
$data->trend;    // list of daily rows
$data->hasData;  // false when Google returned zero rows
```

## When to reach for the client directly

`SearchAnalyticsClient::query()` is the escape hatch. Use it when:

- You need a dimension the fetchers don't cover (`country`, `device`, `searchAppearance`, or combos like `[query, page]`).
- You want to apply `dimensionFilterGroups` (filter by country, page substring, etc.).
- You need `searchType` (`web`, `image`, `video`, `news`, …) or `dataState` (`all` vs `final`).

Otherwise, the fetchers give you typed DTOs and less boilerplate.

```php
use ArtisanPackUI\GoogleSearchConsole\Reporting\DateRange;
use ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsRequest;

$response = googleSearchConsole()->client()->query(
    new SearchAnalyticsRequest(
        dateRange:  DateRange::lastDays( 28 ),
        dimensions: [ 'country', 'device' ],
        rowLimit:   100,
    ),
    $connection,
);

foreach ( $response->rows() as $row ) {
    // $row['country'], $row['device'], $row['clicks'], ...
}
```

## What the client does not do

- **Retries.** A `502` or `429` from Google surfaces as a `ReportingException` — you decide whether to retry, back off, or give up. Wrap in a queued job with `$tries` if you want automatic retry.
- **Rate limiting.** Search Console's quotas are per-user + per-property + per-day. The client won't help you stay under them — throttle upstream.
- **Pagination beyond `startRow`.** `searchAnalytics.query` caps rowLimit at 25,000 per request; paginate by passing `startRow` on subsequent calls if you need the full tail.

## Multi-tenant apps

The `SearchAnalyticsClient` is registered as a singleton with the property URL bound from config. In multi-tenant apps that share one Google account across tenants, override the binding in a tenant-scoped middleware:

```php
namespace App\Http\Middleware;

use ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsClient;
use ArtisanPackUI\Google\Tokens\TokenManager;
use Closure;
use Illuminate\Http\Client\Factory as HttpFactory;

class ResolveGscSiteUrl
{
    public function handle( $request, Closure $next )
    {
        $tenant = $request->user()?->currentTenant;

        if ( $tenant ) {
            config( [
                'google-search-console.reporting.site_url' => $tenant->gsc_site_url,
            ] );

            // The singleton was built at boot from the previous config value.
            // Rebuild it so the new value is honored.
            app()->instance( SearchAnalyticsClient::class, new SearchAnalyticsClient(
                config: app( 'config' ),
                http:   app( HttpFactory::class ),
                tokens: app( TokenManager::class ),
                cache:  app( 'cache.store' ),
            ) );
        }

        return $next( $request );
    }
}
```

Register the middleware globally (or on the route group hitting the endpoints) and every fetcher call downstream will use the tenant's property. The three JSON endpoints already **ignore** any `?site_url` query string override, so cross-tenant leakage isn't possible via the URL.

## Testing

Every reporting call is HTTP against Google's endpoint, so `Http::fake()` is enough. See [[Testing]] for the full patterns.

```php
use Illuminate\Support\Facades\Http;

Http::fake( [
    '*' => Http::response( [
        'rows' => [
            [ 'keys' => [ 'buy shoes' ], 'clicks' => 42, 'impressions' => 500, 'ctr' => 0.084, 'position' => 3.2 ],
        ],
    ], 200 ),
] );

$response = googleSearchConsole()->client()->query( $request, $connection );

expect( $response->rows()[0] )->toMatchArray( [
    'query'  => 'buy shoes',
    'clicks' => 42.0,
] );
```

---
Continue to [[Scopes]] →
