---
title: CMS Framework Bridge
---

# CMS Framework Bridge

An **optional** bridge that surfaces the three [[Components/Livewire|Livewire components]] as admin dashboard widgets for [`artisanpack-ui/cms-framework`](https://github.com/ArtisanPack-UI/cms-framework). When the framework is installed the widgets appear in the "Add Widget" panel automatically; when it isn't, the bridge is dormant and the package keeps running exactly as before.

The bridge is a strict, one-directional integration:

- **No hard dep** on `artisanpack-ui/cms-framework` — the framework only appears in `composer.json` under `suggest`.
- The three widget wrappers **extend** the underlying Livewire components and **implement** the framework's `AdminWidgetInterface`. Publishing views or overriding styles for the base components carries over automatically.
- The bridge only touches the framework's `AdminWidgetManager` when both the framework and Livewire are on the autoloader.

Sub-pages:

- [[CMS Framework Bridge/Widgets|The three widget wrappers]] — class names, widget types, metadata.
- [[CMS Framework Bridge/Integration|Integration]] — installing, verifying, and rendering the widgets.
- [[CMS Framework Bridge/Capabilities|Capabilities]] — the `view_google_search_console` permission and how the framework filters widgets per user.

## Quick reference

The three widget wrappers live under `src/Bridges/CmsFramework/AdminWidgets/`:

| Widget type | Class |
|---|---|
| `google-search-console.performance-card` | `PerformanceCardWidget` |
| `google-search-console.top-queries-table` | `TopQueriesTableWidget` |
| `google-search-console.top-pages-table` | `TopPagesTableWidget` |

Each declares `capability = view_google_search_console`. Grant that permission (via your app's RBAC layer — likely [`artisanpack-ui/rbac`](https://github.com/ArtisanPack-UI/rbac)) to any role that should be able to add these widgets to their dashboard.

## When the bridge is active

`registerCmsFrameworkWidgets()` runs on boot. It short-circuits when either:

- `CmsFrameworkInstalled::check()` returns false — the framework's interface or manager class isn't loadable. OR
- `Livewire\Livewire` doesn't exist — Livewire isn't installed.

Both conditions must be true for the bridge to touch anything.

`CmsFrameworkInstalled::check()` caches its result per process. In tests, `CmsFrameworkInstalled::reset()` clears the cache and `CmsFrameworkInstalled::setForTesting( true|false )` forces a specific state. This mirrors the pattern used by the base package's `BaseInstalled`.

## What the bridge does at boot

```php
protected function registerCmsFrameworkWidgets(): void
{
    if ( ! CmsFrameworkInstalled::check() ) { return; }
    if ( ! class_exists( \Livewire\Livewire::class ) ) { return; }

    $manager = $this->app->make( AdminWidgetManager::class );

    foreach ( self::cmsFrameworkWidgetTypeMap() as $type => $class ) {
        $manager->register( $type, $class );
    }

    Livewire::component( 'google-search-console::performance-card-widget', PerformanceCardWidget::class );
    Livewire::component( 'google-search-console::top-queries-table-widget', TopQueriesTableWidget::class );
    Livewire::component( 'google-search-console::top-pages-table-widget', TopPagesTableWidget::class );
}
```

Three registrations against the framework's `AdminWidgetManager`, plus three `-widget` Livewire aliases so the CMS can render the wrappers by name if it wants to (the CMS's own convention is to render by class, so the aliases are a convenience).

## Rendering by class

`AdminWidgetManager::register( $type, $class )` stores the FQCN. The framework's dashboard renders widgets by:

```blade
@livewire( $widget['component_class'], $widget['options'] ?? [] )
```

Which is why the widget wrappers **extend** the Livewire components — the wrapper is a valid Livewire component in its own right, and Livewire renders it via the inherited `render()` and view.

## What the bridge does NOT do

- **Persist widget selections** — that's the CMS framework's job.
- **Filter widgets by user permission** — also the framework's job (`AdminWidgetManager::getAvailableWidgetsForUser()`). This package just declares the capability slug.
- **Provide framework migrations, seeders, or default dashboards** — the CMS framework owns its own admin setup.
- **Modify markup for the widgets** — the wrappers inherit everything from the base Livewire components. Publish and edit the base views to restyle both the standalone components and the widgets.

## Testing the bridge

The package's own `tests/Feature/CmsFrameworkBridgeTest.php` uses local test stubs under `tests/Stubs/CmsFrameworkAdminWidgets/` autoloaded into the real cms-framework namespace via `autoload-dev`, so the bridge is fully exercised without pulling the full cms-framework package (and its Scramble / Sanctum / RBAC deps) into `require-dev`. See [[Testing]].

---
Continue to [[API Reference]] →
