---
title: Integration
---

# Integration

How to install, verify, and use the CMS framework bridge in a real app.

## Install both packages

```bash
composer require artisanpack-ui/cms-framework
composer require artisanpack-ui/google-search-console
```

Both auto-register their service providers. Order doesn't matter — the bridge inspects the autoloader at `boot()` time via `CmsFrameworkInstalled::check()`, which runs after both providers' `register()` phases.

Then follow both packages' base install steps:

- `artisanpack-ui/google-search-console`: [[Installation|full install walkthrough]] — property verification, `GSC_SITE_URL`, etc.
- `artisanpack-ui/cms-framework`: run its migrations and configure the admin dashboard route as documented in the framework's own docs.

## Verify the bridge is active

```bash
php artisan tinker
>>> ArtisanPackUI\GoogleSearchConsole\Support\CmsFrameworkInstalled::check()
=> true
>>> app( ArtisanPackUI\CMSFramework\Modules\AdminWidgets\Services\AdminWidgetManager::class )
        ->getAvailableWidgets()
```

Expect a keyed array with three entries starting with `google-search-console.`:

```
[
    "google-search-console.performance-card" => [
        "title"           => "Search performance",
        "description"     => "Search Console clicks, impressions, CTR, ...",
        "capability"      => "view_google_search_console",
        "default_options" => [ "days" => 28, "siteUrl" => null ],
    ],
    "google-search-console.top-queries-table" => [ ... ],
    "google-search-console.top-pages-table"   => [ ... ],
]
```

If the array is missing the `google-search-console.*` entries, check:

- The base `artisanpack-ui/google` package is installed and configured.
- Livewire is installed (`class_exists( \Livewire\Livewire::class ) === true`).
- `CmsFrameworkInstalled::check()` returns `true` (see the tinker line above).
- No service provider is overriding `AdminWidgetManager` in a way that resets it after `boot()`.

## Add widgets to a user's dashboard

The CMS framework owns the "add widget" UX. Once the bridge is active, the three widgets appear in whatever picker the framework provides. The user picks one, and the framework calls `AdminWidgetManager::createWidget( 'google-search-console.performance-card' )` under the hood — which returns:

```php
[
    'id'              => '<uuid>',
    'type'            => 'google-search-console.performance-card',
    'component_class' => PerformanceCardWidget::class,
    'title'           => 'Search performance',
    'capability'      => 'view_google_search_console',
    'order'           => 0,
    'color_scheme'    => 'base-100',
    'grid_config'     => [ 'sm' => [...], 'md' => [...], 'lg' => [...], 'xl' => [...] ],
    'options'         => [ 'days' => 28, 'siteUrl' => null ],
    'created_at'      => '2026-01-01T00:00:00Z',
    'updated_at'      => '2026-01-01T00:00:00Z',
]
```

The framework persists that array on the user's dashboard. When rendering, it calls the Livewire component (`component_class`) with `options`.

## Rendering pattern

Assuming the framework's dashboard view iterates persisted widgets, the rendering call looks like:

```blade
@foreach ( $dashboard->widgets as $widget )
    @livewire( $widget['component_class'], $widget['options'] ?? [] )
@endforeach
```

Which resolves to:

```blade
@livewire( \ArtisanPackUI\GoogleSearchConsole\Bridges\CmsFramework\AdminWidgets\PerformanceCardWidget::class, [
    'days'    => 28,
    'siteUrl' => null,
] )
```

The wrapper's `mount( int $days = 28, ?string $siteUrl = null )` runs — inherited from `PerformanceCard::mount()` — and the widget renders exactly like the standalone Livewire component would.

## Publishing views to customise widgets

The widget wrappers inherit `render()` from their base Livewire components, so publishing the standalone views customises the widgets too:

```bash
php artisan vendor:publish --tag=google-search-console-views
```

Edit `resources/views/vendor/google-search-console/livewire/performance-card.blade.php` and the change flows through to both `<livewire:google-search-console::performance-card>` and the CMS-hosted `PerformanceCardWidget`.

## Rendering without the CMS framework

Even without the framework, you can render the widget wrappers by their `-widget` Livewire aliases:

```blade
<livewire:google-search-console::performance-card-widget />
```

This is the standalone Livewire component behavior — no metadata, no framework integration — just the same output as the base component. Useful for testing or for demos of what a widget looks like in isolation.

## Uninstalling / disabling

- **Remove one widget only**: the CMS framework's UI lets a user remove a widget from their dashboard. The `AdminWidgetManager` registration is unchanged; the user just doesn't have it on their dashboard anymore.
- **Disable the bridge entirely**: uninstall `artisanpack-ui/cms-framework`. The bridge short-circuits on `CmsFrameworkInstalled::check() === false` and doesn't touch anything.
- **Keep the framework, hide the widgets**: revoke the `view_google_search_console` capability from every role. The framework's `getAvailableWidgetsForUser()` filters them out.

## See also

- [[CMS Framework Bridge]] — overview.
- [[CMS Framework Bridge/Widgets]] — widget classes and metadata.
- [[CMS Framework Bridge/Capabilities]] — permission handling.
- [[Testing]] — how the package's tests exercise the bridge without pulling in the full cms-framework dep.
