---
title: FAQ
---

# FAQ

## Setup

### Do I have to install `artisanpack-ui/google` separately?

No — Composer pulls it in as a hard dependency when you `composer require artisanpack-ui/google-search-console`. But you still have to **configure** it (Google Cloud OAuth client, credentials, migrations, connected user). The reporting side of this package refuses to boot without a working base install.

### Do I need Livewire?

Only if you want the Livewire components or the CMS framework bridge. The React and Vue components ship as source; the `SearchAnalyticsClient` and fetchers work server-side without a UI layer at all.

### Do I need `artisanpack-ui/cms-framework`?

Only for the [admin widget bridge](CMS-Framework-Bridge). Without it, the package boots fine and the three components work standalone.

### Can this package call Google APIs other than Search Console?

No. The `SearchAnalyticsClient` wraps `searchAnalytics.query` only. For URL Inspection, Sitemaps, or the Indexing API, go straight through the base package's `TokenManager` — see [Components/Custom](Components-Custom#falling-back-to-google-directly).

## Property setup

### `GSC_SITE_URL` is set but the endpoint returns 404. What now?

The string doesn't match a verified property this Google account can query. Compare against `sites.list`:

```bash
curl 'https://searchconsole.googleapis.com/webmasters/v3/sites' \
    -H "Authorization: Bearer <token>"
```

Or use the debug helper — [Installation/Property Verification](Installation-Property-Verification#debug-route-in-a-dev-app).

### Can I query different properties for different users?

Yes. Rebind `SearchAnalyticsClient` per tenant / per user in a middleware — see [Reporting](Reporting#multi-tenant-apps).

**Do not** try to accept a `?site_url` query string on the endpoints. The controllers deliberately ignore it because doing otherwise would let any authenticated user query any property the shared account owns.

### Do URL properties and Domain properties return different data?

Yes. A URL property covers a specific `scheme + host` combination; a Domain property covers every subdomain (via DNS verification) and both `http` and `https`. Two URL properties (`https://example.com/` and `https://www.example.com/`) are separate — the Domain property `sc-domain:example.com` covers both.

Choose based on your app's actual site topology.

## Scopes

### The scope registered but users don't get prompted for it — why?

Users who connected the base package **before** this one was installed still hold the original grant. `ScopeRegistry::hasAllRequired()` now returns `false` for them; the base package's connection UI surfaces this as `needsReauthorize = true`, linking to `/google/auth/reauthorize`. The delta (`webmasters.readonly`) is added incrementally.

### Can I customize which scope this package contributes?

Yes — override `google-search-console.scopes` in `config/google-search-console.php`. The filter callback reads the array on every dispatch. Rare use case; the read-only scope is the whole point.

If you need to add a write scope alongside the read-only one, use a separate filter hook — see [Scopes](Scopes#what-if-i-need-a-write-scope?).

## Reporting

### Why does the performance card show zero data on a new property?

Search Console needs 48–72 hours to start populating data. Once data exists, the trailing 2–3 days of any range are typically still being finalised and may show low or zero values. The `PerformanceOverviewFetcher` distinguishes "no rows at all" (renders empty state) from "all-zero totals" (renders zeros).

### Google returned `429` — how do I back off?

The package doesn't retry on your behalf. Wrap fetcher calls in a queued job with `$tries` + `$backoff`:

```php
class SyncSearchConsoleData implements ShouldQueue
{
    public int $tries    = 5;
    public array $backoff = [ 60, 120, 300, 600 ];  // seconds

    public function handle(): void
    {
        // ...
    }
}
```

Search Console's per-user + per-property quota is generous enough that a single dashboard shouldn't hit it. Bulk syncs across all users will.

### Can I paginate past 25,000 rows?

Yes — pass `startRow` on subsequent `SearchAnalyticsRequest` builds. The shipped fetchers don't do this; write a custom loop:

```php
$allRows = [];
$offset  = 0;

do {
    $response = $client->query( new SearchAnalyticsRequest(
        dateRange:  DateRange::lastDays( 90 ),
        dimensions: [ 'query' ],
        rowLimit:   SearchAnalyticsRequest::MAX_ROW_LIMIT,
        startRow:   $offset,
    ), $connection );

    $rows = $response->rows();
    $allRows = [ ...$allRows, ...$rows ];
    $offset += SearchAnalyticsRequest::MAX_ROW_LIMIT;
} while ( SearchAnalyticsRequest::MAX_ROW_LIMIT === count( $rows ) );
```

## Runtime

### Why does `hasReporting()` return `false` even though I `composer require`'d everything?

Two reasons:

1. `BaseInstalled::check()` returned false — usually because the base package isn't autoloadable (missing service provider, `composer dump-autoload` needed, etc.).
2. The static cache is stale — happens in long-running processes (Octane, Horizon) after a hot-reload. `BaseInstalled::reset()` clears it.

### Why does the Livewire component render "Install the base package" even after I installed it?

The `$baseInstalled` public property was set to `false` at mount time, before the base package was on the autoloader. Reload the page — `mount()` will re-run `BaseInstalled::check()`.

If you're in a long-running worker, `BaseInstalled::reset()` between requests forces a fresh check.

### Does the Livewire component poll?

Yes — `wire:poll.60s="refresh"` on the root element. Every 60 seconds the browser hits the server and the component re-fetches. Override by publishing the views and editing the `wire:poll` attribute.

### Can multiple performance cards on the same page share a fetch?

Each Livewire component instance fetches independently. If you want a shared fetch (e.g., one totals for the header, one trend for the sidebar), resolve the fetcher yourself in a parent controller and pass the DTOs down as props to your own Blade partials. Or build a shared parent Livewire component that owns the DTO and delegates rendering.

## CMS framework bridge

### Do I have to have `artisanpack-ui/cms-framework` installed?

No. The bridge short-circuits when the framework isn't present. This package boots fine without it.

### Can I disable the bridge even when the framework is installed?

Not via config. Uninstall the framework, or filter the widgets out per-user via the framework's capability system — revoke `view_google_search_console` from every role.

### How do I customize widget titles or defaults?

Subclass the wrapper and override `getWidgetInfo()`:

```php
namespace App\Widgets;

use ArtisanPackUI\GoogleSearchConsole\Bridges\CmsFramework\AdminWidgets\PerformanceCardWidget as BaseWidget;

class BrandedPerformanceCardWidget extends BaseWidget
{
    public static function getWidgetInfo(): array
    {
        return [
            ...parent::getWidgetInfo(),
            'title'           => __( 'Acme SEO — Search performance' ),
            'default_options' => [ 'days' => 90 ],
        ];
    }
}
```

Register with the manager instead of the shipped widget:

```php
$manager->register( 'acme.performance-card', BrandedPerformanceCardWidget::class );
```

## Testing

### The Livewire component test fails with "TokenManager binding not found"

Ensure the base `artisanpack-ui/google` service provider is in `getPackageProviders()`. The `SearchAnalyticsClient` singleton needs `TokenManager` to instantiate.

### The bridge tests never register widgets — the manager stays empty

`AdminWidgetManager` isn't bound as a singleton by default. Bind it in `defineEnvironment()` (or `getEnvironmentSetUp()`) so the same instance is used at boot and in the test body:

```php
$app->singleton( AdminWidgetManager::class );
```

The package's own `TestCase` does this — see the reference in [Testing](Testing#test-bootstrap).
