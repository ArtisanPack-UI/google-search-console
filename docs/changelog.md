---
title: Changelog
---

# Changelog

The authoritative changelog lives at [`CHANGELOG.md`](../CHANGELOG.md) in the package root. Every release adds an entry there; the wiki mirrors the shape.

## [Unreleased]

## [1.0.0] - 2026-07-11

Initial release. Search Console performance data and drop-in UI components on top of the shared `artisanpack-ui/google` OAuth2 + token management layer.

### Added

- **`SearchAnalyticsClient`** — typed wrapper over Google's `searchAnalytics.query` endpoint, with per-connection-per-query cache keys, config-driven timeout, and well-typed exceptions.
- **Three high-level fetchers** — `PerformanceOverviewFetcher`, `TopQueriesFetcher`, `TopPagesFetcher` — returning typed DTOs.
- **Typed value objects** — `DateRange`, `SearchAnalyticsRequest`, `SearchAnalyticsResponse`, plus the three fetcher-specific `*Data` DTOs.
- **Three Livewire components** — `PerformanceCard`, `TopQueriesTable`, `TopPagesTable`. Auto-registered under `google-search-console::*` aliases when Livewire is installed.
- **Three React components** and **three Vue 3 components** — mirror the Livewire APIs, ship as source under `resources/js/`, share a single TypeScript fetch client.
- **Three JSON HTTP endpoints** — `GET /performance`, `/top-queries`, `/top-pages`. Deliberately ignore any user-supplied `?site_url` override.
- **OAuth scope contribution** — `webmasters.readonly` contributed to the base package's `ScopeRegistry` via the `ap.google.scopes` filter hook.
- **Optional CMS framework bridge** — three widget wrappers register with `artisanpack-ui/cms-framework`'s `AdminWidgetManager` when both the framework and Livewire are installed. Bridge dormant otherwise.
- **`CmsFrameworkInstalled` + `BaseInstalled` support classes** — presence detectors with per-process memoisation and test hooks.
- **`GoogleConnectionResolver`** — user → connected `GoogleConnection` mapping. Rebindable per tenant.
- **Cache layer** — `SearchAnalyticsClient` caches successful responses via Laravel's default cache store. Per-connection cache keys prevent cross-tenant contamination.
- **Publishable assets** — `google-search-console-config`, `google-search-console-views`, `google-search-console-js`.
- **Comprehensive test suite** — 61 tests covering the client, fetchers, controllers, Livewire component rendering, and the CMS bridge.
- **Comprehensive `/docs` tree** — this documentation.
- **Laravel 10, 11, 12, and 13 support** via widened `illuminate/support` constraint.
- **CI + Release workflows** — GitHub Actions matrix testing PHP 8.2/8.3/8.4 × Laravel 12/13, plus a tag-driven release workflow with Packagist push.

### Security

- Controllers ignore any user-supplied `?site_url` query string.
- Cache keys hash the connection ID + query payload so distinct users cannot see each other's cached rows.
- The `SearchAnalyticsClient` timeout falls back to 30 seconds when the config value is `null` / `0` / non-numeric.

### Notes

- No new database migrations. All persisted data lives in the base package's `google_connections` table.
- No secrets stored by this package. `GSC_SITE_URL` is public information.
