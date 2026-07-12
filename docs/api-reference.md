---
title: API Reference
---

# API Reference

The public surface of `artisanpack-ui/google-search-console`.

Sub-pages by class or group:

- [`GoogleSearchConsole` — the facade / helper root](API-Reference-Google-Search-Console)
- [`SearchAnalyticsClient`](API-Reference-Search-Analytics-Client)
- [`SearchAnalyticsRequest`](API-Reference-Search-Analytics-Request)
- [`SearchAnalyticsResponse`](API-Reference-Search-Analytics-Response)
- [`DateRange`](API-Reference-Date-Range)
- [Fetchers](API-Reference-Fetchers) — the three high-level report fetchers.
- [Data objects](API-Reference-Data-Objects) — the DTOs the fetchers return.
- [Livewire components](API-Reference-Livewire-Components)
- [Widget wrappers](API-Reference-Widget-Wrappers)
- [Controllers](API-Reference-Controllers)
- [Support](API-Reference-Support) — `BaseInstalled`, `CmsFrameworkInstalled`, `GoogleConnectionResolver`.
- [Exceptions](API-Reference-Exceptions)

## The facade

```php
use ArtisanPackUI\GoogleSearchConsole\Facades\GoogleSearchConsole;

GoogleSearchConsole::client();        // SearchAnalyticsClient|null
GoogleSearchConsole::hasReporting();  // bool
```

The facade proxies to a singleton `ArtisanPackUI\GoogleSearchConsole\GoogleSearchConsole` instance. `client()` returns null when `BaseInstalled::check() === false`; `hasReporting()` is a convenient shorthand.

## The helper

```php
googleSearchConsole();  // same as app( 'google-search-console' )
```

Returns the same singleton the facade points at. Pick whichever style matches your codebase.

## Container bindings

The service provider registers:

| Abstract | Concrete | Scope |
|---|---|---|
| `'google-search-console'` (and `GoogleSearchConsole::class`) | `ArtisanPackUI\GoogleSearchConsole\GoogleSearchConsole` | Singleton |
| `SearchAnalyticsClient::class` | The client, built from config + Laravel HTTP client + TokenManager + cache | Singleton |
| `GoogleConnectionResolver::class` | Resolves a `GoogleConnection` for an authenticated user | Singleton |

Rebind any of them (e.g., in a tenant middleware) to swap behavior per request — see [Reporting](Reporting#multi-tenant-apps).

## Namespace map

```
ArtisanPackUI\GoogleSearchConsole\
├── GoogleSearchConsole.php                — the aggregator
├── GoogleSearchConsoleServiceProvider.php
├── helpers.php                            — googleSearchConsole() function
├── Facades\
│   └── GoogleSearchConsole.php
├── Reporting\
│   ├── SearchAnalyticsClient.php
│   ├── SearchAnalyticsRequest.php
│   ├── SearchAnalyticsResponse.php
│   ├── DateRange.php
│   ├── PerformanceOverviewFetcher.php
│   ├── PerformanceOverviewData.php
│   ├── TopQueriesFetcher.php
│   ├── TopQueriesData.php
│   ├── TopPagesFetcher.php
│   └── TopPagesData.php
├── Livewire\
│   ├── PerformanceCard.php                — <livewire:google-search-console::performance-card>
│   ├── TopQueriesTable.php                — <livewire:google-search-console::top-queries-table>
│   └── TopPagesTable.php                  — <livewire:google-search-console::top-pages-table>
├── Http\Controllers\
│   ├── PerformanceOverviewController.php  — GET /google-search-console/performance
│   ├── TopQueriesController.php           — GET /google-search-console/top-queries
│   └── TopPagesController.php             — GET /google-search-console/top-pages
├── Bridges\CmsFramework\AdminWidgets\
│   ├── PerformanceCardWidget.php
│   ├── TopQueriesTableWidget.php
│   └── TopPagesTableWidget.php
├── Support\
│   ├── BaseInstalled.php
│   ├── CmsFrameworkInstalled.php
│   └── GoogleConnectionResolver.php
└── Exceptions\
    ├── BaseNotInstalledException.php
    └── ReportingException.php
```

## Public routes

Reference: [HTTP Endpoints](HTTP-Endpoints).

| Route name | Method | URI |
|---|---|---|
| `google-search-console.performance` | GET | `/google-search-console/performance` |
| `google-search-console.top-queries` | GET | `/google-search-console/top-queries` |
| `google-search-console.top-pages` | GET | `/google-search-console/top-pages` |

## Public views

- `google-search-console::livewire.performance-card`
- `google-search-console::livewire.top-queries-table`
- `google-search-console::livewire.top-pages-table`

Publish with `--tag=google-search-console-views`.

## Publish tags

| Tag | What it publishes |
|---|---|
| `google-search-console-config` | `config/google-search-console.php` |
| `google-search-console-views` | Three Blade views → `resources/views/vendor/google-search-console/` |
| `google-search-console-js` | React + Vue + shared JS → `resources/js/vendor/google-search-console/` |

## Filter hooks used

| Hook | Contract | Purpose |
|---|---|---|
| `ap.google.scopes` | Filter — receives and returns `array<int, string>` | Contribute `webmasters.readonly` to the base package's [scope registry](Scopes). Registered inside `GoogleSearchConsoleServiceProvider::registerGoogleScopeHook()`. |

## CMS framework registrations

When [the bridge is active](CMS-Framework-Bridge):

| Widget type | Class | Capability |
|---|---|---|
| `google-search-console.performance-card` | `PerformanceCardWidget` | `view_google_search_console` |
| `google-search-console.top-queries-table` | `TopQueriesTableWidget` | `view_google_search_console` |
| `google-search-console.top-pages-table` | `TopPagesTableWidget` | `view_google_search_console` |

Discoverable programmatically via:

```php
GoogleSearchConsoleServiceProvider::cmsFrameworkWidgetTypeMap();
```

---
Continue to [Testing](Testing) →
