---
title: Building Your Own
---

# Building Your Own

The shipped Livewire / React / Vue components all sit on top of the same public surfaces you can use directly:

- **The [three JSON endpoints](HTTP-Endpoints)** — call from any client-side framework via `fetch`.
- **The [SearchAnalyticsClient + fetchers](Reporting)** — call from server-side PHP, no HTTP layer required.

## From a non-Vue / non-React client

Fetch directly from JS (Svelte, Solid, Alpine, vanilla, …):

```js
const response = await fetch( '/google-search-console/performance?days=28', {
    credentials: 'same-origin',
    headers: { 'Accept': 'application/json' },
} )

if ( ! response.ok ) {
    const body = await response.json()
    // body.error = 'unauthenticated' | 'not_connected' | 'base_not_installed' | 'reporting_error'
    // body.message = human-readable message
    return
}

const data = await response.json()
// data.range = { startDate, endDate }
// data.totals = { clicks, impressions, ctr, position }
// data.trend  = [ { date, clicks, impressions, ctr, position }, ... ]
// data.hasData = boolean
```

Full payload shapes: [HTTP Endpoints](HTTP-Endpoints).

## From a Blade view (no Livewire)

For apps that don't run Livewire but still want server-rendered HTML, resolve the fetcher directly:

```php
use ArtisanPackUI\GoogleSearchConsole\Reporting\DateRange;
use ArtisanPackUI\GoogleSearchConsole\Reporting\PerformanceOverviewFetcher;
use ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsClient;
use ArtisanPackUI\GoogleSearchConsole\Support\GoogleConnectionResolver;

Route::get( '/dashboard', function () {
    $connection = app( GoogleConnectionResolver::class )->forUser( request()->user() );

    if ( null === $connection ) {
        return view( 'dashboard.disconnected' );
    }

    $fetcher = new PerformanceOverviewFetcher( app( SearchAnalyticsClient::class ) );
    $data    = $fetcher->fetch( $connection, DateRange::lastDays( 28 ) );

    return view( 'dashboard.performance', [ 'data' => $data ] );
} );
```

Full server-side API: [Reporting](Reporting).

## From a queue job (nightly digest, weekly email, etc.)

Same as above — the client and fetchers have no request lifecycle assumptions:

```php
namespace App\Jobs;

use ArtisanPackUI\GoogleSearchConsole\Reporting\DateRange;
use ArtisanPackUI\GoogleSearchConsole\Reporting\PerformanceOverviewFetcher;
use ArtisanPackUI\GoogleSearchConsole\Reporting\SearchAnalyticsClient;
use ArtisanPackUI\GoogleSearchConsole\Support\GoogleConnectionResolver;

class SendWeeklyPerformanceDigest implements ShouldQueue
{
    public function __construct( public int $userId ) {}

    public function handle(): void
    {
        $user       = User::find( $this->userId );
        $connection = app( GoogleConnectionResolver::class )->forUser( $user );

        if ( null === $connection ) {
            return;
        }

        $fetcher = new PerformanceOverviewFetcher( app( SearchAnalyticsClient::class ) );
        $data    = $fetcher->fetch( $connection, DateRange::lastDays( 7 ) );

        Mail::to( $user )->send( new WeeklyDigest( $data ) );
    }
}
```

## Sharing HTTP endpoints with a custom UI

You can also make custom clients hit the same three routes your React / Vue clients hit — this is how you'd wire a mobile app or a headless CMS. The React and Vue components inject `fetchImpl` and `baseUrl`, so they can be pointed at proxied endpoints too:

```tsx
<PerformanceCard
    baseUrl="/api/tenants/acme/gsc"
    fetchImpl={authenticatedFetch}
/>
```

Where `authenticatedFetch` is your app's fetch wrapper (session cookies, bearer tokens, whatever). The controllers on your side would forward to `googleSearchConsole()` internally.

## What the shipped components deliberately do not do

They never:

- **Show one user's data to another.** The controllers pull the connection off `request()->user()` and never accept a `?site_url` query string override — see [Reporting](Reporting#multi-tenant-apps) for the multi-tenant pattern.
- **Persist Search Console data.** Every request round-trips to Google unless a cached copy is still warm.
- **Render an "authorize with Google" button.** That's the base package's [connection UI](https://github.com/ArtisanPack-UI/google/blob/main/docs/connection-ui.md). If the user has no connection, the components render a call to action that links there.
- **Break the CMS bridge's expectations.** The [widget wrappers](CMS-Framework-Bridge) extend these Livewire components without overriding markup, so anything you do to the components (publishing views, restyling) flows through to the widget wrappers automatically.

## Falling back to Google directly

If you need something outside the wrapped surface (URL Inspection API, Sitemaps API, indexing API, sites.list), you don't need this package at all — go straight through the base package's `TokenManager`:

```php
use ArtisanPackUI\Google\Facades\Google;
use Illuminate\Support\Facades\Http;

$token = Google::tokens()->getValidAccessToken( $connection );

$response = Http::withToken( $token )
    ->acceptJson()
    ->get( 'https://searchconsole.googleapis.com/webmasters/v3/sites' );
```

The same pattern powers the `/gsc-test/sites-list` [debug helper](https://github.com/ArtisanPack-UI/google-search-console/blob/main/docs/installation/property-verification.md#debug-route-in-a-dev-app).
