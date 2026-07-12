---
title: Capabilities
---

# Capabilities

Every widget wrapper declares the same permission slug:

```
view_google_search_console
```

Granted → the widget shows up in the "Add widget" picker. Not granted → filtered out by `AdminWidgetManager::getAvailableWidgetsForUser( $user )`.

## Why one slug for all three widgets?

They all render Search Console data for the same connected Google account. A user who can see one has legitimate access to see the others — splitting the permission by report doesn't buy real security, and asking admins to manage three related permissions instead of one adds friction.

If your app genuinely needs finer-grained control (e.g., "top queries" is company-secret but "performance overview" is fine for the whole team), see [Overriding the capability per widget](#overriding-the-capability-per-widget).

## Granting the permission

The CMS framework uses [`artisanpack-ui/rbac`](https://github.com/ArtisanPack-UI/rbac) (Spatie's permission package) under the hood. Add the permission and assign it to the appropriate roles.

### Via a seeder

```php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GoogleSearchConsolePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        Permission::firstOrCreate( [ 'name' => 'view_google_search_console' ] );

        Role::findByName( 'admin' )->givePermissionTo( 'view_google_search_console' );
        Role::findByName( 'marketing' )->givePermissionTo( 'view_google_search_console' );
    }
}
```

### Via a migration

```php
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    public function up(): void
    {
        Permission::firstOrCreate( [ 'name' => 'view_google_search_console' ] );

        Role::whereIn( 'name', [ 'admin', 'marketing' ] )->each(
            fn ( Role $role ) => $role->givePermissionTo( 'view_google_search_console' ),
        );
    }

    public function down(): void
    {
        Permission::where( 'name', 'view_google_search_console' )->delete();
    }
};
```

## How the framework filters

`AdminWidgetManager::getAvailableWidgetsForUser( ?User $user )` runs on every "add widget" panel load. It:

1. Reads all registered widgets.
2. For each widget, looks up an admin-configured capability override in `apGetSetting( 'admin.dashboardWidgets', [] )` — a per-type override map, if the CMS surfaces one. Falls back to the widget's own `capability`.
3. Filters out widgets whose capability isn't granted to `$user`.

That's why the widget wrapper's `getWidgetInfo()['capability']` is a **default** — the CMS framework's runtime override wins, so an operator can rebrand or split the capability without editing this package.

## Overriding the capability per widget

If your admin wants different permissions per widget (e.g., "top queries" gated separately), use the CMS framework's per-widget capability override:

```php
apUpdateSetting( 'admin.dashboardWidgets', [
    'google-search-console.performance-card'  => 'view_gsc_performance',
    'google-search-console.top-queries-table' => 'view_gsc_secret_queries',
    'google-search-console.top-pages-table'   => 'view_gsc_performance',
] );
```

The framework reads that map inside `getAvailableWidgetsForUser()` — no package-level change needed. Details in the CMS framework's own docs.

## Users without the permission

A user who doesn't have `view_google_search_console`:

- **Won't see the widgets in the "add widget" picker** (`getAvailableWidgetsForUser()` filters them out).
- **Can still have the widget on their dashboard** if a role change happened after they added it. The framework's dashboard rendering typically hides widgets the user no longer has permission for — but that's the CMS's responsibility, not this package's.

The widget's own `mount()` doesn't re-check the permission — the framework has already vetted the request by the time render happens. Bypassing the CMS entirely (e.g., typing the Livewire alias into a Blade template) will render the widget regardless of permissions; treat unauthorized rendering as a CMS-integration concern.

## Naming rationale

The slug `view_google_search_console` is:

- **`view_`-prefixed** — matches WordPress and RBAC convention that `view` is a read-only capability.
- **package-scoped** — includes the package name so it can't collide with an app's own `view_search_console`.

If you're worried about it clashing with a future scope (e.g., a `manage_google_search_console` write permission this package might introduce), notice that this package's client is read-only — the read-only scope [[Scopes|contributed to the base package]] wouldn't grant write access anyway. Adding `manage_*` later without a matching write feature is unlikely.

## See also

- [[CMS Framework Bridge]] — bridge overview.
- [[CMS Framework Bridge/Widgets]] — widget classes and metadata.
- [[CMS Framework Bridge/Integration]] — installing and using with the CMS.
