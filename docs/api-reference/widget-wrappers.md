---
title: Widget Wrappers
---

# Widget Wrappers

Class-level reference for the optional [CMS framework bridge](CMS-Framework-Bridge).

All three live under `ArtisanPackUI\GoogleSearchConsole\Bridges\CmsFramework\AdminWidgets\*`. Each extends its corresponding Livewire component from `ArtisanPackUI\GoogleSearchConsole\Livewire\*` and implements `ArtisanPackUI\CMSFramework\Modules\AdminWidgets\Contracts\AdminWidgetInterface`.

## `PerformanceCardWidget`

```php
class PerformanceCardWidget extends PerformanceCard implements AdminWidgetInterface
{
    public static function getWidgetInfo(): array
    {
        return [
            'title'           => __( 'Search performance' ),
            'description'     => __( 'Search Console clicks, impressions, CTR, and position for a rolling range.' ),
            'capability'      => 'view_google_search_console',
            'default_options' => [
                'days'    => 28,
                'siteUrl' => null,
            ],
        ];
    }
}
```

- **FQCN**: `ArtisanPackUI\GoogleSearchConsole\Bridges\CmsFramework\AdminWidgets\PerformanceCardWidget`
- **Extends**: `PerformanceCard`
- **Widget type**: `google-search-console.performance-card`
- **Livewire alias**: `google-search-console::performance-card-widget`

## `TopQueriesTableWidget`

```php
class TopQueriesTableWidget extends TopQueriesTable implements AdminWidgetInterface
{
    public static function getWidgetInfo(): array
    {
        return [
            'title'           => __( 'Top queries' ),
            'description'     => __( 'Highest-performing search queries from Search Console for a rolling range.' ),
            'capability'      => 'view_google_search_console',
            'default_options' => [
                'days'    => 28,
                'limit'   => TopQueriesFetcher::DEFAULT_LIMIT,
                'siteUrl' => null,
            ],
        ];
    }
}
```

- **FQCN**: `ArtisanPackUI\GoogleSearchConsole\Bridges\CmsFramework\AdminWidgets\TopQueriesTableWidget`
- **Extends**: `TopQueriesTable`
- **Widget type**: `google-search-console.top-queries-table`
- **Livewire alias**: `google-search-console::top-queries-table-widget`

## `TopPagesTableWidget`

```php
class TopPagesTableWidget extends TopPagesTable implements AdminWidgetInterface
{
    public static function getWidgetInfo(): array
    {
        return [
            'title'           => __( 'Top pages' ),
            'description'     => __( 'Highest-performing pages in Search Console for a rolling range.' ),
            'capability'      => 'view_google_search_console',
            'default_options' => [
                'days'    => 28,
                'limit'   => TopPagesFetcher::DEFAULT_LIMIT,
                'siteUrl' => null,
            ],
        ];
    }
}
```

- **FQCN**: `ArtisanPackUI\GoogleSearchConsole\Bridges\CmsFramework\AdminWidgets\TopPagesTableWidget`
- **Extends**: `TopPagesTable`
- **Widget type**: `google-search-console.top-pages-table`
- **Livewire alias**: `google-search-console::top-pages-table-widget`

## Discovery

The type map is exposed via a static method on the service provider:

```php
GoogleSearchConsoleServiceProvider::cmsFrameworkWidgetTypeMap();
```

Returns:

```php
[
    'google-search-console.performance-card'  => PerformanceCardWidget::class,
    'google-search-console.top-queries-table' => TopQueriesTableWidget::class,
    'google-search-console.top-pages-table'   => TopPagesTableWidget::class,
]
```

## See also

- [CMS Framework Bridge](CMS-Framework-Bridge) — bridge overview.
- [CMS Framework Bridge/Widgets](CMS-Framework-Bridge-Widgets) — per-widget details.
- [API Reference/Livewire Components](API-Reference-Livewire-Components) — the classes each widget extends.
