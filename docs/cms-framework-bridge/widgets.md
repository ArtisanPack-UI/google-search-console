---
title: The Widget Wrappers
---

# The Widget Wrappers

Three widget classes under `src/Bridges/CmsFramework/AdminWidgets/`. Each extends its corresponding Livewire component **and** implements `ArtisanPackUI\CMSFramework\Modules\AdminWidgets\Contracts\AdminWidgetInterface`.

## `PerformanceCardWidget`

FQCN: `ArtisanPackUI\GoogleSearchConsole\Bridges\CmsFramework\AdminWidgets\PerformanceCardWidget`

Extends [`PerformanceCard`](../components/livewire.md#performance-card).

### `getWidgetInfo(): array`

```php
[
    'title'           => __( 'Search performance' ),
    'description'     => __( 'Search Console clicks, impressions, CTR, and position for a rolling range.' ),
    'capability'      => 'view_google_search_console',
    'default_options' => [
        'days'    => 28,
        'siteUrl' => null,
    ],
]
```

Widget type: `google-search-console.performance-card`.

### Default options

`AdminWidgetManager::createWidget()` seeds `default_options` into a newly-created widget entry. When the CMS renders the widget it mounts the Livewire component with those options — so the performance card starts at 28 days with no site URL override by default.

Override per instance from the CMS UI (if the framework exposes an options editor) or by changing `default_options` in a subclass / your own wrapper.

## `TopQueriesTableWidget`

FQCN: `ArtisanPackUI\GoogleSearchConsole\Bridges\CmsFramework\AdminWidgets\TopQueriesTableWidget`

Extends [`TopQueriesTable`](../components/livewire.md#top-queries-table).

### `getWidgetInfo(): array`

```php
[
    'title'           => __( 'Top queries' ),
    'description'     => __( 'Highest-performing search queries from Search Console for a rolling range.' ),
    'capability'      => 'view_google_search_console',
    'default_options' => [
        'days'    => 28,
        'limit'   => TopQueriesFetcher::DEFAULT_LIMIT,  // 50
        'siteUrl' => null,
    ],
]
```

Widget type: `google-search-console.top-queries-table`.

## `TopPagesTableWidget`

FQCN: `ArtisanPackUI\GoogleSearchConsole\Bridges\CmsFramework\AdminWidgets\TopPagesTableWidget`

Extends [`TopPagesTable`](../components/livewire.md#top-pages-table).

### `getWidgetInfo(): array`

```php
[
    'title'           => __( 'Top pages' ),
    'description'     => __( 'Highest-performing pages in Search Console for a rolling range.' ),
    'capability'      => 'view_google_search_console',
    'default_options' => [
        'days'    => 28,
        'limit'   => TopPagesFetcher::DEFAULT_LIMIT,  // 50
        'siteUrl' => null,
    ],
]
```

Widget type: `google-search-console.top-pages-table`.

## Why extend the Livewire component?

Two alternatives were considered:

1. **Composition** — the widget holds a Livewire component and delegates render. Rejected because AdminWidgetManager renders widgets by class name; delegation would need a per-widget render override or a wrapper Blade view.
2. **Interface-only marker classes** — pure metadata containers that the CMS renders by looking up a separate view. Rejected because it would fork markup between the standalone Livewire component and the CMS widget — publishing views for one wouldn't help the other.

**Extension** — the wrapper IS a Livewire component (inherits from `PerformanceCard`), so it renders through the same `render()` and view. Publishing the base view or restyling the base component flows through unchanged. The extra metadata (`getWidgetInfo()`) satisfies the CMS's interface.

Trade-off: the bridge is fundamentally coupled to Livewire being present. If Livewire is missing, the wrappers are never referenced (guarded in the service provider), so this coupling only bites when Livewire *and* the CMS framework are both installed.

## Widget type map

The three type strings are exposed via a public static method for testability:

```php
GoogleSearchConsoleServiceProvider::cmsFrameworkWidgetTypeMap();
// [
//   'google-search-console.performance-card'  => PerformanceCardWidget::class,
//   'google-search-console.top-queries-table' => TopQueriesTableWidget::class,
//   'google-search-console.top-pages-table'   => TopPagesTableWidget::class,
// ]
```

Consuming code (a "reset admin dashboards" migration, a healthcheck, an audit) can iterate this without booting the SP.

## The Livewire aliases

Alongside the AdminWidgetManager registrations, the SP registers three `-widget` Livewire aliases:

- `google-search-console::performance-card-widget` → `PerformanceCardWidget`
- `google-search-console::top-queries-table-widget` → `TopQueriesTableWidget`
- `google-search-console::top-pages-table-widget` → `TopPagesTableWidget`

Distinct from the standalone base aliases (`google-search-console::performance-card`, etc.) so that a Blade view can pick either the base component or its widget wrapper by name. The CMS itself renders widgets by class name, so it doesn't use these — they're for hand-written Blade or debugging.

## See also

- [[CMS Framework Bridge]] — bridge overview.
- [[CMS Framework Bridge/Integration]] — installing and using with the CMS.
- [[CMS Framework Bridge/Capabilities]] — the `view_google_search_console` permission.
- [[API Reference/Widget Wrappers]] — full class signatures.
