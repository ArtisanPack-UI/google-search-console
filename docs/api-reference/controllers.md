---
title: Controllers
---

# Controllers

Class-level reference. Behavior guide: [HTTP Endpoints](HTTP-Endpoints).

Every controller is a single-action invokable class. FQCNs live under `ArtisanPackUI\GoogleSearchConsole\Http\Controllers\*`.

## `PerformanceOverviewController`

```php
public function __invoke( Request $request ): JsonResponse
```

- **Route name**: `google-search-console.performance`
- **Method**: `GET`
- **URI**: `/google-search-console/performance`
- **Fetcher**: `PerformanceOverviewFetcher`
- **Return type**: JSON, shape defined by `PerformanceOverviewData::toArray()`.

Query params: `?days=28` (int, clamped to `1..DateRange::MAX_DAYS`).

## `TopQueriesController`

```php
public function __invoke( Request $request ): JsonResponse
```

- **Route name**: `google-search-console.top-queries`
- **Method**: `GET`
- **URI**: `/google-search-console/top-queries`
- **Fetcher**: `TopQueriesFetcher`
- **Return type**: JSON, shape defined by `TopQueriesData::toArray()`.

Query params: `?days=28&limit=50` (both int, `days` clamped to `1..DateRange::MAX_DAYS`, `limit` clamped to `1..1000`).

## `TopPagesController`

```php
public function __invoke( Request $request ): JsonResponse
```

- **Route name**: `google-search-console.top-pages`
- **Method**: `GET`
- **URI**: `/google-search-console/top-pages`
- **Fetcher**: `TopPagesFetcher`
- **Return type**: JSON, shape defined by `TopPagesData::toArray()`.

Query params: `?days=28&limit=50` (same clamps as top-queries).

## Shared error handling

Every controller returns:

- `401` `{ error: 'unauthenticated', message }` — no authenticated user.
- `409` `{ error: 'not_connected', message }` — user has no connected `GoogleConnection`.
- `501` `{ error: 'base_not_installed', message, baseInstalled: false }` — `BaseInstalled::check()` failed.
- `502` `{ error: 'reporting_error', message }` — `ReportingException` from the client.

The three controllers deliberately ignore any `?site_url` query string. The site URL comes from `config('google-search-console.reporting.site_url')` only — accepting a caller-supplied override would let one authenticated user query any property the shared Google account owns. See [HTTP Endpoints](HTTP-Endpoints#shared-behavior) and [Reporting](Reporting#multi-tenant-apps).

## Testing

```php
$this->actingAs( $user )
    ->getJson( '/google-search-console/performance?days=7' )
    ->assertOk()
    ->assertJsonPath( 'totals.clicks', 42.0 );
```

Reference test: `tests/Feature/ControllersTest.php`. Full patterns: [Testing](Testing).

## See also

- [HTTP Endpoints](HTTP-Endpoints) — behavior + payload shapes.
- [API Reference/Fetchers](API-Reference-Fetchers) — fetchers the controllers dispatch to.
- [API Reference/Data Objects](API-Reference-Data-Objects) — DTOs whose `toArray()` is returned as JSON.
