---
title: Scopes
---

# Scopes

The Google OAuth scope this package needs — `https://www.googleapis.com/auth/webmasters.readonly` — is contributed to the base package's shared `ScopeRegistry` automatically. Users see a single OAuth consent screen covering Search Console alongside every other Google service the app uses.

## What the package registers

Contents of `config/google-search-console.php`:

```php
'scopes' => [
    'https://www.googleapis.com/auth/webmasters.readonly',
],
```

This is a **read-only** scope: it grants performance data (`searchAnalytics.query`), site listing (`sites.list`), and URL Inspection reads. It does **not** grant permission to add / verify sites or to write anything.

## How the registration works

On boot, `GoogleSearchConsoleServiceProvider::registerGoogleScopeHook()` hooks the base package's `ap.google.scopes` filter with a callback that appends this package's scopes:

```php
addFilter( 'ap.google.scopes', static function ( array $scopes ) use ( $config ): array {
    $ours = (array) $config->get( 'google-search-console.scopes', [] );

    return array_values( array_unique( array_merge( $scopes, array_map( 'strval', $ours ) ) ) );
} );
```

Every time the base package's `ScopeRegistry::all()` runs, this callback fires, and `webmasters.readonly` ends up in the union.

The hook itself is only registered when `BaseInstalled::check()` is true and the `addFilter` helper exists — the package boots safely on hosts that don't have the base package or the hooks package installed. See [[FAQ#Scopes]] for behavior when the base package is added *after* this one.

## What the single-consent screen looks like

Before this package:

```
This app is requesting access to:
  • Your email address
  • Your basic profile info
```

After installing this package:

```
This app is requesting access to:
  • Your email address
  • Your basic profile info
  • View Search Console data for your verified sites
```

The last line is Google's human phrasing of `webmasters.readonly`. Every service package (`analytics-google`, `google-tag-manager`, …) adds its own line the same way.

## Users who connected before installation

If a user connected the base package *before* you added this one:

- The user's `google_connections` row has `scopes` = `[openid, userinfo.email, userinfo.profile]` (no `webmasters.readonly`).
- The base package's `ScopeRegistry::hasAllRequired()` will now return `false` — the union has more entries than the row grants.
- The base package's connection UI surfaces this via `state.needsReauthorize === true`, showing a "Reauthorize" button that links to `/google/auth/reauthorize`.
- The reauthorize flow uses `include_granted_scopes=true`, so the user only approves the delta — in this case, just `webmasters.readonly`.

You don't have to do anything programmatically. The base package's connection UI handles it. Details: the base package's [Connection UI docs](https://github.com/ArtisanPack-UI/google/blob/main/docs/connection-ui.md) and [OAuth Flow docs](https://github.com/ArtisanPack-UI/google/blob/main/docs/oauth.md).

## Adding your own scopes

The base package's `ScopeRegistry` accepts imperative registrations from app code:

```php
use ArtisanPackUI\Google\Facades\Google;

Google::scopes()->register( 'https://www.googleapis.com/auth/some-other-scope' );
```

For scopes tied to a specific service package, prefer the `ap.google.scopes` filter hook — same shape this package uses:

```php
use ArtisanPackUI\Hooks\Facades\Filter;

Filter::add( 'ap.google.scopes', fn ( array $scopes ) => [
    ...$scopes,
    'https://www.googleapis.com/auth/some-other-scope',
] );
```

Register it in a service provider's `boot()` method so it's live before any consent-URL builder runs.

**Then** — add the scope in [Google Cloud Console](https://console.cloud.google.com/) under **APIs & Services → OAuth consent screen → Scopes**. Google rejects authorize requests that ask for scopes not listed there with `invalid_scope`.

## Testing scope registration

The package's own `tests/Feature/ServiceProviderTest.php` covers this — reference pattern:

```php
use ArtisanPackUI\Google\Scopes\ScopeRegistry;
use ArtisanPackUI\GoogleSearchConsole\Support\BaseInstalled;

it( 'contributes the webmasters.readonly scope to the ScopeRegistry', function (): void {
    BaseInstalled::setForTesting( true );

    $registry = app( ScopeRegistry::class );

    expect( $registry->all() )->toContain( 'https://www.googleapis.com/auth/webmasters.readonly' );
} );
```

The base package's `ScopeRegistry` reads the filter hook every time `all()` is called, so any test that boots the service provider picks up the contribution.

## What if I need a write scope?

This package intentionally only contributes the read-only scope. If your app needs a write scope for the Search Console API (e.g., `https://www.googleapis.com/auth/webmasters` — the read/write version), don't override `google-search-console.scopes` — that would just replace the read-only scope with the write one, and Google's newer OAuth flow may reject a mixed scope set.

Instead, register the write scope alongside via the imperative API or a separate filter hook:

```php
Filter::add( 'ap.google.scopes', fn ( array $scopes ) => [
    ...$scopes,
    'https://www.googleapis.com/auth/webmasters',  // read/write
] );
```

Then add it in Google Cloud Console and prompt users to reauthorize. This package's client only uses reads — it won't accidentally trigger writes just because the write scope was granted.

## Reference

- Google's [Search Console API scopes](https://developers.google.com/webmaster-tools/v1/how-tos/authorizing) reference.
- The base package's [[Scopes|scope registry docs]].
