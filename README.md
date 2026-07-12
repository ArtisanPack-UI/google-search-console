# ArtisanPack UI Google Search Console

Search Console performance data and drop-in UI components (Livewire, React, Vue) for the ArtisanPack UI ecosystem. Uses the shared [`artisanpack-ui/google`](https://github.com/ArtisanPack-UI/google) base package for OAuth2 authentication, token storage/refresh, and single-consent scope management, and calls the Search Console API to surface performance and coverage insights.

## Requirements

- PHP **8.2+**
- Laravel **10, 11, 12, or 13**
- [`artisanpack-ui/google`](https://github.com/ArtisanPack-UI/google) **^1.0** — installed and configured with an OAuth client, with the current user's Google account already connected
- A **verified Search Console property** (URL property or Domain property) accessible to the connected Google account
- **Livewire ^3.6** *(optional)* — required only if you use the Blade / Livewire components or the CMS-framework AdminWidget bridge

## Installation

Install the package via Composer:

```bash
composer require artisanpack-ui/google-search-console
```

The service provider and `GoogleSearchConsole` facade are auto-discovered by Laravel. The base [`artisanpack-ui/google`](https://github.com/ArtisanPack-UI/google) package is pulled in automatically.

Publish the config file if you want to override defaults:

```bash
php artisan vendor:publish --tag=google-search-console-config
```

## Prerequisites

The Search Console API returns performance data for a **verified property** owned by (or delegated to) the connected Google account. Before the components will render data:

1. **Connect a Google account.** Install and configure `artisanpack-ui/google`, run its OAuth flow, and confirm the user has a `google_connections` row with `status = connected`.
2. **Verify the property in Search Console.** In [Google Search Console](https://search.google.com/search-console) add the site as either a **URL prefix property** (e.g. `https://example.com/`) or a **Domain property** (e.g. `sc-domain:example.com`) and complete verification (DNS TXT, HTML file, Google Analytics, etc.).
3. **Set `GSC_SITE_URL`.** Point the package at the exact property string Search Console uses:
   ```dotenv
   # URL property — include the trailing slash
   GSC_SITE_URL="https://example.com/"

   # Domain property — use the sc-domain: prefix
   GSC_SITE_URL="sc-domain:example.com"
   ```
   > **Multi-tenant / agency apps** should not use a global env var. Override the `SearchAnalyticsClient` binding (or pass a per-request `siteUrl` on the server side) to scope by tenant. The HTTP controllers intentionally **ignore** any `?site_url=…` query string to prevent one authenticated user from querying another tenant's property.

## OAuth Scope

The package contributes `https://www.googleapis.com/auth/webmasters.readonly` to the base package's shared `ScopeRegistry` via the `ap.google.scopes` filter hook. That means the base package's single OAuth consent screen automatically includes Search Console alongside every other Google service the app uses — no separate consent step, no manual scope wiring.

The scope is registered whenever the base package is installed. Applications that already granted consent before adding this package will see the base package prompt for incremental consent on the next connect.

## Usage

### Blade / Livewire

Drop the Livewire components into any Blade view. They handle their own data fetching, empty states, error states, and the "base package missing" call to action.

```blade
{{-- Full performance card: clicks, impressions, CTR, position, trend chart --}}
<livewire:google-search-console::performance-card :days="28" />

{{-- Top queries table --}}
<livewire:google-search-console::top-queries-table :days="28" :limit="50" />

{{-- Top pages table --}}
<livewire:google-search-console::top-pages-table :days="28" :limit="50" />
```

All three components accept the same three parameters:

| Parameter | Type | Default | Notes |
|-----------|------|---------|-------|
| `days` | `int` | `28` | Clamped to the Search Console maximum (`DateRange::MAX_DAYS`). |
| `limit` *(tables only)* | `int` | `50` | Clamped to `1000`. |
| `siteUrl` | `?string` | `null` | Overrides the configured site for this instance. |

### React

The React components live under the `react/` entrypoint. They call the package's HTTP routes and render the same performance card / tables as the Livewire components.

```tsx
import { PerformanceCard } from '@artisanpack-ui/google-search-console/react/PerformanceCard'
import { TopQueriesTable } from '@artisanpack-ui/google-search-console/react/TopQueriesTable'
import { TopPagesTable } from '@artisanpack-ui/google-search-console/react/TopPagesTable'

export function Dashboard() {
    return (
        <>
            <PerformanceCard initialDays={28} />
            <TopQueriesTable initialDays={28} initialLimit={50} />
            <TopPagesTable initialDays={28} initialLimit={50} />
        </>
    )
}
```

Props (all optional):

| Prop | Type | Default | Notes |
|------|------|---------|-------|
| `initialDays` | `number` | `28` | Initial range in days. |
| `initialLimit` *(tables)* | `number` | `50` | Initial row limit. |
| `siteUrl` | `string \| null` | `null` | Override the configured site. |
| `baseUrl` | `string` | `/google-search-console` | Override the HTTP endpoint prefix. |
| `fetchImpl` | `typeof fetch` | `window.fetch` | Injectable fetch, useful for SSR / testing. |

### Vue

The Vue components mirror the React API and live under the `vue/` entrypoint:

```vue
<script setup lang="ts">
import PerformanceCard from '@artisanpack-ui/google-search-console/vue/PerformanceCard.vue'
import TopQueriesTable from '@artisanpack-ui/google-search-console/vue/TopQueriesTable.vue'
import TopPagesTable from '@artisanpack-ui/google-search-console/vue/TopPagesTable.vue'
</script>

<template>
    <PerformanceCard :initial-days="28" />
    <TopQueriesTable :initial-days="28" :initial-limit="50" />
    <TopPagesTable :initial-days="28" :initial-limit="50" />
</template>
```

The React and Vue components share a single fetch layer (`resources/js/shared/`) so both frameworks talk to the same server payload.

### Publishing views and JS assets

Publish the Blade views if you need to customise the markup:

```bash
php artisan vendor:publish --tag=google-search-console-views
```

Publish the React / Vue sources to bring them under your build pipeline:

```bash
php artisan vendor:publish --tag=google-search-console-js
```

### HTTP endpoints (used by the React + Vue components)

Enabled by default, mounted under the `google-search-console` prefix with `web` + `auth` middleware:

| Endpoint | Route name |
|----------|------------|
| `GET /google-search-console/performance?days=28` | `google-search-console.performance` |
| `GET /google-search-console/top-queries?days=28&limit=50` | `google-search-console.top-queries` |
| `GET /google-search-console/top-pages?days=28&limit=50` | `google-search-console.top-pages` |

Disable them for headless / API-only apps in `config/google-search-console.php`:

```php
'routes' => [
    'enabled' => false,
],
```

### Programmatic access

```php
use ArtisanPackUI\GoogleSearchConsole\Facades\GoogleSearchConsole;
use ArtisanPackUI\GoogleSearchConsole\Reporting\DateRange;
use ArtisanPackUI\GoogleSearchConsole\Reporting\PerformanceOverviewFetcher;

if ( googleSearchConsole()->hasReporting() ) {
    $fetcher = new PerformanceOverviewFetcher( googleSearchConsole()->client() );
    $data    = $fetcher->fetch( $connection, DateRange::lastDays( 28 ) );

    // $data->totals, $data->trend, $data->hasData
}
```

## CMS Framework Bridge (Optional)

When [`artisanpack-ui/cms-framework`](https://github.com/ArtisanPack-UI/cms-framework) is installed, the package automatically registers three dashboard widgets with the CMS framework's `AdminWidgetManager`:

| Widget type | Class |
|-------------|-------|
| `google-search-console.performance-card` | `Bridges\CmsFramework\AdminWidgets\PerformanceCardWidget` |
| `google-search-console.top-queries-table` | `Bridges\CmsFramework\AdminWidgets\TopQueriesTableWidget` |
| `google-search-console.top-pages-table` | `Bridges\CmsFramework\AdminWidgets\TopPagesTableWidget` |

Each widget wrapper extends the corresponding Livewire component and implements the CMS framework's `AdminWidgetInterface`, so the dashboard can drop them onto any admin page with no extra wiring.

The bridge is a strict **opt-in** — the wrappers are only referenced when both the CMS framework and Livewire are on the autoloader. Without the CMS framework the package still boots and behaves exactly as before.

Each widget declares the `view_google_search_console` capability. Grant that permission to any role that should see Search Console data on the dashboard.

## Configuration

Published to `config/google-search-console.php`:

| Key | Env | Default | Notes |
|-----|-----|---------|-------|
| `reporting.site_url` | `GSC_SITE_URL` | *(required)* | See [Prerequisites](#prerequisites). |
| `reporting.api_base` | — | `https://searchconsole.googleapis.com/webmasters/v3` | Change only if Google moves the endpoint. |
| `reporting.timeout` | — | `30` | Seconds. Falls back to `30` if set to `0` or `null` so a stuck endpoint cannot hang the worker. |
| `reporting.cache_ttl` | — | `300` | Seconds. Set to `0` to disable response caching. |
| `scopes` | — | `[webmasters.readonly]` | Contributed to the shared `ScopeRegistry` via `ap.google.scopes`. |
| `routes.enabled` | — | `true` | Toggle the React/Vue HTTP routes. |
| `routes.prefix` | — | `google-search-console` | Prefix for the HTTP routes. |
| `routes.middleware` | — | `[web, auth]` | Middleware stack applied to the routes. |

## Testing

```bash
composer install
composer test
```

## Contributing

Please [read through the contributing guidelines](CONTRIBUTING.md) to learn more about how you can contribute to this project.
