---
title: Support
---

# Support

Support classes under `ArtisanPackUI\GoogleSearchConsole\Support\*`. Small utilities the rest of the package composes with.

## `BaseInstalled`

Presence detector for the `artisanpack-ui/google` base package. FQCN: `ArtisanPackUI\GoogleSearchConsole\Support\BaseInstalled`.

Final, static-only.

### `check(): bool`

`true` when both `\ArtisanPackUI\Google\Google` and `\ArtisanPackUI\Google\Tokens\TokenManager` are loadable. Cached per process — subsequent calls short-circuit.

Used by:

- `GoogleSearchConsoleServiceProvider` — to gate routes, scope hook, and the `SearchAnalyticsClient` construction.
- `SearchAnalyticsClient::ensureBaseInstalled()` — to throw `BaseNotInstalledException` cleanly instead of a class-not-found fatal.
- Every Livewire component's `mount()` and `refresh()`.

### `reset(): void`

Clears the memoised result. Only used by tests. Called in the base `TestCase::setUp()` and `tearDown()`.

### `setForTesting( ?bool $value ): void`

Forces the memoised result to a specific value. Test-only helper for exercising the "base not installed" branch without literally uninstalling the package from the autoloader.

## `CmsFrameworkInstalled`

Same pattern as `BaseInstalled` but for `artisanpack-ui/cms-framework`. FQCN: `ArtisanPackUI\GoogleSearchConsole\Support\CmsFrameworkInstalled`.

Final, static-only.

### `check(): bool`

`true` when both `\ArtisanPackUI\CMSFramework\Modules\AdminWidgets\Contracts\AdminWidgetInterface` and `\ArtisanPackUI\CMSFramework\Modules\AdminWidgets\Services\AdminWidgetManager` are loadable. Cached per process.

Used by `GoogleSearchConsoleServiceProvider::registerCmsFrameworkWidgets()` to gate the entire [bridge](CMS-Framework-Bridge).

### `reset(): void`

Clears the memoised result. Called in the base `TestCase::setUp()` and `tearDown()`.

### `setForTesting( ?bool $value ): void`

Same as `BaseInstalled::setForTesting()` but for the CMS framework.

## `GoogleConnectionResolver`

Turns an authenticated user into a `GoogleConnection` (or `null`). FQCN: `ArtisanPackUI\GoogleSearchConsole\Support\GoogleConnectionResolver`.

Registered as a singleton so tenant middlewares can rebind it.

### `forUser( ?Authenticatable $user ): ?GoogleConnection`

Returns the first `GoogleConnection` row with `status = connected` for the given user, ordered by `updated_at DESC`. Returns `null` when:

- `$user` is null.
- The base package isn't installed (the `GoogleConnection` model isn't loadable).
- The user has no connected rows.

Used by:

- Every Livewire component's `refresh()`.
- Every HTTP controller.

### Multi-tenant rebinding

Override the binding in a tenant middleware if the same user model has different rows per tenant:

```php
$this->app->instance( GoogleConnectionResolver::class, new class extends GoogleConnectionResolver {
    public function forUser( ?Authenticatable $user ): ?GoogleConnection
    {
        return GoogleConnection::query()
            ->where( 'user_id', $user?->id )
            ->where( 'tenant_id', tenant()->id )
            ->where( 'status', GoogleConnection::STATUS_CONNECTED )
            ->orderByDesc( 'updated_at' )
            ->first();
    }
} );
```

## See also

- [FAQ](FAQ#runtime) — memoisation behavior and reset patterns.
- [Testing](Testing) — how to use `setForTesting` in tests.
- [Reporting](Reporting#multi-tenant-apps) — full multi-tenant pattern.
