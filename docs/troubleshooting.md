---
title: Troubleshooting
---

# Troubleshooting

Common errors and what to check first. If your problem isn't here, the [FAQ](FAQ) covers the "why is this designed like that?" questions.

## Error messages

### `The artisanpack-ui/google base package is required for Search Console reporting but is not installed or is not fully bootable.`

Thrown from `SearchAnalyticsClient::query()` or the constructor's `null` `$tokens`. Means `BaseInstalled::check()` returned false.

**Check:**

1. Is `artisanpack-ui/google` in `composer.json`? Run `composer show artisanpack-ui/google` — expect the current version.
2. Did you run `composer install` / `composer dump-autoload`?
3. In tinker: `class_exists( ArtisanPackUI\Google\Google::class )` — should be `true`. If false, autoloader isn't picking it up.
4. Long-running process (Octane, Horizon)? `BaseInstalled::reset()` between requests clears the memoised result.

### `Google Search Console site URL is not configured. Set google-search-console.reporting.site_url or GSC_SITE_URL.`

Thrown when neither the `$siteUrl` argument nor config supplied a value.

**Check:**

- `.env`: `GSC_SITE_URL="..."` — set?
- Run `php artisan config:clear` — the config cache may be stale.
- In tinker: `config('google-search-console.reporting.site_url')` — should show the value.

### `Search Console authentication failed: ...`

Thrown from `ReportingException::authenticationFailed()`. The base package's `TokenManager` threw a `TokenRefreshException`.

**Check:**

1. `GoogleConnection` row: `status = 'connected'`? If not, the user needs to reconnect.
2. `disconnect_reason` column: if set, that's the base package's own explanation (typically "Refresh token revoked or expired.").
3. Did `APP_KEY` rotate without re-encrypting the tokens? Every connection is dead if so — see the base package's [FAQ](https://github.com/ArtisanPack-UI/google/blob/main/docs/faq.md#what-happens-when-i-rotate-app_key).

### `Search Console API request failed to reach Google.`

`ReportingException::transportFailure()`. Guzzle raised a `ConnectionException`.

**Check:**

1. Is `googleapis.com` reachable from your app server? `curl -v https://searchconsole.googleapis.com/webmasters/v3/sites`.
2. Firewall / proxy blocking outbound HTTPS?
3. `google-search-console.reporting.timeout` too low for slow networks? Bump it, or lower the range you're querying.

### `Search Console API returned an error (status 403).`

`ReportingException::apiError( 403, ... )`. Google refused.

**Check:**

- The account has `siteOwner` or `siteFullUser` permission on the property. `siteRestrictedUser` isn't enough for `searchAnalytics.query`.
- The `webmasters.readonly` scope was granted. Users who connected before this package was installed need to reauthorize — the base package's connection UI surfaces this.
- The property is verified (not just added-but-unverified).

### `Search Console API returned an error (status 404).`

`ReportingException::apiError( 404, ... )`. The property URL doesn't match a verified property.

**Check:**

- `GSC_SITE_URL` matches one of the `siteUrl` values from `sites.list` verbatim. See [Installation/Property Verification](Installation-Property-Verification).
- Trailing slashes, `http` vs `https`, `www` prefix all matter.

### `Search Console API returned an error (status 429).`

Rate limit hit. Google's Search Console API quota is per-account + per-property + per-day.

**Check:**

- Are multiple worker processes hitting the same property? Serialise via a queue.
- The `SearchAnalyticsClient` cache should keep this rare — check `google-search-console.reporting.cache_ttl` is > 0.
- If you must, throttle in your job scheduler (`RateLimited` middleware in Laravel queues).

### `Search Console API returned an error (status 500).` (or 502/503)

Google-side. Retry with backoff — the client doesn't retry on its own.

## Component symptoms

### Livewire component shows "Install artisanpack-ui/google" persistently

`$baseInstalled` was set to `false` at mount. Base package isn't autoloadable. Same diagnosis as the `BaseNotInstalledException` message above.

### Livewire component shows "Connect a Google account to view Search Console data."

`GoogleConnectionResolver::forUser()` returned `null`. The signed-in user has no `google_connections` row with `status = connected`.

**Check:**

- User hits `/google/auth/connect` (base package route) → gets bounced back with a working row?
- Row exists but `status = disconnected`? Check the `disconnect_reason` column.
- Multi-tenant app? The connection is scoped by `user_id`; if your tenant model doesn't reuse the `User` model, override `GoogleConnectionResolver` — see [API Reference/Support](API-Reference-Support#googleconnectionresolver).

### Component renders but every metric is `0`

Data actually is zero, or Google's rate for the recent days isn't finalised yet.

**Check:**

- `hasData` public property (Livewire) / `data.hasData` (React/Vue) — false means Google returned no rows; the component should be rendering an empty state, not zeros. If you customized the view, make sure the empty branch renders.
- Change the range to `90` or `180` — new properties often show zeros for the trailing 2–3 days.
- In tinker: run `PerformanceOverviewFetcher::fetch()` directly and inspect the raw `$data->totals`.

### React / Vue component always shows "Not authenticated"

Endpoint returned `401`. The auth middleware failed.

**Check:**

- The fetch includes cookies: `credentials: 'same-origin'` in your fetch options. `fetchGscPerformance()` sets this by default; if you call `fetch()` directly, add it.
- Session cookie is valid — check DevTools → Application → Cookies.

### CMS bridge widgets don't appear in the "Add widget" panel

Check in order:

1. `CmsFrameworkInstalled::check()` returns `true`.
2. `class_exists( Livewire\Livewire::class )` returns `true`.
3. `app( AdminWidgetManager::class )->getAvailableWidgets()` returns the three keys.
4. The current user has the `view_google_search_console` capability.

The first three are the bridge's responsibility; the fourth is your RBAC layer's.

## Cache & configuration

### Changed `GSC_SITE_URL` but the endpoint still queries the old property

Config is cached. Run `php artisan config:clear` (or `php artisan optimize:clear` for everything). If you're using `config:cache` in production, rebuild.

The `SearchAnalyticsClient` also caches successful responses — a warm cache from before the change will still return the old data. Either wait `cache_ttl` seconds or forget the keys manually:

```bash
php artisan tinker
>>> Redis::keys('google-search-console:query:*')->each(fn($k) => Cache::forget($k))
```

### Two tenants see each other's data

Almost certainly a cache key collision, or the resolver isn't tenant-scoped.

**Check:**

- Cache keys include `$connection->getKey()` — distinct `user_id` produces distinct keys, so this should be safe by default.
- Multi-tenant middleware overrides `GoogleConnectionResolver` (see [API Reference/Support](API-Reference-Support#googleconnectionresolver)).
- No shared connection ID (unlikely — the base package's `google_connections.user_id` is unique).

### The Livewire component's `wire:poll.60s` hits the server but the response doesn't update

Either the cache is warm (see above) or the underlying data hasn't updated.

**Check:**

- Search Console updates once per day at most. Polling at 60s intervals mostly returns the same numbers.
- Lower `cache_ttl` if you want faster polling to be useful.

## Diagnostic recipes

### Dump what the client is sending Google

```php
use Illuminate\Support\Facades\Http;

Http::macro( 'debug', function () {
    return $this->beforeSending( function ( $request ) {
        logger( 'GSC request', [
            'url'     => $request->url(),
            'headers' => $request->headers(),
            'body'    => $request->body(),
        ] );
    } );
} );

// Then use ->debug()->post() in a local rebind of SearchAnalyticsClient.
```

Or use Guzzle's built-in `HandlerStack` middleware — same effect.

### Verify the scope registration end to end

```bash
php artisan tinker
>>> app( ArtisanPackUI\Google\Scopes\ScopeRegistry::class )->all()
=> [ ..., "https://www.googleapis.com/auth/webmasters.readonly", ... ]
>>> app( ArtisanPackUI\Google\Facades\Google::class )::oauth()->authorizationUrl( auth()->id() )
=> "https://accounts.google.com/o/oauth2/v2/auth?..."
```

The URL should include `scope=...%20webmasters.readonly...`.

### Confirm the CMS bridge is registering the widgets

```php
php artisan tinker
>>> ArtisanPackUI\GoogleSearchConsole\Support\CmsFrameworkInstalled::check()
=> true
>>> array_keys( app( ArtisanPackUI\CMSFramework\Modules\AdminWidgets\Services\AdminWidgetManager::class )->getAvailableWidgets() )
=> [
     "google-search-console.performance-card",
     "google-search-console.top-queries-table",
     "google-search-console.top-pages-table",
     // ... plus whatever other packages contribute
   ]
```

## Still stuck?

- [Testing](Testing) — every failure mode above is covered by the package's own test suite. If you're not sure whether a behavior is a bug or a design, check `tests/Feature/*` for the reference.
- [FAQ](FAQ) — design rationale for anything that seems weird.
- GitHub issues on [`artisanpack-ui/google-search-console`](https://github.com/ArtisanPack-UI/google-search-console) — file a repro.
