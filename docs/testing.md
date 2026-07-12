---
title: Testing
---

# Testing

Patterns for testing your own code against `artisanpack-ui/google-search-console`. The package's own suite (Pest, 61 tests, 154 assertions) is the reference implementation — look at `tests/Feature/*` if you want copy-paste-ready examples.

## Test bootstrap

The package's own base test case wires up:

- Orchestra Testbench for the Laravel harness.
- The base `artisanpack-ui/google` service provider (needed for `TokenManager`, `ScopeRegistry`, etc.).
- Livewire's service provider (needed for `Livewire::test()`).
- Local test stubs for `artisanpack-ui/cms-framework` autoloaded into the real cms-framework namespace via `autoload-dev` (so the bridge can be tested without pulling in the framework's transitive deps).
- Static-state resets: `BaseInstalled::reset()`, `CmsFrameworkInstalled::reset()` in setUp/tearDown.

An app-level test that talks to this package doesn't need any of this — install the package normally and Testbench (or your normal Laravel `TestCase`) will bootstrap it.

```php
use ArtisanPackUI\GoogleSearchConsole\GoogleSearchConsoleServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders( $app ): array
    {
        return [
            ArtisanPackUI\Google\GoogleServiceProvider::class,
            Livewire\LivewireServiceProvider::class,
            GoogleSearchConsoleServiceProvider::class,
        ];
    }
}
```

## Fake Google

Every call goes through the standard Laravel HTTP client, so `Http::fake()` is enough. Both endpoint shapes:

### Fake a single query

```php
use Illuminate\Support\Facades\Http;

Http::fake( [
    '*' => Http::response( [
        'rows' => [
            [ 'keys' => [ 'buy shoes' ], 'clicks' => 42, 'impressions' => 500, 'ctr' => 0.084, 'position' => 3.2 ],
        ],
    ], 200 ),
] );
```

### Fake the two-call performance query

The `PerformanceOverviewFetcher` fires two queries — totals first, then trend — in that order:

```php
Http::fakeSequence()
    ->push( [
        'rows' => [
            [ 'keys' => [], 'clicks' => 100, 'impressions' => 1000, 'ctr' => 0.1, 'position' => 8.5 ],
        ],
    ], 200 )
    ->push( [
        'rows' => [
            [ 'keys' => [ '2026-01-01' ], 'clicks' => 40, 'impressions' => 400, 'ctr' => 0.1, 'position' => 8.6 ],
            [ 'keys' => [ '2026-01-02' ], 'clicks' => 60, 'impressions' => 600, 'ctr' => 0.1, 'position' => 8.4 ],
        ],
    ], 200 );
```

### Fake an API error

```php
Http::fake( [
    '*' => Http::response( [ 'error' => 'permission denied' ], 403 ),
] );

// Client will throw ReportingException::apiError( 403, '...' )
```

### Fake a transport error

```php
Http::fake( function (): void {
    throw new Illuminate\Http\Client\ConnectionException( 'connection refused' );
} );

// Client will throw ReportingException::transportFailure( $e )
```

## Testing the client

```php
use ArtisanPackUI\GoogleSearchConsole\Reporting\DateRange;
use ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsClient;
use ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsRequest;
use ArtisanPackUI\GoogleSearchConsole\Support\BaseInstalled;
use Illuminate\Http\Client\Factory as HttpFactory;

BaseInstalled::setForTesting( true );
config()->set( 'google-search-console.reporting.site_url', 'https://example.com/' );

$client = new SearchAnalyticsClient(
    config: app( 'config' ),
    http:   app( HttpFactory::class ),
    tokens: makeGscStubTokenManager( 'test-access-token' ),
);

$response = $client->query(
    new SearchAnalyticsRequest(
        dateRange:  new DateRange( '2026-01-01', '2026-01-07' ),
        dimensions: [ 'query' ],
    ),
    makeGscConnectedConnection(),
);

expect( $response->rows() )->toBe( [
    [ 'query' => 'buy shoes', 'clicks' => 42.0, 'impressions' => 500.0, 'ctr' => 0.084, 'position' => 3.2 ],
] );
```

Where `makeGscStubTokenManager()` and `makeGscConnectedConnection()` are helpers the package's own `tests/Pest.php` defines — copy them into your own `Pest.php` for the same patterns. See the actual source in the package for the working versions.

## Testing the fetchers

```php
use ArtisanPackUI\GoogleSearchConsole\Reporting\PerformanceOverviewFetcher;

Http::fakeSequence()
    ->push( [ 'rows' => [ [ 'keys' => [], 'clicks' => 100, 'impressions' => 1000, 'ctr' => 0.1, 'position' => 8.5 ] ] ], 200 )
    ->push( [ 'rows' => [] ], 200 );

$fetcher = new PerformanceOverviewFetcher( app( SearchAnalyticsClient::class ) );
$data    = $fetcher->fetch( makeGscConnectedConnection(), DateRange::lastDays( 7 ) );

expect( $data->totals[ 'clicks' ] )->toBe( 100.0 );
expect( $data->hasData )->toBeTrue();
```

## Testing the HTTP endpoints

Stub the resolver and client via `->instance()` so the controller runs on synthetic data:

```php
use ArtisanPackUI\GoogleSearchConsole\Support\GoogleConnectionResolver;
use ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsClient;

app()->instance( GoogleConnectionResolver::class, new class extends GoogleConnectionResolver {
    public function forUser( ?Illuminate\Contracts\Auth\Authenticatable $user ): ?ArtisanPackUI\Google\Models\GoogleConnection
    {
        return makeGscConnectedConnection();
    }
} );

Http::fake( [ '*' => Http::response( [ 'rows' => [] ], 200 ) ] );

$this->actingAs( $user )
    ->getJson( '/google-search-console/performance?days=7' )
    ->assertOk();
```

## Testing the Livewire components

The package's `tests/Feature/LivewireComponentsTest.php` covers the reference patterns:

```php
use ArtisanPackUI\GoogleSearchConsole\Livewire\PerformanceCard;
use Livewire\Livewire;

// Stub the resolver + client bindings (see above).

Http::fakeSequence()
    ->push( [ 'rows' => [ [ 'keys' => [], 'clicks' => 137, 'impressions' => 900, 'ctr' => 0.152, 'position' => 4.1 ] ] ], 200 )
    ->push( [ 'rows' => [ [ 'keys' => [ '2026-01-01' ], 'clicks' => 137, 'impressions' => 900, 'ctr' => 0.152, 'position' => 4.1 ] ] ], 200 );

Livewire::test( PerformanceCard::class )
    ->assertSet( 'baseInstalled', true )
    ->assertSet( 'hasData', true )
    ->assertSet( 'totals.clicks', 137.0 )
    ->assertSee( 'Search performance' );
```

`Livewire::test()` mounts the component, runs `mount()` (which calls `refresh()`), and gives you a fluent-assertion object.

## Testing the CMS framework bridge

The package's `tests/Feature/CmsFrameworkBridgeTest.php` uses stub classes in `tests/Stubs/CmsFrameworkAdminWidgets/` autoloaded into the real cms-framework namespace via `autoload-dev`:

```json
"autoload-dev": {
    "psr-4": {
        "Tests\\": "tests/",
        "ArtisanPackUI\\CMSFramework\\Modules\\AdminWidgets\\": "tests/Stubs/CmsFrameworkAdminWidgets/"
    }
}
```

Copy the same pattern into your own package if you want to test bridge integrations without pulling the full cms-framework dep tree in.

Assertion pattern:

```php
use ArtisanPackUI\CMSFramework\Modules\AdminWidgets\Services\AdminWidgetManager;
use ArtisanPackUI\GoogleSearchConsole\Bridges\CmsFramework\AdminWidgets\PerformanceCardWidget;

// TestCase binds AdminWidgetManager as a singleton in defineEnvironment,
// so the boot-time registrations are visible here.
$registered = app( AdminWidgetManager::class )->getRegistered();

expect( $registered )->toBe( [
    'google-search-console.performance-card'  => PerformanceCardWidget::class,
    'google-search-console.top-queries-table' => TopQueriesTableWidget::class,
    'google-search-console.top-pages-table'   => TopPagesTableWidget::class,
] );
```

## Testing scope registration

```php
use ArtisanPackUI\Google\Scopes\ScopeRegistry;
use ArtisanPackUI\GoogleSearchConsole\Support\BaseInstalled;

BaseInstalled::setForTesting( true );

expect( app( ScopeRegistry::class )->all() )
    ->toContain( 'https://www.googleapis.com/auth/webmasters.readonly' );
```

## Testing the cache

The `SearchAnalyticsClient` caches successful responses under `google-search-console:query:*` for the configured TTL:

```php
config()->set( 'google-search-console.reporting.cache_ttl', 300 );

Http::fake( [ '*' => Http::response( [ 'rows' => [ /* ... */ ] ], 200 ) ] );

$client->query( $request, $connection );  // hits Google
$client->query( $request, $connection );  // hits the cache

Http::assertSentCount( 1 );
```

To exercise cache misses, either set `cache_ttl` to `0` or vary the request payload between calls.

## Running the package's own tests

From the package directory:

```bash
composer test          # runs Pest
composer lint          # php-cs-fixer --dry-run + phpcs
composer fix           # php-cs-fixer fix
```

From this dev app (with the package symlinked):

```bash
cd packages/google-search-console
vendor/bin/pest --compact
```

## See also

- The package's own `tests/Feature/*` — reference implementations for every pattern above.
- [Reporting/Caching](Reporting-Caching) — cache behavior in depth.
- [Troubleshooting](Troubleshooting) — common test failures decoded.
