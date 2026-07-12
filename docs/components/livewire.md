---
title: Livewire Components
---

# Livewire Components

Three Livewire components are auto-registered when `livewire/livewire ^3.6` is installed. All render server-side, so no bundler config is needed.

## Requirements

- `livewire/livewire ^3.6` installed and running.
- The base `artisanpack-ui/google` package installed with the current user's Google account connected.

If Livewire isn't installed, `GoogleSearchConsoleServiceProvider::registerLivewireComponents()` returns without registering — the package still boots and the React / Vue surfaces still work.

## `performance-card`

A four-tile summary (clicks, impressions, avg CTR, avg position) plus a daily-clicks trend chart, for a rolling range.

### Basic usage

```blade
<livewire:google-search-console::performance-card />
```

### With overrides

```blade
<livewire:google-search-console::performance-card
    :days="90"
    :site-url="'sc-domain:staging.example.com'"
/>
```

### Public properties

| Property | Type | Default | Notes |
|---|---|---|---|
| `days` | `int` | `28` | Clamped to `1 <= days <= DateRange::MAX_DAYS`. |
| `siteUrl` | `?string` | `null` | Overrides `config('google-search-console.reporting.site_url')` for this instance. |
| `totals` | `?array{clicks: float, impressions: float, ctr: float, position: float}` | `null` | Populated after `refresh()`. `null` while never fetched. |
| `trend` | `list<array{date: string, clicks: float, impressions: float, ctr: float, position: float}>` | `[]` | One entry per day in the range. |
| `hasData` | `bool` | `false` | `false` when the API returned no rows (fresh property / last 2–3 finalising days). |
| `errorMessage` | `?string` | `null` | Human-readable error or `null`. |
| `baseInstalled` | `bool` | `true` | Flips to `false` if `BaseInstalled::check()` returns false. |

### Actions

- `refresh()` — re-fetches from Google. Called automatically on mount and every 60 seconds via `wire:poll.60s`.
- `updatedDays()` — Livewire lifecycle hook that re-fetches when `days` changes (used by the range dropdown in the default view).

### View

`google-search-console::livewire.performance-card`. Publish with `--tag=google-search-console-views` to customize; Laravel's view resolver prefers the published copy over the package copy.

## `top-queries-table`

Ranked table of the highest-performing search queries. Server fetches the rows once; client-side sort + pagination.

### Basic usage

```blade
<livewire:google-search-console::top-queries-table />
```

### With overrides

```blade
<livewire:google-search-console::top-queries-table
    :days="90"
    :limit="100"
    :site-url="'https://staging.example.com/'"
/>
```

### Public properties

| Property | Type | Default | Notes |
|---|---|---|---|
| `days` | `int` | `28` | Clamped to `1..DateRange::MAX_DAYS`. |
| `limit` | `int` | `TopQueriesFetcher::DEFAULT_LIMIT` (`50`) | Clamped to `1..1000`. |
| `siteUrl` | `?string` | `null` | Overrides the configured site URL. |
| `sortBy` | `string` | `'clicks'` | One of `query`, `clicks`, `impressions`, `ctr`, `position`. |
| `sortDir` | `string` | `'desc'` | `'asc'` or `'desc'`. |
| `perPage` | `int` | `10` | Client-side paginate size. |
| `page` | `int` | `1` | Clamped in `nextPage()` / `previousPage()`. |
| `rows` | `list<array{query: string, clicks: float, impressions: float, ctr: float, position: float}>` | `[]` | All rows the server returned. |

### Actions

- `refresh()` — re-fetch from Google. Auto-called on mount.
- `sortByColumn(string $column)` — flip sort direction if same column, else set new column with a sensible default direction (`position` and `query` → `asc`, everything else → `desc`).
- `nextPage()` / `previousPage()` — clamp to `[1, ceil(count / perPage)]`.
- `updatedDays()` — re-fetch and reset to page 1 when `days` changes.

## `top-pages-table`

Same shape as `top-queries-table` but keyed by page URL instead of query text.

### Basic usage

```blade
<livewire:google-search-console::top-pages-table />
```

### Public properties

Same as top-queries-table, but `rows` items have a `page` key instead of `query`, and `sortBy` accepts `page`, `clicks`, `impressions`, `ctr`, `position`.

## Refresh from other code

Every component listens for its own `refresh` message and re-fetches when it's dispatched. From another Livewire component or Alpine:

```blade
<button wire:click="$dispatchTo('google-search-console::performance-card', 'refresh')">
    Refresh performance card
</button>
```

Or from JS:

```html
<script>
    Livewire.dispatch('refresh');  // fires on every listening component
</script>
```

## Customising the views

```bash
php artisan vendor:publish --tag=google-search-console-views
```

Copies `resources/views/livewire/performance-card.blade.php`, `top-queries-table.blade.php`, and `top-pages-table.blade.php` under `resources/views/vendor/google-search-console/`.

The default views use plain HTML with `ap-gsc-*` BEM classes — no Tailwind classes, no daisyUI dependency. Style them however you want.

## Using ArtisanPack UI components inside

If you're already on `livewire-ui-components`, publish and rewrite the views with `<x-artisanpack-*>` components:

```blade
{{-- resources/views/vendor/google-search-console/livewire/performance-card.blade.php --}}
<x-artisanpack-card wire:poll.60s="refresh">
    <x-slot:header>
        {{ __( 'Search performance' ) }}
    </x-slot:header>

    @if ( ! $baseInstalled )
        <x-artisanpack-alert type="warning">
            {{ __( 'Install artisanpack-ui/google to enable Search Console reporting.' ) }}
        </x-artisanpack-alert>
    @elseif ( $errorMessage )
        <x-artisanpack-alert type="error">{{ $errorMessage }}</x-artisanpack-alert>
    @elseif ( ! $hasData )
        <p>{{ __( 'No data for this range yet.' ) }}</p>
    @else
        {{-- render totals + trend --}}
    @endif
</x-artisanpack-card>
```

## Testing

```php
use ArtisanPackUI\GoogleSearchConsole\Livewire\PerformanceCard;
use Livewire\Livewire;
use Illuminate\Support\Facades\Http;

Http::fakeSequence()
    ->push( [ 'rows' => [ [ 'keys' => [], 'clicks' => 42, 'impressions' => 500, 'ctr' => 0.084, 'position' => 3.2 ] ] ], 200 )
    ->push( [ 'rows' => [ [ 'keys' => [ '2026-01-01' ], 'clicks' => 42, 'impressions' => 500, 'ctr' => 0.084, 'position' => 3.2 ] ] ], 200 );

Livewire::test( PerformanceCard::class )
    ->assertSet( 'baseInstalled', true )
    ->assertSet( 'hasData', true )
    ->assertSet( 'totals.clicks', 42.0 )
    ->assertSee( 'Search performance' );
```

Full test patterns in [Testing](Testing).

## The classes

- `ArtisanPackUI\GoogleSearchConsole\Livewire\PerformanceCard`
- `ArtisanPackUI\GoogleSearchConsole\Livewire\TopQueriesTable`
- `ArtisanPackUI\GoogleSearchConsole\Livewire\TopPagesTable`

Every rule about what to show lives on the DTO layer (`PerformanceOverviewData`, `TopQueriesData`, `TopPagesData`) so the Livewire, React, and Vue surfaces cannot drift. See [API Reference/Livewire Components](API-Reference-Livewire-Components) for method signatures.
