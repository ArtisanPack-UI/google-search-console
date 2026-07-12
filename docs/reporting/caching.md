---
title: Caching
---

# Caching

`SearchAnalyticsClient` caches every **successful** API response. This is a straightforward speed and quota optimization: repeat calls with the same dates + dimensions for the same connection short-circuit to the cache instead of round-tripping to Google.

## How it works

On every `SearchAnalyticsClient::query()` call:

1. Build a cache key from `sha256( siteUrl | connectionId | json( payload ) )`.
2. If `cache_ttl > 0` and the key is warm, return the cached response.
3. Otherwise call Google, and — on 2xx — store the raw JSON body under the key with `cache_ttl` seconds.

## What's cached

- **Only successful responses.** 4xx and 5xx responses are never cached — the next call always round-trips.
- **The raw response body**, not the parsed `SearchAnalyticsResponse`. The DTO is rebuilt on each read.

## Cache key format

```
google-search-console:query:<sha256( siteUrl | connectionId | jsonPayload )>
```

Where:

- `siteUrl` — the resolved property URL for this call (config or `$siteUrl` override).
- `connectionId` — `$connection->getKey() ?? $connection->google_user_id ?? 'anon'`. This is why distinct users cannot see each other's cached rows.
- `jsonPayload` — `json_encode( $request->toApiPayload() )`, so a different date range, dimension set, filter set, or paging offset gets its own key.

The prefix `google-search-console:query:` makes it easy to purge only this package's cache:

```php
Cache::store()->forget( 'google-search-console:query:*' );  // if your store supports wildcard forget
```

Otherwise use tagged caches (see below).

## Configuration

Single knob: `google-search-console.reporting.cache_ttl` (default `300` seconds).

```php
// config/google-search-console.php
'reporting' => [
    'cache_ttl' => 300,  // 5 minutes
],
```

Set to `0` to disable caching entirely — every call round-trips to Google.

## When to lower the TTL

- **Dashboards displayed to end users.** 300s is fine for admin dashboards refreshed on demand; drop to 60s if users notice stale numbers on visible screens.
- **A/B tests keyed on click volume.** Fresher numbers → tighter feedback loops.

## When to raise the TTL

- **Nightly digests / weekly emails.** Cache for hours; the underlying data updates once per day at most.
- **Rate limit pressure.** Search Console has a per-user quota. Longer cache = fewer API calls per user.

## When to disable

- **Tests** — set `config( [ 'google-search-console.reporting.cache_ttl' => 0 ] )` in `beforeEach` if you want your `Http::fake` sequence to be re-consumed on each call. (The package's own tests do this only where needed.)
- **Deterministic reproduction** — turning caching off gives you `Http::assertSentCount( N )` control.

## Which cache store is used

The default store: `app( 'cache.store' )`. Whatever driver `CACHE_STORE` (or the legacy `CACHE_DRIVER`) points at.

- `array` in tests — plain in-process memory; auto-clears between tests.
- `file` in development — simple, no infra.
- `redis` in production — recommended; wildcard forget works via Redis's `KEYS`.

If you want a dedicated store (e.g., a memory-backed cache just for GSC), rebind the fourth constructor argument:

```php
use ArtisanPackUI\Google\Tokens\TokenManager;
use ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsClient;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Cache;

$this->app->singleton( SearchAnalyticsClient::class, fn ( $app ) => new SearchAnalyticsClient(
    config: $app[ 'config' ],
    http:   $app->make( HttpFactory::class ),
    tokens: $app->make( TokenManager::class ),
    cache:  Cache::store( 'gsc-cache' ),  // a dedicated cache connection
) );
```

## Manually invalidating

Two patterns.

### Wildcard forget (Redis)

```php
Redis::connection()->keys( 'google-search-console:query:*' )
    ->each( fn ( string $key ) => Cache::store()->forget( $key ) );
```

### Force-refresh the next call

Set `cache_ttl = 0` for that single request:

```php
config( [ 'google-search-console.reporting.cache_ttl' => 0 ] );

$data = $fetcher->fetch( $connection, DateRange::lastDays( 28 ) );

// Reset — or don't, if you want to disable for the rest of the request.
```

The client re-reads the config value on every call, so this works even without container flushing.

## What isn't cached

- **Token refresh** — the base package's `TokenManager` handles that separately, refreshing 60s before the current token expires.
- **The parsed `SearchAnalyticsResponse` DTO** — the cache holds the raw JSON. Rebuilding the DTO from cached JSON is cheap.
- **Failed responses** — an API 4xx / 5xx surfaces as a `ReportingException` on the first attempt; the next attempt round-trips again.

## Testing cache behavior

The package's own `tests/Feature/SearchAnalyticsClientTest.php` has the reference test:

```php
it( 'caches successful query responses for the configured TTL', function (): void {
    config()->set( 'google-search-console.reporting.cache_ttl', 300 );

    Http::fake( [ '*' => Http::response( [ 'rows' => [ /* ... */ ] ], 200 ) ] );

    $client = new SearchAnalyticsClient(
        config: app( 'config' ),
        http:   app( HttpFactory::class ),
        tokens: makeGscStubTokenManager( 'x' ),
        cache:  Cache::store(),
    );

    $first  = $client->query( $request, $connection );
    $second = $client->query( $request, $connection );

    expect( $first->rows() )->toBe( $second->rows() );

    Http::assertSentCount( 1 );  // second call short-circuited
} );
```
