---
title: Installation
---

# Installation

## Install via Composer

```bash
composer require artisanpack-ui/google-search-console
```

The package auto-registers via Laravel's package discovery:

- **Service provider**: `ArtisanPackUI\GoogleSearchConsole\GoogleSearchConsoleServiceProvider`
- **Facade alias**: `GoogleSearchConsole` (`ArtisanPackUI\GoogleSearchConsole\Facades\GoogleSearchConsole`)

The base [`artisanpack-ui/google`](https://github.com/ArtisanPack-UI/google) package is pulled in transitively; if you haven't installed it yet, install and configure it now — this package's reporting side refuses to boot without it.

## Publish the config (optional)

```bash
php artisan vendor:publish --tag=google-search-console-config
```

Publishes `config/google-search-console.php`. Override the site URL, API base, cache TTL, scope list, or the HTTP route settings here. Full reference: [[Installation/Configuration|Configuration]].

## Publish the views (optional)

```bash
php artisan vendor:publish --tag=google-search-console-views
```

Copies the three Blade views to `resources/views/vendor/google-search-console/`. Publish only if you need to customize the markup. Every component's view is a plain Blade file using BEM-classed HTML; there's no daisyUI / Tailwind Component library dependency by default. See [[Components/Livewire]].

## Publish the JS components (optional)

```bash
php artisan vendor:publish --tag=google-search-console-js
```

Copies the React (`resources/js/react/`), Vue (`resources/js/vue/`), and shared fetch layer (`resources/js/shared/`) sources to `resources/js/vendor/google-search-console/`. Skip this if you'd rather import from the package's `resources/js/` directly. See [[Components/React]] and [[Components/Vue]].

## Configure your Search Console property

The package queries **one Search Console property per app**. Add it to `.env`:

```env
# URL property — include the trailing slash
GSC_SITE_URL="https://example.com/"

# Domain property — use the sc-domain: prefix
GSC_SITE_URL="sc-domain:example.com"
```

The string must literally match one of the properties [Google Search Console](https://search.google.com/search-console) lists for the connected account. Trailing slashes, http vs https, and the `sc-domain:` prefix all matter. See [[Installation/Property Verification|property verification]] for the debugging routine.

For multi-tenant apps this env var is a footgun — override the container binding instead. See [[Reporting#Multi-tenant apps]].

## OAuth scope contribution

The `webmasters.readonly` scope is contributed to the base package's shared `ScopeRegistry` automatically on boot via the `ap.google.scopes` filter hook. The single-consent screen covers Search Console alongside every other Google service.

Users who connected **before** you installed this package will have `needsReauthorize === true` on the base package's connection UI, which links them to `/google/auth/reauthorize` for the delta.

Details: [[Scopes]].

## Apply route middleware (optional)

The three HTTP routes mount under `/google-search-console` with `[ 'web', 'auth' ]` middleware. Adjust in `config/google-search-console.php`:

```php
'routes' => [
    'enabled'    => true,
    'prefix'     => 'google-search-console',
    'middleware' => [ 'web', 'auth' ],
],
```

Set `'enabled' => false` to skip HTTP route registration entirely — the correct choice for headless / API-only apps that use only the [[Reporting|server-side reporting]] API. Route reference: [[HTTP Endpoints]].

## Verify the install

```bash
php artisan route:list --path=google-search-console
```

You should see:

```
GET  google-search-console/performance    google-search-console.performance
GET  google-search-console/top-queries    google-search-console.top-queries
GET  google-search-console/top-pages      google-search-console.top-pages
```

Then from tinker:

```bash
php artisan tinker
>>> googleSearchConsole()->hasReporting()
=> true
>>> app( ArtisanPackUI\Google\Scopes\ScopeRegistry::class )->all()
=> [
     "openid",
     "https://www.googleapis.com/auth/userinfo.email",
     "https://www.googleapis.com/auth/userinfo.profile",
     "https://www.googleapis.com/auth/webmasters.readonly",
     ...
   ]
```

## Deeper topics

- [[Installation/Requirements|Requirements]] — PHP, Laravel, and peer-package versions in full detail.
- [[Installation/Configuration|Configuration]] — full `config/google-search-console.php` reference.
- [[Installation/Environment Variables|Environment variables]] — every env var the package reads.
- [[Installation/Property Verification|Property verification]] — how to confirm the site URL your account can query, plus the `/gsc-test/sites-list` debug helper pattern.

---
Continue to [[Components]] →
