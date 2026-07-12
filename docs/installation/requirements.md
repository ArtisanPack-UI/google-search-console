---
title: Requirements
---

# Requirements

## PHP

- **PHP 8.2 or newer.** The package uses constructor property promotion, readonly properties, typed enum-style constants, and `declare( strict_types=1 )` everywhere.

## Laravel

- **Laravel 10.x, 11.x, 12.x, or 13.x.** The package requires `illuminate/support` on the matching majors, plus `illuminate/http` (for the HTTP client factory and controllers).

The service provider registers with either the classic `config/app.php` providers array (Laravel 10) or `bootstrap/providers.php` (Laravel 11+). Laravel's package discovery handles it automatically.

## HTTP client

- **Guzzle 7.5+** (`guzzlehttp/guzzle`). Used by the `SearchAnalyticsClient` to talk to Google. Laravel apps almost always have Guzzle installed already; if you're on a stripped-down install, add it explicitly.

## Required ArtisanPack packages

- **`artisanpack-ui/core` ^1.0** — transitively via other packages.
- **`artisanpack-ui/google` ^1.0** — provides the OAuth flow, `TokenManager`, and `GoogleConnection` model that this package's reporting side depends on. Install and configure the base package before installing this one. If the base is absent at runtime, the reporting side of this package returns null and the components render an install-the-base-package call to action.

## Optional peer packages

| Package | Version | What it enables |
|---|---|---|
| [`livewire/livewire`](https://livewire.laravel.com/) | `^3.6` | The three `<livewire:google-search-console::*>` Livewire components and the [[CMS Framework Bridge]]. The React and Vue components do not require Livewire. |
| [`artisanpack-ui/cms-framework`](https://github.com/ArtisanPack-UI/cms-framework) | any | The [[CMS Framework Bridge]] — three widget wrappers register with the framework's `AdminWidgetManager` so the components drop straight onto the CMS admin dashboard. |

Neither is required. The base package boots and works without them; the CMS bridge is dormant when the framework isn't installed, and the Livewire components are only registered when Livewire's class is autoloadable.

## Search Console prerequisites

The `SearchAnalyticsClient` calls the Search Console API. That needs:

- **A verified Search Console property** the connected Google account can query — see [[Installation/Property Verification|property verification]].
- **The `webmasters.readonly` OAuth scope** granted to the connected account. This package contributes the scope to the shared registry automatically, so the base package's `/connect` and `/reauthorize` flows will request it — but users who connected before this package was installed need to reauthorize.

## Encryption

This package does **not** encrypt any data of its own. Everything sensitive (access tokens, refresh tokens) lives in the base package's `google_connections` table, encrypted via `APP_KEY`. See the base package's [Requirements](https://github.com/ArtisanPack-UI/google/blob/main/docs/installation/requirements.md#encryption) page for the `APP_KEY` rotation caveats.

## Cache

The `SearchAnalyticsClient` optionally caches successful API responses for `google-search-console.reporting.cache_ttl` seconds (default `300`). It uses Laravel's default cache store — whatever driver you have configured (`file`, `redis`, `memcached`, `array`, `dynamodb`, `database`) works. Set `cache_ttl` to `0` to disable caching entirely. Cache keys include the connection identifier and the query payload hash so distinct users' data cannot cross-contaminate.

## Session driver

The package does **not** rely on the session for anything. The [[HTTP Endpoints|HTTP endpoints]] use standard `auth` middleware; the session role there is Laravel's normal one.

## Frontend runtime (JS components only)

The React and Vue components are shipped as source (`.tsx` / `.vue`). If you use them:

- **React**: 18+ (uses `useEffect`, `useState`, `useCallback`, standard hooks).
- **Vue**: 3+ (uses the Composition API with `<script setup>`).
- A bundler (Vite, Webpack, Turbopack, …) that compiles TSX / Vue SFCs.

Both share a small fetch client in `resources/js/shared/` — no framework-specific state library dependencies.

The base package works without a frontend build — Livewire renders the components server-side.
