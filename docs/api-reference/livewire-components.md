---
title: Livewire Components
---

# Livewire Components

Class-level reference. Usage guide: [[Components/Livewire]].

All three components live under `ArtisanPackUI\GoogleSearchConsole\Livewire\*` and extend `Livewire\Component`.

## `PerformanceCard`

FQCN: `ArtisanPackUI\GoogleSearchConsole\Livewire\PerformanceCard`. Alias: `google-search-console::performance-card`.

### Public properties

| Property | Type | Default |
|---|---|---|
| `days` | `int` | `28` |
| `siteUrl` | `?string` | `null` |
| `totals` | `?array{clicks: float, impressions: float, ctr: float, position: float}` | `null` |
| `trend` | `list<array{date: string, clicks: float, impressions: float, ctr: float, position: float}>` | `[]` |
| `errorMessage` | `?string` | `null` |
| `baseInstalled` | `bool` | `true` |
| `hasData` | `bool` | `false` |

### Methods

- `mount( int $days = 28, ?string $siteUrl = null ): void`
- `updatedDays( int $value ): void` — Livewire lifecycle hook, re-fetches on `$days` change.
- `refresh(): void` — re-fetches from Google.
- `render(): View`

## `TopQueriesTable`

FQCN: `ArtisanPackUI\GoogleSearchConsole\Livewire\TopQueriesTable`. Alias: `google-search-console::top-queries-table`.

### Public properties

| Property | Type | Default |
|---|---|---|
| `days` | `int` | `28` |
| `limit` | `int` | `TopQueriesFetcher::DEFAULT_LIMIT` (`50`) |
| `siteUrl` | `?string` | `null` |
| `sortBy` | `string` | `'clicks'` |
| `sortDir` | `string` | `'desc'` |
| `perPage` | `int` | `10` |
| `page` | `int` | `1` |
| `rows` | `list<array{query: string, clicks: float, impressions: float, ctr: float, position: float}>` | `[]` |
| `errorMessage` | `?string` | `null` |
| `baseInstalled` | `bool` | `true` |

### Methods

- `mount( int $days = 28, int $limit = TopQueriesFetcher::DEFAULT_LIMIT, ?string $siteUrl = null ): void`
- `updatedDays( int $value ): void` — re-fetches on `$days` change, resets pagination.
- `sortByColumn( string $column ): void` — one of `query`, `clicks`, `impressions`, `ctr`, `position`. Toggles direction or resets to sensible default.
- `nextPage(): void`
- `previousPage(): void`
- `refresh(): void`
- `render(): View`

## `TopPagesTable`

FQCN: `ArtisanPackUI\GoogleSearchConsole\Livewire\TopPagesTable`. Alias: `google-search-console::top-pages-table`.

Same shape as `TopQueriesTable`. Public property differences: `rows` items use `page` instead of `query`; `sortByColumn` accepts `page`, `clicks`, `impressions`, `ctr`, `position`.

## Testing

```php
use Livewire\Livewire;

Livewire::test( PerformanceCard::class )
    ->assertSet( 'baseInstalled', true )
    ->assertSee( 'Search performance' );
```

Full patterns: [[Testing]].

## See also

- [[Components/Livewire]] — usage guide + view customisation.
- [[API Reference/Widget Wrappers]] — the CMS widget wrappers that extend these.
- [[Reporting/Fetchers]] — the fetchers each component uses under the hood.
