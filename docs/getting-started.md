---
title: Getting Started
---

# Getting Started

The shortest path from `composer require` to a rendered Search Console performance card on a Blade page. Every step links to a more detailed page for the full walkthrough.

See also: [Installation](Installation), [Components](Components), [Reporting](Reporting), [Scopes](Scopes).

## Prerequisites

- PHP 8.2+ and Laravel 10.x, 11.x, 12.x, or 13.x.
- [`artisanpack-ui/google`](https://github.com/ArtisanPack-UI/google) **installed and configured**, with an OAuth client, running migrations, and the current user's Google account **connected** (see the base package's [Getting Started](https://github.com/ArtisanPack-UI/google/blob/main/docs/getting-started.md)).
- A **verified Search Console property** the connected Google account can query — either a URL property (`https://example.com/`) or a Domain property (`sc-domain:example.com`). Set that up in [Google Search Console](https://search.google.com/search-console) before continuing. See [property verification](Installation-Property-Verification).

Optional peer packages:

| Package | Version | What it enables |
|---|---|---|
| [`livewire/livewire`](https://livewire.laravel.com/) | `^3.6` | The three `<livewire:google-search-console::*>` components and the [CMS Framework Bridge](CMS-Framework-Bridge). |
| [`artisanpack-ui/cms-framework`](https://github.com/ArtisanPack-UI/cms-framework) | any | The [CMS Framework Bridge](CMS-Framework-Bridge) — the three components register as admin dashboard widgets. |

Neither is required. The React and Vue components ship without Livewire.

## 1. Install

```bash
composer require artisanpack-ui/google-search-console
```

The service provider and `GoogleSearchConsole` facade are auto-discovered. The base [`artisanpack-ui/google`](https://github.com/ArtisanPack-UI/google) package is pulled in transitively — if you haven't installed it yet, do so first (see the base package docs).

## 2. Point the package at your verified Search Console property

Add the property to `.env`:

```env
# URL property — include the trailing slash
GSC_SITE_URL="https://example.com/"

# Domain property — use the sc-domain: prefix
GSC_SITE_URL="sc-domain:example.com"
```

The exact string must match one of the properties [Google Search Console](https://search.google.com/search-console) lists for the connected account. See [property verification](Installation-Property-Verification) for the debugging routine.

## 3. Confirm the OAuth scope registered

The package hooks `webmasters.readonly` into the base package's [scope registry](Scopes) automatically. To confirm:

```bash
php artisan tinker
>>> app( ArtisanPackUI\Google\Scopes\ScopeRegistry::class )->all()
```

`https://www.googleapis.com/auth/webmasters.readonly` should appear in the array. If a user connected before this package installed, they'll need to reauthorize — the base package's [connection UI](https://github.com/ArtisanPack-UI/google/blob/main/docs/connection-ui.md) surfaces this automatically.

## 4. Drop a Livewire component into a Blade view

```blade
<livewire:google-search-console::performance-card :days="28" />
<livewire:google-search-console::top-queries-table :days="28" :limit="50" />
<livewire:google-search-console::top-pages-table   :days="28" :limit="50" />
```

Full component reference: [Components/Livewire](Components-Livewire).

For React and Vue see [Components/React](Components-React) and [Components/Vue](Components-Vue) — both pull from the [HTTP endpoints](HTTP-Endpoints) this package mounts under `/google-search-console/*`.

## 5. Or call the API directly

```php
use ArtisanPackUI\GoogleSearchConsole\Facades\GoogleSearchConsole;
use ArtisanPackUI\GoogleSearchConsole\Reporting\DateRange;
use ArtisanPackUI\GoogleSearchConsole\Reporting\PerformanceOverviewFetcher;

if ( googleSearchConsole()->hasReporting() ) {
    $client  = googleSearchConsole()->client();
    $fetcher = new PerformanceOverviewFetcher( $client );
    $data    = $fetcher->fetch( $connection, DateRange::lastDays( 28 ) );

    // $data->totals, $data->trend, $data->hasData
}
```

Full walkthrough: [Reporting](Reporting).

## 6. Verify

```bash
php artisan route:list --path=google-search-console
```

You should see:

```
GET  google-search-console/performance    google-search-console.performance
GET  google-search-console/top-queries    google-search-console.top-queries
GET  google-search-console/top-pages      google-search-console.top-pages
```

Hitting `/google-search-console/performance?days=28` as an authenticated user with a connected Google account returns the same JSON payload the React and Vue components consume — a quick way to prove the round-trip end to end.

## Next steps

- [Installation](Installation) — full install walkthrough including property verification and configuration.
- [Components](Components) — Livewire, React, Vue components.
- [HTTP Endpoints](HTTP-Endpoints) — the three JSON endpoints and their payload shape.
- [Reporting](Reporting) — the server-side API for calling Search Console directly.
- [Scopes](Scopes) — how the scope contribution works and how to test it.
- [CMS Framework Bridge](CMS-Framework-Bridge) — optional AdminWidget bridge for `artisanpack-ui/cms-framework`.
- [API Reference](API-Reference) — the full public surface.

---
Continue to [Installation](Installation) →
